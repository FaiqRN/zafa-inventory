<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CREATING ROLE AND USER ===\n\n";

try {
    // Create role first
    $role = App\Models\Role::create([
        'nama_role' => 'Administrator',
        'deskripsi' => 'Administrator dengan akses penuh ke semua fitur',
    ]);
    
    echo "✅ Role created successfully!\n";
    echo "   - ID: {$role->role_id}\n";
    echo "   - Name: {$role->nama_role}\n\n";
    
    // Create admin user
    $user = App\Models\User::create([
        'role_id' => $role->role_id,
        'username' => 'admin',
        'password' => 'admin123', // Will be hashed automatically by model
        'firstname' => 'Admin',
        'lastname' => 'System',
        'email' => 'admin@zafasys.com',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '1990-01-01',
        'alamat' => 'Jl. Admin No. 123, Jakarta',
        'telp' => '081234567890',
    ]);
    
    echo "✅ User created successfully!\n";
    echo "   - ID: {$user->user_id}\n";
    echo "   - Username: {$user->username}\n";
    echo "   - Email: {$user->email}\n\n";
    
    echo "=== LOGIN CREDENTIALS ===\n";
    echo "Username: admin\n";
    echo "Password: admin123\n\n";
    
    echo "✅ You can now login to the application!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END ===\n";
