<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to match new enum values
        DB::table('leads')->where('status', 'open')->update(['status' => 'new']);
        DB::table('leads')->where('status', 'in_progress')->update(['status' => 'working']);

        Schema::table('leads', function (Blueprint $table) {
            $table->string('status')->default('new')->change();
        });

        DB::statement("ALTER TABLE leads MODIFY COLUMN status ENUM('new', 'qualified', 'working', 'ready_to_close', 'won', 'lost') DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE leads MODIFY COLUMN status ENUM('open', 'in_progress', 'won', 'lost') DEFAULT 'open'");
    }
};
