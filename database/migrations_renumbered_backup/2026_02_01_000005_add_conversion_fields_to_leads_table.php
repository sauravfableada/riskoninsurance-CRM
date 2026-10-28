<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('is_converted')->default(false)->after('lead_stage_id');
            $table->unsignedBigInteger('converted_customer_id')->nullable()->after('is_converted');

            $table->index(['is_converted', 'converted_customer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['is_converted', 'converted_customer_id']);
            $table->dropColumn(['is_converted', 'converted_customer_id']);
        });
    }
};
