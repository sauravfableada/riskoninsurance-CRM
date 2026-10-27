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
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('assigned_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('whatsapp')->nullable()->after('phone');
            $table->text('address')->nullable()->after('whatsapp');
            $table->string('image')->nullable()->after('address');
            $table->string('company_name')->nullable()->after('image');
            $table->string('sic_code')->nullable()->after('company_name');
            
            // Make travel fields optional
            $table->string('destination')->nullable()->change();
            $table->date('travel_start_date')->nullable()->change();
            $table->integer('travelers')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn(['assigned_user_id', 'whatsapp', 'address', 'image', 'company_name', 'sic_code']);
            
            $table->string('destination')->nullable(false)->change();
            $table->date('travel_start_date')->nullable(false)->change();
            $table->integer('travelers')->nullable(false)->change();
        });
    }
};
