<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\RoleHelper;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission;
use App\Models\Role as OldRole;
use stdClass;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-users');
    }

    public function index()
    {
        $breadcrumb = new stdClass();
        $breadcrumb->title = 'Manajemen Role';
        $breadcrumb->list = ['Home', 'Role', 'Manajemen Role'];
        $permissions = $this->getGroupedPermissions();

        return view('role.index', [
            'activemenu' => 'role',
            'breadcrumb' => $breadcrumb,
            'permissions' => $permissions
        ]);
    }

    public function getData()
    {
        $roles = SpatieRole::withCount('users')->get();
        
        $data = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions->count(),
                'guard_name' => $role->guard_name,
                'created_at' => $role->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function edit($id)
    {
        $role = SpatieRole::with('permissions')->find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name'
        ], [
            'name.required' => 'Nama role harus diisi',
            'name.unique' => 'Nama role sudah digunakan',
            'permissions.required' => 'Minimal pilih 1 permission',
            'permissions.min' => 'Minimal pilih 1 permission'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $spatieRole = SpatieRole::create([
                'name' => $request->name,
                'guard_name' => 'web'
            ]);

            $spatieRole->givePermissionTo($request->permissions);

            $oldRole = OldRole::create([
                'nama_role' => $request->name,
                'deskripsi' => 'Custom role created by admin'
            ]);

            DB::commit();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'status' => 'success',
                'message' => 'Role berhasil ditambahkan',
                'data' => $spatieRole
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $role = SpatieRole::find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role tidak ditemukan'
            ], 404);
        }

        if (\in_array(\strtolower($role->name), ['admin', 'superadmin', 'administrator'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role Admin tidak dapat diubah. Admin harus memiliki akses penuh ke semua fitur.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name'
        ], [
            'name.required' => 'Nama role harus diisi',
            'name.unique' => 'Nama role sudah digunakan',
            'permissions.required' => 'Minimal pilih 1 permission',
            'permissions.min' => 'Minimal pilih 1 permission'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role->name = $request->name;
            $role->save();

            $role->syncPermissions($request->permissions);

            $oldRole = OldRole::where('nama_role', $role->getOriginal('name'))->first();
            if ($oldRole) {
                $oldRole->nama_role = $request->name;
                $oldRole->save();
            }

            DB::commit();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'status' => 'success',
                'message' => 'Role berhasil diperbarui',
                'data' => $role
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $role = SpatieRole::withCount('users')->find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role tidak ditemukan'
            ], 404);
        }

        if (\in_array(\strtolower($role->name), ['admin', 'superadmin', 'administrator'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role Admin tidak dapat dihapus'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $usersWithRole = \App\Models\User::query()
                ->whereHas('roles', function ($query) use ($role) {
                    $query->where('name', $role->name);
                })
                ->get();

            /** @var \App\Models\User $user */
            foreach ($usersWithRole as $user) {
                $user->removeRole($role->name);
            }

            $oldRole = OldRole::where('nama_role', $role->name)->first();
            if ($oldRole) {
                DB::table('user')->where('role_id', $oldRole->role_id)->update(['role_id' => null]);
                $oldRole->delete();
            }

            $role->delete();

            DB::commit();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'status' => 'success',
                'message' => "Role berhasil dihapus. {$role->users_count} user sekarang hanya dapat mengakses dashboard."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus role: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getGroupedPermissions()
    {
        $permissions = Permission::all();
        $grouped = [];

        $grouped['Special'] = $permissions->filter(function ($perm) {
            return \in_array($perm->name, ['manage-master-data', 'manage-users', 'manage-notification-settings', 'view-notifications']);
        })->values();

        $grouped['Analytics'] = $permissions->filter(function ($perm) {
            return \in_array($perm->name, ['view-partner-performance']);
        })->values();

        $grouped['Barang'] = $permissions->filter(function ($perm) {
            return \preg_match('/-barang$/', $perm->name);
        })->values();

        $grouped['Barang Toko'] = $permissions->filter(function ($perm) {
            return \str_contains($perm->name, '-barang-toko');
        })->values();

        $grouped['Toko'] = $permissions->filter(function ($perm) {
            return \preg_match('/-toko$/', $perm->name) && !\str_contains($perm->name, 'barang-toko');
        })->values();

        $modules = [
            'Dashboard'                        => 'dashboard',
            'Customer'                         => 'customer',
            'Pemesanan'                        => 'pemesanan',
            'Pengiriman'                       => 'pengiriman',
            'Retur'                            => 'retur',
            'Follow Up'                        => 'follow-up',
            'EOQ Setting'                      => 'eoq-setting',
            'Z-Score Setting'                  => 'zscore-setting',
            'Konfigurasi Interval Pengiriman' => 'config-interval-kirim',
            'Partner Performance'              => 'partner-performance',
        ];

        foreach ($modules as $label => $module) {
            $grouped[$label] = $permissions->filter(function ($perm) use ($module) {
                return \str_contains($perm->name, "-{$module}");
            })->values();
        }

        return $grouped;
    }
}

