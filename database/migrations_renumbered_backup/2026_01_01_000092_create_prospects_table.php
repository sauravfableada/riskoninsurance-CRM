<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prospects')) {
            return;
        }

        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_no', 30)->nullable();
            $table->string('area')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('prospect');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
