<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invested_party')) {
            Schema::create('invested_party', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('city', 255)->nullable();
                $table->text('remark')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('modified_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invested_party');
    }
};
