<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claim_trackers', function (Blueprint $table) {
            $table->foreignId('family_group_id')->nullable()->after('id')->constrained('family_groups')->nullOnDelete();
            $table->foreignId('proposer_id')->nullable()->after('family_group_id')->constrained('customers')->nullOnDelete();
            $table->foreignId('insured_member_id')->nullable()->after('policy_id')->constrained('family_group_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('claim_trackers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('insured_member_id');
            $table->dropConstrainedForeignId('proposer_id');
            $table->dropConstrainedForeignId('family_group_id');
        });
    }
};
