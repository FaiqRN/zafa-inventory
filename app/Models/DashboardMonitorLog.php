<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardMonitorLog extends Model
{
    protected $table = 'dashboard_monitor_logs';

    protected $fillable = [
        'username',
        'action',
        'module',
        'description',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    /**
     * Label warna Bootstrap untuk badge aksi.
     */
    public function getActionBadgeAttribute(): string
    {
        return match ($this->action) {
            'create' => 'success',
            'update' => 'warning',
            'delete' => 'danger',
            default  => 'secondary',
        };
    }

    /**
     * Label teks Indonesia untuk aksi.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create' => 'Tambah',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            default  => ucfirst($this->action),
        };
    }

    /**
     * Hapus log yang lebih tua dari $days hari.
     */
    public static function cleanup(int $days = 30): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
