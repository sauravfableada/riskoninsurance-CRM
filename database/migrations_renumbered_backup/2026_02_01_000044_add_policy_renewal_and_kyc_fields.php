<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            if (!Schema::hasColumn('policies', 'kyc_id_type')) {
                $table->string('kyc_id_type')->nullable()->after('policy_pdf');
            }

            if (!Schema::hasColumn('policies', 'kyc_id_number')) {
                $table->string('kyc_id_number')->nullable()->after('kyc_id_type');
            }

            if (!Schema::hasColumn('policies', 'kyc_notes')) {
                $table->text('kyc_notes')->nullable()->after('kyc_id_number');
            }

            if (!Schema::hasColumn('policies', 'renewal_of_policy_id')) {
                $table->unsignedBigInteger('renewal_of_policy_id')->nullable()->after('kyc_notes');
                $table->foreign('renewal_of_policy_id')->references('id')->on('policies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            if (Schema::hasColumn('policies', 'renewal_of_policy_id')) {
                $table->dropForeign(['renewal_of_policy_id']);
                $table->dropColumn('renewal_of_policy_id');
            }

            foreach (['kyc_notes', 'kyc_id_number', 'kyc_id_type'] as $column) {
                if (Schema::hasColumn('policies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
