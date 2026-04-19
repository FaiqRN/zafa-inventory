<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Helpers\RoleHelper;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixAdminAccess extends Command
{
    protected $signature = 'user:fix-admin {username=admin}';
    protected $description = 'Fix admin user access by ensuring role and permissions';

    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("=== FIXING ACCESS FOR: {$username} ===\n");

        $user = User::where('username', $username)->first();

        if (!$user) {
            $this->error("User '{$username}' not found!");
            return 1;
        }

        $this->line("Found user: {$user->username}");

        // Check if Admin role exists
        $adminRole = Role::where('name', 'Admin')->first();

        if (!$adminRole) {
            $this->warn("Admin role not found. Creating...");
            $adminRole = Role::create([
                'name' => 'Admin',
                'guard_name' => 'web'
            ]);
            $this->info("✓ Admin role created");
        }

        // Ensure Admin has all permissions
        $this->line("\nEnsuring Admin role has all permissions...");
        RoleHelper::ensureAdminHasAllPermissions();
        $permCount = $adminRole->fresh()->permissions->count();
        $this->info("✓ Admin role has {$permCount} permissions");

        // Assign Admin role to user
        $this->line("\nAssigning Admin role to user...");
        $user->syncRoles(['Admin']);
        $this->info("✓ User now has Admin role");

        // Verify
        $this->info("\n=== VERIFICATION ===");
        $user = User::where('username', $username)->first();
        $this->line("Roles: " . $user->roles->pluck('name')->implode(', '));
        $this->line("Permissions: {$user->getAllPermissions()->count()}");
        $this->line("Can manage users: " . ($user->can('manage-users') ? 'YES' : 'NO'));
        $this->line("Can view barang: " . ($user->can('view-barang') ? 'YES' : 'NO'));

        $this->info("\n✓ ACCESS FIXED!");
        $this->warn("\nPlease:");
        $this->warn("1. Clear browser cache");
        $this->warn("2. Logout and login again");
        $this->warn("3. All menus should now appear");

        return 0;
    }
}
