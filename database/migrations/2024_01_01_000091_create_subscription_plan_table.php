<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plan', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable();
            $table->string('staff_limit')->nullable();
            $table->timestamps();
        });

        DB::table('subscription_plan')->insert([
            [
                'id' => 1,
                'name' => 'Basic Plan',
                'staff_limit' => '15',
                'created_at' => '2025-01-08 11:53:18',
                'updated_at' => '2025-06-17 12:40:18',
            ],
            [
                'id' => 2,
                'name' => 'Premium Plan',
                'staff_limit' => '25',
                'created_at' => '2025-01-08 11:53:18',
                'updated_at' => '2026-05-13 07:19:14',
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan');
    }
};
