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
        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['key' => 'stripe_mode'],
            ['key' => 'stripe_mode', 'value' => 'test', 'group' => 'integrations', 'type' => 'string']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->where('key', 'stripe_mode')->delete();
    }
};
