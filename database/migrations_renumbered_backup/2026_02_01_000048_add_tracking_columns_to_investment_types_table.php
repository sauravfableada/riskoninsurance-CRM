<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_types', function (Blueprint $table) {
            if (!Schema::hasColumn('investment_types', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (!Schema::hasColumn('investment_types', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
            if (!Schema::hasColumn('investment_types', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
            }
            if (!Schema::hasColumn('investment_types', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('investment_types', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('investment_types', 'created_by')) $columns[] = 'created_by';
            if (Schema::hasColumn('investment_types', 'updated_by')) $columns[] = 'updated_by';
            if (Schema::hasColumn('investment_types', 'deleted_by')) $columns[] = 'deleted_by';
            if (Schema::hasColumn('investment_types', 'deleted_at')) $columns[] = 'deleted_at';
            
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
