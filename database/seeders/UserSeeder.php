<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('12345678'),
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('super-admin');

        // Create Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        $this->command->info('Super Admin and Admin users created successfully!');
    }
}
