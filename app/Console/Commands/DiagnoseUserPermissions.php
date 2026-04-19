<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DiagnoseUserPermissions extends Command
{
    protected $signature = 'user:diagnose {username=admin}';
    protected $description = 'Diagnose user roles and permissions';

    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("=== DIAGNOSING USER: {$username} ===\n");

        $user = User::where('username', $username)->first();

        if (!$user) {
            $this->error("User '{$username}' not found!");
            return 1;
        }

        $this->info("✓ User found");
        $this->line("  - user_id: {$user->user_id}");
        $this->line("  - username: {$user->username}");
        $this->line("  - role_id (old): {$user->role_id}\n");

        // Check Spatie roles
        $this->info("Spatie Roles:");
        $roles = $user->roles;
        if ($roles->count() > 0) {
            foreach ($roles as $role) {
                $this->line("  ✓ {$role->name}");
            }
        } else {
            $this->error("  ❌ NO ROLES ASSIGNED!");
            $this->warn("\n  Run: php artisan db:seed --class=MigrateRolesToSpatieSeeder --force\n");
        }

        // Check permissions
        $this->info("\nPermissions:");
        $permissions = $user->getAllPermissions();
        if ($permissions->count() > 0) {
            $this->line("  ✓ Has {$permissions->count()} permissions");
            $this->line("  Sample permissions:");
            foreach ($permissions->take(5) as $perm) {
                $this->line("    - {$perm->name}");
            }
        } else {
            $this->error("  ❌ NO PERMISSIONS!");
        }

        // Check specific permissions
        $this->info("\nChecking specific permissions:");
        $testPermissions = [
            'manage-users',
            'view-barang',
            'view-user',
            'view-dashboard',
        ];

        foreach ($testPermissions as $perm) {
            $has = $user->can($perm);
            if ($has) {
                $this->line("  ✓ {$perm}");
            } else {
                $this->error("  ❌ {$perm}");
            }
        }

        // Check middleware
        $this->info("\nMiddleware Check:");
        if ($user->roles->isEmpty()) {
            $this->error("  ❌ User has NO roles - middleware will block most routes!");
            $this->warn("  User can only access: dashboard, logout, profile");
        } else {
            $this->line("  ✓ User has roles - middleware will allow access");
        }

        $this->info("\n=== DIAGNOSIS COMPLETE ===");
        
        return 0;
    }
}
