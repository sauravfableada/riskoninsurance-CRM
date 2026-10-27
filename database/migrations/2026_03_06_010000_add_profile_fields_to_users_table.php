<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('job_title')->nullable()->after('phone');
            $table->string('company')->nullable()->after('job_title');
            $table->string('city')->nullable()->after('company');
            $table->string('country')->nullable()->after('city');
            $table->string('avatar_path')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'job_title',
                'company',
                'city',
                'country',
                'avatar_path',
            ]);
        });
    }
};

