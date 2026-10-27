<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['leads', 'customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('country_code', 5)->default('+91')->after('phone');
                $table->string('whatsapp_country_code', 5)->default('+91')->after('whatsapp');
            });
        }
    }

    public function down(): void
    {
        foreach (['leads', 'customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['country_code', 'whatsapp_country_code']);
            });
        }
    }
};
