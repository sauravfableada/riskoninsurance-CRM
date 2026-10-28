<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('phone');
            $table->text('address')->nullable()->after('whatsapp');
            $table->string('company_name')->nullable()->after('address');
            $table->string('website')->nullable()->after('company_name');
            $table->string('tax_number')->nullable()->after('website');
            $table->string('image')->nullable()->after('tax_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'address', 'company_name', 'website', 'tax_number', 'image']);
        });
    }
};
