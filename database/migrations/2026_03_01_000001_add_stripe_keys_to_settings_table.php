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
        $settings = [
            ['key' => 'stripe_key',    'value' => null, 'group' => 'integrations', 'type' => 'password'],
            ['key' => 'stripe_secret', 'value' => null, 'group' => 'integrations', 'type' => 'password'],
        ];

        foreach ($settings as $setting) {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->whereIn('key', ['stripe_key', 'stripe_secret'])->delete();
    }
};
