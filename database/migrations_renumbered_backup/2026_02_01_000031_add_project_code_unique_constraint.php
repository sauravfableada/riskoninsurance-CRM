<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'project_code')) {
                $table->string('project_code')->nullable()->after('id');
            }
        });

        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'projects')
            ->where('index_name', 'projects_project_code_unique')
            ->exists();

        if (! $indexExists) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unique('project_code', 'projects_project_code_unique');
            });
        }
    }

    public function down(): void
    {
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'projects')
            ->where('index_name', 'projects_project_code_unique')
            ->exists();

        if ($indexExists) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropUnique('projects_project_code_unique');
            });
        }
    }
};
