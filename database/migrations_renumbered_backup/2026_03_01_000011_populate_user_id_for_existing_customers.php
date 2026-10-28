<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Assign all customers without a user_id to the admin user (ID 1)
        DB::table('customers')
            ->whereNull('user_id')
            ->update(['user_id' => 1]);
        
        // Do the same for other tables
        DB::table('leads')
            ->whereNull('user_id')
            ->update(['user_id' => 1]);
        
        DB::table('deals')
            ->whereNull('user_id')
            ->update(['user_id' => 1]);
        
        DB::table('follow_ups')
            ->whereNull('user_id')
            ->update(['user_id' => 1]);
        
        DB::table('bookings')
            ->whereNull('user_id')
            ->update(['user_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset user_id to NULL for all records
        DB::table('customers')->update(['user_id' => null]);
        DB::table('leads')->update(['user_id' => null]);
        DB::table('deals')->update(['user_id' => null]);
        DB::table('follow_ups')->update(['user_id' => null]);
        DB::table('bookings')->update(['user_id' => null]);
    }
};
