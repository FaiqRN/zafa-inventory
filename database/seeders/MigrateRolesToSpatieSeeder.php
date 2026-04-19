<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Helpers\RoleHelper;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission;

class MigrateRolesToSpatieSeeder extends Seeder
{

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $oldRoles = DB::table('role')->get();

        $roleMapping = [];

        foreach ($oldRoles as $oldRole) {
            $spatieRole = SpatieRole::firstOrCreate([
                'name' => $oldRole->nama_role,
                'guard_name' => 'web'
            ]);

            $roleMapping[$oldRole->role_id] = $spatieRole;

            echo "✓ Role '{$oldRole->nama_role}' berhasil dibuat/ditemukan\n";
        }

        $specialPermissions = [
            'manage-master-data' => 'Akses penuh Master Data',
            'manage-users' => 'Akses penuh User Management',
            'manage-notification-settings' => 'Akses Pengaturan Notifikasi',
            'view-barang' => 'Lihat Data Barang',
            'view-dashboard-inventory-optimization' => 'Akses Dashboard Inventory Optimization',
            'view-dashboard-partner-performance' => 'Akses Dashboard Partner Performance',
        ];

        foreach ($specialPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
            echo "✓ Permission '{$name}' berhasil dibuat\n";
        }

        $modules = [
            'dashboard' => 'Dashboard',
            'user' => 'User Management',
            'barang' => 'Barang',
            'barang-toko' => 'Barang Toko',
            'toko' => 'Toko',
            'customer' => 'Customer',
            'pemesanan' => 'Pemesanan',
            'pengiriman' => 'Pengiriman',
            'retur' => 'Retur',
            'follow-up' => 'Follow Up Pelanggan',
            'eoq-setting' => 'EOQ Setting',
            'zscore-setting' => 'Z-Score Setting',
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($modules as $module => $label) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}-{$module}",
                    'guard_name' => 'web'
                ]);
            }
            echo "✓ Permissions untuk modul '{$label}' berhasil dibuat\n";
        }

        $this->assignPermissionsByRole($roleMapping);

        RoleHelper::ensureAdminHasAllPermissions();
        echo "✓ Admin role verified - has ALL permissions\n";

        $users = DB::table('user')->whereNull('deleted_at')->get();

        foreach ($users as $user) {
            if (isset($roleMapping[$user->role_id])) {
                $userModel = User::find($user->user_id);
                if ($userModel) {
                    $userModel->assignRole($roleMapping[$user->role_id]);
                    echo "✓ User '{$user->username}' berhasil di-assign role\n";
                }
            }
        }

        echo "\n✓ Migrasi selesai!\n";
    }

    private function assignPermissionsByRole($roleMapping)
    {
        foreach ($roleMapping as $spatieRole) {
            $roleName = strtolower($spatieRole->name);

            if (\in_array($roleName, ['admin', 'superadmin', 'administrator'])) {
                $spatieRole->givePermissionTo(Permission::all());
                echo "✓ Role '{$spatieRole->name}' mendapat semua permissions (termasuk manage-master-data dan manage-users)\n";
                continue;
            }

            if (\in_array($roleName, ['ketua', 'manager', 'kepala'])) {
                $permissions = Permission::where('name', 'not like', '%-user')
                    ->where('name', '!=', 'manage-users')
                    ->where('name', '!=', 'view-dashboard-partner-performance')
                    ->get();
                $spatieRole->givePermissionTo($permissions);
                $spatieRole->givePermissionTo('manage-master-data');
                $spatieRole->givePermissionTo('view-dashboard-inventory-optimization');
                echo "✓ Role '{$spatieRole->name}' mendapat permissions manager (termasuk manage-master-data)\n";
                continue;
            }

            if (\in_array($roleName, ['karyawan', 'staff', 'pegawai'])) {
                $permissions = Permission::whereIn('name', [
                    'view-dashboard-partner-performance',
                    'view-barang', 'view-barang-toko', 'view-toko',
                    'view-customer', 'create-customer', 'edit-customer',
                    'view-pemesanan', 'create-pemesanan', 'edit-pemesanan',
                    'view-pengiriman', 'create-pengiriman', 'edit-pengiriman',
                    'view-retur', 'create-retur',
                    'view-follow-up', 'create-follow-up', 'edit-follow-up',
                    'view-eoq-setting',
                    'view-zscore-setting',
                ])->get();
                $spatieRole->givePermissionTo($permissions);
                echo "✓ Role '{$spatieRole->name}' mendapat permissions staff\n";
                continue;
            }

            $permissions = Permission::where('name', 'like', 'view-%')->get();
            $spatieRole->givePermissionTo($permissions);
            echo "✓ Role '{$spatieRole->name}' mendapat permissions view only\n";
        }
    }
}
