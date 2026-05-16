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

        return response()->streamDownload(function () use ($logPath) {
            readfile($logPath);
        }, $fileName, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
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
}
