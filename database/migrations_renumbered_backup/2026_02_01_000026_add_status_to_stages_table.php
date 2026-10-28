<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            if (!Schema::hasColumn('stages', 'status')) {
                $table->enum('status', ['in_progress', 'paused', 'completed'])
                    ->default('in_progress')
                    ->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            if (Schema::hasColumn('stages', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
