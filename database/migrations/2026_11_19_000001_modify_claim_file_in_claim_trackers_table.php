<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE claim_trackers MODIFY claim_file LONGTEXT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE claim_trackers MODIFY claim_file VARCHAR(255) NULL");
    }
};

