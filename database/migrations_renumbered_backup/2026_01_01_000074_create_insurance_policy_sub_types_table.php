<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policy_sub_types', function (Blueprint $table) {
            $table->id();

            // Foreign Key
            $table->unsignedBigInteger('insurance_policy_types_id');

            $table->string('name');

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign Key Constraint
            $table->foreign('insurance_policy_types_id')
                  ->references('id')
                  ->on('insurance_policy_types')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policy_sub_types');
    }
};
