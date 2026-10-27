<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_stages') && !Schema::hasTable('stages')) {
            Schema::rename('lead_stages', 'stages');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stages') && !Schema::hasTable('lead_stages')) {
            Schema::rename('stages', 'lead_stages');
        }
    }
};
