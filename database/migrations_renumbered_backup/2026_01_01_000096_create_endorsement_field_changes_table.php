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
        Schema::create('endorsement_field_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('endorsement_id');
            $table->string('field_key')->nullable();
            $table->string('field_label')->nullable();
            $table->text('existing_value')->nullable();
            $table->string('transaction_type')->nullable();
            $table->text('new_value')->nullable();
            $table->text('remarks')->nullable();
            $table->text('endorse_effect')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_field_changes');
    }
};
