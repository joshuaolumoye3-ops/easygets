<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmins@example.com',
            'password' => Hash::make('SuperAdmin123!'),
            'role' => 'super_admin',
            'phone' => '08011111111',
            'status' => 'active',
        ]);

        Admin::create([
            'name' => 'Regular Admin',
            'email' => 'admins@example.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
            'phone' => '08022222222',
            'status' => 'active',
        ]);
    }
}
