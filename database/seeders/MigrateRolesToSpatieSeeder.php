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
                'name'       => $oldRole->nama_role,
                'guard_name' => 'web'
            ]);

            $roleMapping[$oldRole->role_id] = $spatieRole;

            echo "✓ Role '{$oldRole->nama_role}' berhasil dibuat/ditemukan\n";
        }

        // ── Special / non-CRUD permissions ───────────────────────────────────
        $specialPermissions = [
            'manage-master-data'                    => 'Akses penuh Master Data',
            'manage-users'                          => 'Akses penuh User Management',
            'manage-notification-settings'          => 'Akses Pengaturan Notifikasi',
            'view-notifications'                    => 'Lihat Notifikasi',
            'view-barang'                           => 'Lihat Data Barang',
            'view-dashboard-inventory-optimization' => 'Akses Dashboard Inventory Optimization',
            'view-dashboard-partner-performance'    => 'Akses Dashboard Partner Performance',
            'view-partner-performance'              => 'Akses Analytics Partner Performance',
            'view-config-interval-kirim'            => 'Lihat Konfigurasi Interval Pengiriman',
            'update-config-interval-kirim'          => 'Update Konfigurasi Interval Pengiriman',
        ];

        foreach ($specialPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
            echo "✓ Permission '{$name}' berhasil dibuat\n";
        }

        // ── CRUD module permissions ───────────────────────────────────────────
        $modules = [
            'dashboard'      => 'Dashboard',
            'user'           => 'User Management',
            'barang'         => 'Barang',
            'barang-toko'    => 'Barang Toko',
            'toko'           => 'Toko',
            'customer'       => 'Customer',
            'pemesanan'      => 'Pemesanan',
            'pengiriman'     => 'Pengiriman',
            'retur'          => 'Retur',
            'follow-up'      => 'Follow Up Pelanggan',
            'eoq-setting'    => 'EOQ Setting',
            'zscore-setting' => 'Z-Score Setting',
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($modules as $module => $label) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$action}-{$module}",
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

    private function assignPermissionsByRole($roleMapping): void
    {
        foreach ($roleMapping as $spatieRole) {
            $roleName = strtolower($spatieRole->name);

            // ── Admin: akses penuh ke semua permission ────────────────────────
            if (\in_array($roleName, ['admin', 'superadmin', 'administrator'])) {
                $spatieRole->syncPermissions(Permission::all());
                echo "✓ Role '{$spatieRole->name}' mendapat semua permissions (Full Access)\n";
                continue;
            }

            // ── Ketua ─────────────────────────────────────────────────────────
            // Dashboard : Partner Performance
            // Menu      : barang, toko, customer, pengiriman, follow-up, retur,
            //             pemesanan, barang-toko
            // Tidak dapat: eoq-setting, zscore-setting, config-interval-kirim,
            //              manage-users, manage-master-data, inventory-optimization
            if (\in_array($roleName, ['ketua', 'manager', 'kepala'])) {
                $allowed = [
                    // Barang
                    'view-barang', 'create-barang', 'edit-barang', 'delete-barang',
                    // Toko
                    'view-toko', 'create-toko', 'edit-toko', 'delete-toko',
                    // Customer
                    'view-customer', 'create-customer', 'edit-customer', 'delete-customer',
                    // Pengiriman
                    'view-pengiriman', 'create-pengiriman', 'edit-pengiriman', 'delete-pengiriman',
                    // Follow Up
                    'view-follow-up', 'create-follow-up', 'edit-follow-up', 'delete-follow-up',
                    // Retur
                    'view-retur', 'create-retur', 'edit-retur', 'delete-retur',
                    // Pemesanan
                    'view-pemesanan', 'create-pemesanan', 'edit-pemesanan', 'delete-pemesanan',
                    // Barang Toko
                    'view-barang-toko', 'create-barang-toko', 'edit-barang-toko', 'delete-barang-toko',
                    // Dashboard Partner Performance
                    'view-dashboard-partner-performance',
                    // Dashboard (CRUD)
                    'view-dashboard', 'create-dashboard', 'edit-dashboard', 'delete-dashboard',
                ];

                $spatieRole->syncPermissions(
                    Permission::whereIn('name', $allowed)->get()
                );
                echo "✓ Role '{$spatieRole->name}' → akses menu operasional + Dashboard Partner Performance\n";
                continue;
            }

            // ── Karyawan ──────────────────────────────────────────────────────
            // Dashboard : Inventory Optimization
            // Menu      : barang, toko, customer, pengiriman, follow-up,
            //             zscore-setting, config-interval-kirim, eoq-setting,
            //             retur, pemesanan, barang-toko
            // Tidak dapat: view-partner-performance, dashboard partner performance,
            //              manage-users, manage-master-data
            if (\in_array($roleName, ['karyawan', 'staff', 'pegawai'])) {
                $allowed = [
                    // Barang
                    'view-barang', 'create-barang', 'edit-barang', 'delete-barang',
                    // Toko
                    'view-toko', 'create-toko', 'edit-toko', 'delete-toko',
                    // Customer
                    'view-customer', 'create-customer', 'edit-customer', 'delete-customer',
                    // Pengiriman
                    'view-pengiriman', 'create-pengiriman', 'edit-pengiriman', 'delete-pengiriman',
                    // Follow Up
                    'view-follow-up', 'create-follow-up', 'edit-follow-up', 'delete-follow-up',
                    // Z-Score Setting
                    'view-zscore-setting', 'create-zscore-setting', 'edit-zscore-setting', 'delete-zscore-setting',
                    // Konfigurasi Interval Kirim
                    'view-config-interval-kirim', 'update-config-interval-kirim',
                    // EOQ Setting
                    'view-eoq-setting', 'create-eoq-setting', 'edit-eoq-setting', 'delete-eoq-setting',
                    // Retur
                    'view-retur', 'create-retur', 'edit-retur', 'delete-retur',
                    // Pemesanan
                    'view-pemesanan', 'create-pemesanan', 'edit-pemesanan', 'delete-pemesanan',
                    // Barang Toko
                    'view-barang-toko', 'create-barang-toko', 'edit-barang-toko', 'delete-barang-toko',
                    // Dashboard Inventory Optimization
                    'view-dashboard-inventory-optimization',
                    // Dashboard (CRUD)
                    'view-dashboard', 'create-dashboard', 'edit-dashboard', 'delete-dashboard',
                ];

                $spatieRole->syncPermissions(
                    Permission::whereIn('name', $allowed)->get()
                );
                echo "✓ Role '{$spatieRole->name}' → akses menu operasional + Dashboard Inventory Optimization\n";
                continue;
            }

            // ── Default: view-only ────────────────────────────────────────────
            $permissions = Permission::where('name', 'like', 'view-%')->get();
            $spatieRole->syncPermissions($permissions);
            echo "✓ Role '{$spatieRole->name}' mendapat permissions view only\n";
        }
    }
}
