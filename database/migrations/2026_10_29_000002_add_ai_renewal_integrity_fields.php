<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            if (!Schema::hasColumn('policies', 'renewal_of_policy_id')) $table->unsignedBigInteger('renewal_of_policy_id')->nullable()->index();
            if (!Schema::hasColumn('policies', 'renewal_document_hash')) $table->string('renewal_document_hash', 64)->nullable()->unique();
            if (!Schema::hasColumn('policies', 'renewal_review')) $table->json('renewal_review')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropUnique(['renewal_document_hash']);
            $table->dropColumn(['renewal_document_hash', 'renewal_review']);
        });
    }
};
