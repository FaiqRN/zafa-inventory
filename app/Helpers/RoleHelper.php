<?php

namespace App\Helpers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleHelper
{

    public static function ensureAdminHasAllPermissions(): void
    {
        $adminRoles = Role::whereIn('name', ['Admin', 'admin', 'Superadmin', 'superadmin', 'Administrator', 'administrator'])->get();
        
        foreach ($adminRoles as $adminRole) {
            $adminRole->syncPermissions(Permission::all());
        }
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public static function isAdminRole(string $roleName): bool
    {
        return \in_array(\strtolower($roleName), ['admin', 'superadmin', 'administrator']);
    }

    public static function getNonAdminRoles()
    {
        return Role::whereNotIn('name', ['Admin', 'admin', 'Superadmin', 'superadmin', 'Administrator', 'administrator'])->get();
    }

    public static function syncRolePermissions(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        
        if (!$role) {
            return;
        }

        $roleLower = \strtolower($roleName);

        if (self::isAdminRole($roleLower)) {
            $role->syncPermissions(Permission::all());
            return;
        }

        if (\in_array($roleLower, ['ketua', 'manager', 'kepala'])) {
            $permissions = Permission::where('name', 'not like', '%-user')
                ->where('name', '!=', 'manage-users')
                ->get();
            $role->syncPermissions($permissions);
            $role->givePermissionTo('manage-master-data');
            return;
        }

        if (\in_array($roleLower, ['karyawan', 'staff', 'pegawai'])) {
            $permissions = Permission::whereIn('name', [
                'view-dashboard',
                'view-barang', 'view-barang-toko', 'view-toko',
                'view-customer', 'create-customer', 'edit-customer',
                'view-pemesanan', 'create-pemesanan', 'edit-pemesanan',
                'view-pengiriman', 'create-pengiriman', 'edit-pengiriman',
                'view-retur', 'create-retur',
                'view-follow-up', 'create-follow-up', 'edit-follow-up',
                'view-eoq-setting',
            ])->get();
            $role->syncPermissions($permissions);
            return;
        }

        $permissions = Permission::where('name', 'like', 'view-%')->get();
        $role->syncPermissions($permissions);
    }
}
