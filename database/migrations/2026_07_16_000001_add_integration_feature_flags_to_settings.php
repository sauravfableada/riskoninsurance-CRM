<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'whatsapp_integration_enabled',
            'social_integrations_enabled',
            'google_connection_enabled',
        ] as $key) {
            Setting::withTrashed()->updateOrCreate(
                ['key' => $key],
                ['value' => '0', 'group' => 'integrations', 'type' => 'boolean', 'deleted_at' => null]
            );
        }
    }

    public function down(): void
    {
        Setting::withTrashed()->whereIn('key', [
            'whatsapp_integration_enabled',
            'social_integrations_enabled',
            'google_connection_enabled',
        ])->forceDelete();
    }
};
