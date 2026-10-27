<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Converted from: app\Models\CommissionRulesModel.php (CodeIgniter)
     */
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();

            // Optional link to a parent company rule
            $table->foreignId('company_rule_id')
                  ->nullable()
                  ->constrained('company_rules')
                  ->nullOnDelete();

            // Insurance company this rule applies to
            $table->foreignId('company_id')
                  ->constrained('insurance_companies')
                  ->onDelete('cascade');

            // Optional: restrict rule to a specific policy type
            $table->unsignedBigInteger('policy_type_id')->nullable();
            $table->foreign('policy_type_id')
                  ->references('id')
                  ->on('insurance_policy_types')
                  ->nullOnDelete();

            // Optional: restrict rule to a specific agent
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->foreign('agent_id')
                  ->references('id')
                  ->on('agents')
                  ->nullOnDelete();

            $table->string('rule_name', 255);

            // 'percentage' or 'flat'
            $table->enum('commission_type', ['percentage', 'flat'])->default('percentage');

            // The percentage (e.g. 10.50) or flat amount (e.g. 500.00)
            $table->decimal('commission_value', 12, 2)->default(0);

            // Optional premium slab range
            $table->decimal('min_premium', 15, 2)->nullable();
            $table->decimal('max_premium', 15, 2)->nullable();

            // Validity window
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
