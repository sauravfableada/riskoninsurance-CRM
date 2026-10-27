<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'stage_id')) {
                $table->unsignedBigInteger('stage_id')->nullable()->after('amount');
                $table->index('stage_id');
            }
            if (!Schema::hasColumn('deals', 'probability')) {
                $table->decimal('probability', 5, 2)->nullable()->after('stage_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'probability')) {
                $table->dropColumn('probability');
            }
            if (Schema::hasColumn('deals', 'stage_id')) {
                $table->dropIndex(['stage_id']);
                $table->dropColumn('stage_id');
            }
        });
    }
};
