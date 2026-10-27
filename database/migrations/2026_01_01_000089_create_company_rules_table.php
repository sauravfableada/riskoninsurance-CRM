<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Converted from: app\Models\CompanyRulesModel.php (CodeIgniter)
     */
    public function up(): void
    {
        Schema::create('company_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                  ->constrained('insurance_companies')
                  ->onDelete('cascade');

            $table->string('rule_name', 255);
            $table->text('rule_description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_rules');
    }
};
