<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('post_office')) {
            Schema::create('post_office', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
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
        Schema::dropIfExists('post_office');
    }
};
