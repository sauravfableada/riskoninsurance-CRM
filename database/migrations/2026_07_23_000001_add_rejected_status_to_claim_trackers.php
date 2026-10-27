<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE claim_trackers MODIFY claim_status ENUM('Processed', 'Query', 'Closed', 'Rejected') NOT NULL DEFAULT 'Processed'");
    }

    public function down(): void
    {
        DB::table('claim_trackers')->where('claim_status', 'Rejected')->update(['claim_status' => 'Query']);
        DB::statement("ALTER TABLE claim_trackers MODIFY claim_status ENUM('Processed', 'Query', 'Closed') NOT NULL DEFAULT 'Processed'");
    }
};
