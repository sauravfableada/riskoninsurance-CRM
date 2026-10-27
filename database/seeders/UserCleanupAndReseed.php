<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserCleanupAndReseed extends Seeder
{
    public function run(): void
    {
        // 1. Identify "Test" users to delete
        $this->command->info("Cleaning up 100k test users...");
        
        $query = User::where('name', 'like', 'User %')
            ->orWhere('email', 'like', 'user%@example.com');
            
        $totalToDelete = $query->count();
            
        if ($totalToDelete > 0) {
            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
            
            $this->command->info("Deleting $totalToDelete users in chunks...");
            
            // We use a while loop because IDs might change or we're deleting from the same table we're querying
            while ($query->exists()) {
                $query->limit(5000)->delete();
                $this->command->info("Deleted a chunk of 5000 users...");
            }
            
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }
            
        $this->command->info("Deleted $totalToDelete test users.");

        // 2. Seed 30 new users
        $this->command->info("Seeding 30 new users with roles...");
        
        $roles = ['admin', 'manager', 'staff'];
        $password = Hash::make('password');
        
        for ($i = 0; $i < 30; $i++) {
            $user = User::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => $password,
                'phone' => fake()->phoneNumber(),
                'job_title' => fake()->jobTitle(),
                'company' => 'Tour CRM',
                'city' => fake()->city(),
                'country' => 'India',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Assign roles: 5 Admins, 10 Managers, 15 Staff
            if ($i < 5) {
                $user->assignRole('admin');
            } elseif ($i < 15) {
                $user->assignRole('manager');
            } else {
                $user->assignRole('staff');
            }
        }
        
        $this->command->info("Seeded 30 users successfully.");
    }
}
