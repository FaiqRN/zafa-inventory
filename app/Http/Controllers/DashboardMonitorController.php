<?php

namespace App\Http\Controllers;

use App\Models\DashboardMonitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'activemenu' => 'dashboard-monitor',
            'breadcrumb' => (object) [
                'title' => 'Dashboard Monitor',
                'list'  => ['Home', 'Dashboard Monitor'],
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

        DB::table('dashboard_monitor_logs')->truncate();

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
}
