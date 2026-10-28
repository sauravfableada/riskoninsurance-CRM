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
        Schema::create('endorsement_policy_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('endorsement_id');
            $table->unsignedBigInteger('policy_id');
            $table->string('policy_type')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_policy_links');
    }
};
