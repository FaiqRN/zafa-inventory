<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING DATABASE ===\n\n";

// Check users
$users = App\Models\User::all(['user_id', 'username', 'email']);
echo "Total users in database: " . $users->count() . "\n\n";

if ($users->count() > 0) {
    echo "Users:\n";
    foreach ($users as $user) {
        echo "  - ID: {$user->user_id}, Username: {$user->username}, Email: {$user->email}\n";
    }
} else {
    echo "⚠️  NO USERS FOUND IN DATABASE!\n";
    echo "You need to create a user first.\n\n";
    
    // Check roles
    $roles = App\Models\Role::all(['role_id', 'nama_role']);
    echo "\nTotal roles in database: " . $roles->count() . "\n";
    
    if ($roles->count() > 0) {
        echo "Roles:\n";
        foreach ($roles as $role) {
            echo "  - ID: {$role->role_id}, Name: {$role->nama_role}\n";
        }
    } else {
        echo "⚠️  NO ROLES FOUND IN DATABASE!\n";
        echo "You need to create a role first before creating a user.\n";
    }
}

echo "\n=== END ===\n";
