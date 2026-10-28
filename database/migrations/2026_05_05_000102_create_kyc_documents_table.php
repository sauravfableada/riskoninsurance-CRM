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
        if (!Schema::hasTable('kyc_documents')) {
            Schema::create('kyc_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('policy_id')->nullable()->index();
                $table->text('aadhaar_card')->nullable();
                $table->text('pan_card')->nullable();
                $table->text('driving_licence')->nullable();
                $table->text('addr_aadhaar')->nullable();
                $table->text('addr_driving_licence')->nullable();
                $table->text('photo')->nullable();
                $table->text('signature')->nullable();
                $table->string('kyc_status')->default('pending');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};

