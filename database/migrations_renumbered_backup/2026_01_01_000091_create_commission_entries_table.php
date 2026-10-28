<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Converted from: app\Models\CommissionEntriesModel.php (CodeIgniter)
     */
    public function up(): void
    {
        Schema::create('commission_entries', function (Blueprint $table) {
            $table->id();

            // Which commission rule generated this entry
            $table->foreignId('commission_rule_id')
                  ->constrained('commission_rules')
                  ->onDelete('cascade');

            // The policy this commission relates to (nullable – entry may be pre-policy)
            $table->unsignedBigInteger('policy_id')->nullable();
            $table->foreign('policy_id')
                  ->references('id')
                  ->on('policies')
                  ->nullOnDelete();

            // The agent who earns this commission
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->foreign('agent_id')
                  ->references('id')
                  ->on('agents')
                  ->nullOnDelete();

            // Premium on which commission is computed
            $table->decimal('premium_amount', 15, 2)->default(0);

            // Computed commission amount (in currency)
            $table->decimal('commission_amount', 15, 2)->default(0);

            // Snapshot of the percentage at the time of entry (useful if rule changes later)
            $table->decimal('commission_percentage', 8, 2)->nullable();

            // 'pending' | 'paid' | 'cancelled'
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');

            $table->text('remarks')->nullable();
            $table->date('entry_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_entries');
    }
};
