<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('area')) {
            Schema::create('area', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('city_id');
                $table->string('area_name', 255);
                $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('modified_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('area');
    }
};
