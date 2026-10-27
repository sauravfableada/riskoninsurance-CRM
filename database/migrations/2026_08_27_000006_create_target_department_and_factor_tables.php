<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('target_factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_department_id')->constrained('target_departments')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['target_department_id', 'name']);
        });

        $now = now();
        DB::table('target_departments')->insert([
            ['id' => 1, 'name' => 'General Insurance Target', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Investment Target', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Life Insurance Target', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('target_factors')->insert([
            ['id' => 1, 'target_department_id' => 1, 'name' => 'Gen. Ins. New Business', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'target_department_id' => 3, 'name' => 'Life Ins. Ren. Business', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'target_department_id' => 3, 'name' => 'Life Ins. New Business', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'target_department_id' => 1, 'name' => 'Gen. Ins. Ren. Business', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'target_department_id' => 2, 'name' => 'MF-SIP New Business', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'target_department_id' => 2, 'name' => 'MF-Lumpsum New Business', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('target_factors');
        Schema::dropIfExists('target_departments');
    }
};
