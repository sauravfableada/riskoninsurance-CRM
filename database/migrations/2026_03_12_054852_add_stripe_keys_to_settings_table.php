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
            \App\Models\Setting::firstOrCreate(
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
        \App\Models\Setting::whereIn('key', ['stripe_key', 'stripe_secret'])->delete();
    }
};
