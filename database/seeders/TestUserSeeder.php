<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test admin user
        User::create([
            'username' => 'admin',
            'password' => 'admin123', // Will be hashed by model boot method
            'firstname' => 'Admin',
            'lastname' => 'System',
            'email' => 'admin@zafasys.com',
            'role_id' => 1,
            'telp' => '081234567890',
            'alamat' => 'Jl. Test No. 123',
        ]);

        echo "Test user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    }
}
