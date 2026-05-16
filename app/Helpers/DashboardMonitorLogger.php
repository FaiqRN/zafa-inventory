<?php

namespace App\Helpers;

use App\Models\DashboardMonitorLog;
use Illuminate\Http\Request;

class DashboardMonitorLogger
{
    /**
     * Catat aktivitas Create.
     *
     * @param  string       $module      Nama modul (misal: 'Barang', 'Toko')
     * @param  string       $description Deskripsi singkat (misal: 'Tambah barang KODE001')
     * @param  array|null   $newData     Data yang baru dibuat
     * @param  Request|null $request
     */
    public static function create(
        string $module,
        string $description,
        ?array $newData = null,
        ?Request $request = null
    ): void {
        static::log('create', $module, $description, null, $newData, $request);
    }

    /**
     * Catat aktivitas Update.
     */
    public static function update(
        string $module,
        string $description,
        ?array $oldData = null,
        ?array $newData = null,
        ?Request $request = null
    ): void {
        static::log('update', $module, $description, $oldData, $newData, $request);
    }

    /**
     * Catat aktivitas Delete.
     */
    public static function delete(
        string $module,
        string $description,
        ?array $oldData = null,
        ?Request $request = null
    ): void {
        static::log('delete', $module, $description, $oldData, null, $request);
    }

    /**
     * Catat log ke database.
     */
    private static function log(
        string $action,
        string $module,
        string $description,
        ?array $oldData,
        ?array $newData,
        ?Request $request
    ): void {
        try {
            DashboardMonitorLog::create([
                'username'    => AuditHelper::currentUsername(),
                'action'      => $action,
                'module'      => $module,
                'description' => $description,
                'old_data'    => $oldData,
                'new_data'    => $newData,
                'ip_address'  => $request?->ip() ?? request()?->ip(),
                'user_agent'  => $request?->userAgent() ?? request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Jangan sampai error logging menghentikan proses utama
            \Illuminate\Support\Facades\Log::warning('DashboardMonitorLogger failed: ' . $e->getMessage());
        }
    }
}
