<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'ip_address',
        'user_agent',
        'successful',
        'attempted_at',
        'locked_until',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Check if account is currently locked
     *
     * @param string $username
     * @return bool
     */
    public static function isLocked(string $username): bool
    {
        $latestAttempt = self::where('username', $username)
            ->whereNotNull('locked_until')
            ->latest('locked_until')
            ->first();

        if (!$latestAttempt) {
            return false;
        }

        return $latestAttempt->locked_until->isFuture();
    }

    /**
     * Get remaining lock time in seconds
     *
     * @param string $username
     * @return int
     */
    public static function getLockTimeRemaining(string $username): int
    {
        $latestAttempt = self::where('username', $username)
            ->whereNotNull('locked_until')
            ->latest('locked_until')
            ->first();

        if (!$latestAttempt || !$latestAttempt->locked_until->isFuture()) {
            return 0;
        }

        return $latestAttempt->locked_until->diffInSeconds(now());
    }

    /**
     * Count failed attempts in the last X minutes
     *
     * @param string $username
     * @param int $minutes
     * @return int
     */
    public static function countRecentFailedAttempts(string $username, int $minutes = 15): int
    {
        return self::where('username', $username)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Record a login attempt
     *
     * @param string $username
     * @param string $ipAddress
     * @param string|null $userAgent
     * @param bool $successful
     * @return self
     */
    public static function recordAttempt(
        string $username,
        string $ipAddress,
        ?string $userAgent,
        bool $successful
    ): self {
        return self::create([
            'username' => $username,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Lock account for specified minutes
     *
     * @param string $username
     * @param int $minutes
     * @return void
     */
    public static function lockAccount(string $username, int $minutes = 30): void
    {
        self::create([
            'username' => $username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'successful' => false,
            'attempted_at' => now(),
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Clear failed attempts for username
     *
     * @param string $username
     * @return void
     */
    public static function clearAttempts(string $username): void
    {
        self::where('username', $username)
            ->where('successful', false)
            ->delete();
    }

    /**
     * Clean old attempts (older than 7 days)
     *
     * @return int
     */
    public static function cleanOldAttempts(): int
    {
        return self::where('attempted_at', '<', now()->subDays(7))->delete();
    }
}
