<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('assigned_user_id');
                $table->index('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'created_by')) {
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};
