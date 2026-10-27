<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call([
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
            SettingsSeeder::class,
            DealDemoSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            // AllModulesSeeder::class,
        ]);
    }
}
