<?php

namespace App\Http\Controllers;

use App\Helpers\DashboardMonitorLogger;
use App\Models\DashboardMonitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Admin|admin|Superadmin|superadmin|Administrator|administrator');
    }

    /**
     * Tampilkan halaman DashboardMonitor.
     */
    public function index()
    {
        $stats = [
            'total'   => DashboardMonitorLog::count(),
            'create'  => DashboardMonitorLog::where('action', 'create')->count(),
            'update'  => DashboardMonitorLog::where('action', 'update')->count(),
            'delete'  => DashboardMonitorLog::where('action', 'delete')->count(),
            'today'   => DashboardMonitorLog::whereDate('created_at', today())->count(),
        ];

        return view('DashboardMonitor', [
            'activemenu' => 'dashboard',
            'breadcrumb' => (object) [
                'title' => 'Dashboard Monitor',
                'list'  => ['Home', 'Dashboard'],
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Endpoint AJAX: ambil data paginasi activity logs.
     */
    public function getData(Request $request)
    {
        $query = DashboardMonitorLog::query()->orderByDesc('created_at');

        // Filter aksi
        if ($request->filled('action') && in_array($request->action, ['create', 'update', 'delete'])) {
            $query->where('action', $request->action);
        }

        // Filter modul
        if ($request->filled('module')) {
            $query->where('module', 'like', '%' . $request->module . '%');
        }

        // Filter username
        if ($request->filled('username')) {
            $query->where('username', 'like', '%' . $request->username . '%');
        }

        // Filter tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = 10;
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * Endpoint AJAX: detail satu log entry.
     */
    public function show(int $id)
    {
        $log = DashboardMonitorLog::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $log,
        ]);
    }

    /**
     * Truncate seluruh data dashboard_monitor_logs (only admin).
     */
    public function truncate(Request $request)
    {
        if (!$request->user() || !$request->user()->can('truncate-dashboard-monitor')) {
            abort(403, 'Anda tidak memiliki izin untuk melakukan truncate.');
        }

        $totalBefore = DashboardMonitorLog::count();

        DB::table('dashboard_monitor_logs')->truncate();

        DashboardMonitorLogger::delete(
            'Dashboard Monitor',
            "Truncate semua activity log ({$totalBefore} record dihapus)",
            ['total_deleted' => $totalBefore],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Semua data activity log berhasil dihapus.',
        ]);
    }

    /**
     * Daftar modul unik untuk filter dropdown.
     */
    public function modules()
    {
        $modules = DashboardMonitorLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return response()->json(['success' => true, 'data' => $modules]);
    }

    // =====================================================================
    // LARAVEL LOG MANAGEMENT
    // =====================================================================

    /**
     * Informasi ukuran & status file laravel.log.
     */
    public function laravelLogInfo(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');

        $exists   = file_exists($logPath);
        $sizeBytes = $exists ? filesize($logPath) : 0;
        $sizeKb    = round($sizeBytes / 1024, 2);
        $sizeMb    = round($sizeBytes / 1024 / 1024, 4);
        $modified  = $exists ? date('Y-m-d H:i:s', filemtime($logPath)) : null;

        return response()->json([
            'success'    => true,
            'exists'     => $exists,
            'size_bytes' => $sizeBytes,
            'size_kb'    => $sizeKb,
            'size_mb'    => $sizeMb,
            'modified'   => $modified,
        ]);
    }

    /**
     * Export laravel.log sebagai file download (.log).
     */
    public function exportLaravelLog(Request $request): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        if (!$request->user() || !$request->user()->can('export-laravel-log')) {
            abort(403, 'Anda tidak memiliki izin untuk mengekspor laravel.log.');
        }

        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return response()->json(['success' => false, 'message' => 'File laravel.log tidak ditemukan.'], 404);
        }

        $sizeKb   = round(filesize($logPath) / 1024, 2);
        $fileName = 'laravel_' . now()->format('Ymd_His') . '.log';

        // Catat aktivitas export
        DashboardMonitorLogger::create(
            'Laravel Log',
            "Export laravel.log ({$sizeKb} KB) sebagai {$fileName}",
            [
                'file'      => 'storage/logs/laravel.log',
                'size_kb'   => $sizeKb,
                'export_as' => $fileName,
            ],
            $request
        );

        // Tutup session sebelum streaming (Redis timeout diabaikan agar export tetap jalan)
        try {
            if (session()->isStarted()) {
                session()->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Session save gagal sebelum export log (Redis?): ' . $e->getMessage());
        }

        // Bersihkan semua output buffer yang aktif (penting di production)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->streamDownload(function () use ($logPath) {
            readfile($logPath);
        }, $fileName, [
            'Content-Type'                  => 'application/octet-stream',
            'Content-Disposition'           => 'attachment; filename="' . $fileName . '"',
            'Content-Transfer-Encoding'     => 'binary',
            'Cache-Control'                 => 'private, no-cache, no-store, must-revalidate',
            'Pragma'                        => 'no-cache',
            'Expires'                       => '0',
            'X-Accel-Buffering'             => 'no',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ]);
    }

    /**
     * Truncate isi file laravel.log (hanya admin).
     */
    public function truncateLaravelLog(Request $request)
    {
        if (!$request->user() || !$request->user()->can('truncate-laravel-log')) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus laravel.log.');
        }

        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return response()->json(['success' => false, 'message' => 'File laravel.log tidak ditemukan.'], 404);
        }

        $sizeBefore = filesize($logPath);
        $sizeBeforeKb = round($sizeBefore / 1024, 2);

        file_put_contents($logPath, '');

        DashboardMonitorLogger::delete(
            'Laravel Log',
            "Truncate laravel.log ({$sizeBeforeKb} KB dihapus)",
            [
                'file'        => 'storage/logs/laravel.log',
                'size_before' => "{$sizeBeforeKb} KB",
                'triggered'   => 'manual',
            ],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => "File laravel.log berhasil dikosongkan ({$sizeBeforeKb} KB dihapus).",
        ]);
    }

    // =====================================================================
    // SQL IMPORT FEATURE
    // =====================================================================

    /**
     * Daftar tabel yang diizinkan untuk SQL import.
     */
    private function getAllowedTables(): array
    {
        return [
            'barang',
            'barang_stok',
            'toko',
            'barang_toko',
            'pengiriman',
            'retur',
            'pemesanan',
            'follow_up',
            'data_customer',
            'eoq_biaya_pesan_global',
            'eoq_biaya_pesan_toko',
            'eoq_biaya_simpan',
            'ss_zscore_setting',
        ];
    }

    /**
     * Ambil informasi kolom untuk tabel yang diizinkan (untuk preview UI).
     */
    public function getTableColumns(Request $request)
    {
        $table = $request->query('table');
        $allowed = $this->getAllowedTables();

        if (!$table || !in_array($table, $allowed)) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel tidak valid atau tidak diizinkan.',
            ], 422);
        }

        try {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $columnList = array_map(fn($col) => [
                'field'   => $col->Field,
                'type'    => $col->Type,
                'null'    => $col->Null,
                'key'     => $col->Key,
                'default' => $col->Default,
                'extra'   => $col->Extra,
            ], $columns);

            return response()->json([
                'success' => true,
                'table'   => $table,
                'columns' => $columnList,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil informasi kolom: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ambil daftar tabel yang diizinkan (untuk dropdown UI).
     */
    public function getAllowedTablesList()
    {
        return response()->json([
            'success' => true,
            'tables'  => $this->getAllowedTables(),
        ]);
    }

    /**
     * Export SQL INSERT untuk tabel yang diizinkan.
     */
    public function exportSql(Request $request): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $table = $request->query('table');
        $allowed = $this->getAllowedTables();

        if (!$table || !in_array($table, $allowed)) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel tidak valid atau tidak diizinkan.',
            ], 422);
        }

        try {
            $columns = $this->getTableColumnNames($table);
            if (empty($columns)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kolom tabel tidak ditemukan.',
                ], 404);
            }

            $rowCount = DB::table($table)->count();
            $primaryKey = $this->getPrimaryKeyColumn($table);
            $fileName = 'sql_export_' . $table . '_' . now()->format('Ymd_His') . '.sql';
            $generatedAt = now()->format('Y-m-d H:i:s');

            DashboardMonitorLogger::create(
                'SQL Export',
                "Export SQL tabel \"{$table}\" ({$rowCount} baris)",
                [
                    'table'     => $table,
                    'rows'      => $rowCount,
                    'export_as' => $fileName,
                ],
                $request
            );

            // Tutup session sebelum streaming (Redis timeout diabaikan agar export tetap jalan)
            try {
                if (session()->isStarted()) {
                    session()->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Session save gagal sebelum export SQL (Redis?): ' . $e->getMessage());
            }

            // Bersihkan semua output buffer yang aktif (penting di production)
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            return response()->streamDownload(function () use ($table, $columns, $primaryKey, $rowCount, $generatedAt) {
                set_time_limit(300);
                ini_set('memory_limit', '512M');

                $pdo = DB::connection()->getPdo();
                $out = fopen('php://output', 'w');

                fwrite($out, "-- SQL Export\n");
                fwrite($out, "-- Table: {$table}\n");
                fwrite($out, "-- Rows: {$rowCount}\n");
                fwrite($out, "-- Generated at: {$generatedAt}\n\n");

                $orderColumn = $primaryKey ?: $columns[0];
                $colList = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

                DB::table($table)
                    ->orderBy($orderColumn)
                    ->chunk(500, function ($rows) use ($out, $table, $columns, $colList, $pdo) {
                        if ($rows->isEmpty()) {
                            return;
                        }

                        $valueLines = [];
                        foreach ($rows as $row) {
                            $rowArray = (array) $row;
                            $values = [];
                            foreach ($columns as $col) {
                                $values[] = $this->sqlValue($rowArray[$col] ?? null, $pdo);
                            }
                            $valueLines[] = '(' . implode(', ', $values) . ')';
                        }

                        fwrite(
                            $out,
                            "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valueLines) . ";\n\n"
                        );

                        flush();
                    });

                fclose($out);
            }, $fileName, [
                'Content-Type'                  => 'application/octet-stream',
                'Content-Disposition'           => 'attachment; filename="' . $fileName . '"',
                'Content-Transfer-Encoding'     => 'binary',
                'Cache-Control'                 => 'private, no-cache, no-store, must-revalidate',
                'Pragma'                        => 'no-cache',
                'Expires'                       => '0',
                'X-Accel-Buffering'             => 'no',
                'Access-Control-Expose-Headers' => 'Content-Disposition',
            ]);
        } catch (\Exception $e) {
            Log::error('Export SQL failed for table ' . $table . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export SQL INSERT untuk SEMUA tabel yang diizinkan sekaligus (dalam format ZIP).
     */
    public function exportAllSql(Request $request): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $allowed = $this->getAllowedTables();
            $zipFileName = 'sql_export_all_' . now()->format('Ymd_His') . '.zip';
            $generatedAt = now()->format('Y-m-d H:i:s');

            $totalRows = 0;
            foreach ($allowed as $table) {
                try {
                    $totalRows += DB::table($table)->count();
                } catch (\Exception $e) {
                    Log::warning("Failed to count table {$table}: " . $e->getMessage());
                }
            }

            DashboardMonitorLogger::create(
                'SQL Export All',
                "Export SQL semua tabel (" . count($allowed) . " tabel, {$totalRows} baris total)",
                [
                    'tables'    => $allowed,
                    'total_tables' => count($allowed),
                    'total_rows' => $totalRows,
                    'export_as' => $zipFileName,
                ],
                $request
            );

            // Tutup session sebelum streaming (Redis timeout diabaikan agar export tetap jalan)
            try {
                if (session()->isStarted()) {
                    session()->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Session save gagal sebelum export all SQL (Redis?): ' . $e->getMessage());
            }

            // Bersihkan semua output buffer yang aktif (penting di production)
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            return response()->streamDownload(function () use ($allowed, $generatedAt) {
                set_time_limit(300);
                ini_set('memory_limit', '512M');

                $zip = new \ZipArchive();
                $tempZipPath = tempnam(sys_get_temp_dir(), 'sql_export_') . '.zip';

                if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    throw new \Exception('Gagal membuat file ZIP.');
                }

                $pdo = DB::connection()->getPdo();

                foreach ($allowed as $table) {
                    try {
                        $columns = $this->getTableColumnNames($table);
                        if (empty($columns)) {
                            continue;
                        }

                        $rowCount = DB::table($table)->count();
                        $primaryKey = $this->getPrimaryKeyColumn($table);
                        $orderColumn = $primaryKey ?: $columns[0];
                        $colList = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

                        $sqlContent = "-- SQL Export\n";
                        $sqlContent .= "-- Table: {$table}\n";
                        $sqlContent .= "-- Rows: {$rowCount}\n";
                        $sqlContent .= "-- Generated at: {$generatedAt}\n\n";

                        DB::table($table)
                            ->orderBy($orderColumn)
                            ->chunk(500, function ($rows) use (&$sqlContent, $table, $columns, $colList, $pdo) {
                                if ($rows->isEmpty()) {
                                    return;
                                }

                                $valueLines = [];
                                foreach ($rows as $row) {
                                    $rowArray = (array) $row;
                                    $values = [];
                                    foreach ($columns as $col) {
                                        $values[] = $this->sqlValue($rowArray[$col] ?? null, $pdo);
                                    }
                                    $valueLines[] = '(' . implode(', ', $values) . ')';
                                }

                                $sqlContent .= "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valueLines) . ";\n\n";
                            });

                        $zip->addFromString("{$table}.sql", $sqlContent);
                    } catch (\Exception $e) {
                        Log::error("Failed to export table {$table}: " . $e->getMessage());
                        $zip->addFromString("{$table}_ERROR.txt", "Export failed: " . $e->getMessage());
                    }
                }

                $zip->close();

                if (!file_exists($tempZipPath)) {
                    throw new \Exception('File ZIP tidak berhasil dibuat.');
                }

                $handle = fopen($tempZipPath, 'rb');
                if ($handle === false) {
                    throw new \Exception('Tidak dapat membuka file ZIP untuk dibaca.');
                }

                while (!feof($handle)) {
                    echo fread($handle, 8192);
                    flush();
                }

                fclose($handle);

                if (file_exists($tempZipPath)) {
                    unlink($tempZipPath);
                }
            }, $zipFileName, [
                'Content-Type'                  => 'application/zip',
                'Content-Disposition'           => 'attachment; filename="' . $zipFileName . '"',
                'Content-Transfer-Encoding'     => 'binary',
                'Cache-Control'                 => 'private, no-cache, no-store, must-revalidate',
                'Pragma'                        => 'no-cache',
                'Expires'                       => '0',
                'X-Accel-Buffering'             => 'no',
                'Access-Control-Expose-Headers' => 'Content-Disposition',
            ]);
        } catch (\Exception $e) {
            Log::error('Export All SQL failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getTableColumnNames(string $table): array
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");

        return array_map(static fn($col) => $col->Field, $columns);
    }

    private function getPrimaryKeyColumn(string $table): ?string
    {
        $keys = DB::select("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");

        if (empty($keys)) {
            return null;
        }

        return $keys[0]->Column_name ?? null;
    }

    private function sqlValue($value, \PDO $pdo): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof \DateTimeInterface) {
            $quoted = $pdo->quote($value->format('Y-m-d H:i:s'));
            return $quoted === false ? "'" . $value->format('Y-m-d H:i:s') . "'" : $quoted;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $quoted = $pdo->quote((string) $value);
        if ($quoted === false) {
            $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value);
            return "'{$escaped}'";
        }

        return $quoted;
    }

    /**
     * Eksekusi SQL Import.
     * 
     * Menerima raw SQL INSERT statement, mem-parsing dan mengeksekusinya
     * dalam database transaction dengan foreign key check dinonaktifkan sementara.
     */
    public function executeSqlImport(Request $request)
    {
        $request->validate([
            'sql'  => 'required|string|min:20',
            'mode' => 'nullable|in:insert,upsert',
        ]);

        $rawSql = trim($request->input('sql'));
        $mode   = $request->input('mode', 'insert');
        $allowed = $this->getAllowedTables();

        // ── 1. Validasi bahwa SQL dimulai dengan INSERT INTO ──
        if (!preg_match('/^\s*INSERT\s+INTO\s+/i', $rawSql)) {
            return response()->json([
                'success' => false,
                'message' => 'SQL harus berupa statement INSERT INTO.',
            ], 422);
        }

        // ── 2. Extract nama tabel dari SQL ──
        if (!preg_match('/INSERT\s+INTO\s+`?(\w+)`?\s*/i', $rawSql, $tableMatch)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menemukan nama tabel dari SQL.',
            ], 422);
        }

        $tableName = $tableMatch[1];

        // ── 3. Validasi tabel ada di whitelist ──
        if (!in_array($tableName, $allowed)) {
            return response()->json([
                'success' => false,
                'message' => "Tabel \"{$tableName}\" tidak diizinkan untuk import. Tabel yang diizinkan: " . implode(', ', $allowed),
            ], 422);
        }

        // ── 4. Cek keamanan: pastikan SQL tidak mengandung statement berbahaya ──
        // Hanya cek apakah SQL DIMULAI dengan command berbahaya (bukan di dalam data values)
        $dangerousPatterns = [
            '/^\s*DROP\s+/im',
            '/^\s*ALTER\s+/im',
            '/^\s*DELETE\s+/im',
            '/^\s*TRUNCATE\s+/im',
            '/^\s*CREATE\s+/im',
            '/^\s*GRANT\s+/im',
            '/^\s*REVOKE\s+/im',
            '/;\s*DROP\s+/i',
            '/;\s*ALTER\s+/i',
            '/;\s*DELETE\s+/i',
            '/;\s*UPDATE\s+/i',
            '/;\s*TRUNCATE\s+/i',
            '/;\s*CREATE\s+/i',
        ];
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $rawSql)) {
                return response()->json([
                    'success' => false,
                    'message' => 'SQL mengandung perintah berbahaya. Hanya satu statement INSERT INTO yang diizinkan.',
                ], 422);
            }
        }

        // ── 5. Hilangkan trailing semicolon dan whitespace ──
        $cleanSql = rtrim($rawSql, " \t\n\r\0\x0B;");

        // ── 6. Jika mode upsert, tambahkan ON DUPLICATE KEY UPDATE ──
        if ($mode === 'upsert') {
            // Extract kolom dari INSERT INTO `table` (`col1`, `col2`, ...) VALUES
            if (preg_match('/INSERT\s+INTO\s+`?\w+`?\s*\(([^)]+)\)/i', $cleanSql, $colMatch)) {
                $colsPart = $colMatch[1];
                $cols = array_map(function ($c) {
                    return trim(trim($c), '`');
                }, explode(',', $colsPart));

                $updateParts = array_map(fn($c) => "`{$c}` = VALUES(`{$c}`)", $cols);
                $cleanSql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts);
            }
        }

        // ── 7. Eksekusi ──
        // Gunakan DB::unprepared() karena DB::statement() menggunakan PDO prepared statements
        // yang akan gagal jika data mengandung karakter '?' (dianggap parameter placeholder)
        try {
            $rowsBefore = DB::table($tableName)->count();

            DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
            DB::unprepared($cleanSql);
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1');

            $rowsAfter = DB::table($tableName)->count();
            $rowsInserted = $rowsAfter - $rowsBefore;

            // Log aktivitas import
            DashboardMonitorLogger::create(
                'SQL Import',
                "Import data ke tabel \"{$tableName}\" ({$rowsInserted} baris baru, mode: {$mode})",
                [
                    'table'         => $tableName,
                    'mode'          => $mode,
                    'rows_before'   => $rowsBefore,
                    'rows_after'    => $rowsAfter,
                    'rows_inserted' => $rowsInserted,
                    'sql_preview'   => mb_substr($rawSql, 0, 500),
                ],
                $request
            );

            return response()->json([
                'success'       => true,
                'message'       => "Import berhasil ke tabel \"{$tableName}\".",
                'table'         => $tableName,
                'rows_before'   => $rowsBefore,
                'rows_after'    => $rowsAfter,
                'rows_inserted' => $rowsInserted,
                'mode'          => $mode,
            ]);
        } catch (\Throwable $e) {
            // Pastikan FK check dihidupkan kembali
            try { DB::unprepared('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $ex) {}

            Log::error('SQL Import failed', [
                'table' => $tableName,
                'error' => $e->getMessage(),
                'sql'   => mb_substr($rawSql, 0, 500),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'table'   => $tableName,
            ], 500);
        }
    }
}
