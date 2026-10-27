<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LargeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Batch size for better performance
        $batchSize = 2500;
        $totalRecords = 100000;
        $password = Hash::make('password');

        $this->command->info("Seeding $totalRecords users in batches of $batchSize...");

        for ($i = 0; $i < ($totalRecords / $batchSize); $i++) {
            $users = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $users[] = [
                    'name' => "User " . (($i * $batchSize) + $j + 1),
                    'email' => "user" . (($i * $batchSize) + $j + 1) . "@example.com",
                    'email_verified_at' => now(),
                    'password' => $password,
                    'phone' => "98765432" . (($i * $batchSize) + $j + 1),
                    'job_title' => "Staff Member",
                    'company' => "Tour CRM",
                    'city' => "Ahmedabad",
                    'country' => "India",
                    'remember_token' => Str::random(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('users')->insert($users);
            $this->command->info("Seeded batch " . ($i + 1));
        }
    }
}
