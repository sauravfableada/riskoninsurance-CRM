<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_member_designations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        if (Schema::hasColumn('users', 'designation')) {
            DB::table('users')->whereNotNull('designation')->where('designation', '!=', '')
                ->select('designation')->distinct()->orderBy('designation')->get()
                ->each(function ($row) {
                    DB::table('team_member_designations')->insertOrIgnore([
                        'name' => trim($row->designation),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_designations');
    }
};
