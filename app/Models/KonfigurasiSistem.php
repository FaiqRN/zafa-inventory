<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class KonfigurasiSistem extends Model
{
    public const TABLE = 'konfigurasi_sistem';

    // ── Key-key yang dikenal sistem ──────────────────────────────────────────
    public const KEY_MIN_INTERVAL_KIRIM_HARI = 'min_interval_kirim_hari';

    // ── Defaults ─────────────────────────────────────────────────────────────
    public const DEFAULT_MIN_INTERVAL_KIRIM_HARI = 14;

    // ── Cache TTL (5 menit — cukup cepat untuk responsif, cukup lambat untuk efisien) ──
    public const CACHE_TTL_SECONDS = 300;

    protected $table    = self::TABLE;
    protected $fillable = ['key', 'nilai', 'tipe', 'label', 'keterangan', 'user_update'];

    // ──────────────────────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Ambil nilai konfigurasi berdasarkan key, dengan fallback default.
     * Hasil di-cache selama CACHE_TTL_SECONDS agar tidak membebani DB.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            "konfigurasi_sistem:{$key}",
            self::CACHE_TTL_SECONDS,
            function () use ($key, $default) {
                $row = static::where('key', $key)->first();
                return $row ? $row->getCastedValue() : $default;
            }
        );
    }

    /**
     * Simpan / update nilai konfigurasi dan hapus cache-nya.
     */
    public static function set(string $key, mixed $nilai, int|string|null $userId = null): self
    {
        $userUpdate = is_numeric($userId) ? (int) $userId : null;
        $row = static::updateOrCreate(
            ['key' => $key],
            ['nilai' => (string) $nilai, 'user_update' => $userUpdate]
        );

        Cache::forget("konfigurasi_sistem:{$key}");

        return $row;
    }

    /**
     * Hapus cache key tertentu (misal setelah update via admin).
     */
    public static function clearCache(string $key): void
    {
        Cache::forget("konfigurasi_sistem:{$key}");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // INSTANCE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Cast nilai ke tipe yang sesuai berdasarkan kolom `tipe`.
     */
    public function getCastedValue(): mixed
    {
        return match ($this->tipe) {
            'integer' => (int) $this->nilai,
            'float'   => (float) $this->nilai,
            'boolean' => filter_var($this->nilai, FILTER_VALIDATE_BOOLEAN),
            default   => $this->nilai,
        };
    }
}
