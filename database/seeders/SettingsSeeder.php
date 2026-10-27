<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // =====================
            // GENERAL
            // =====================
            ['key' => 'app_name',       'value' => 'Tour CRM',   'group' => 'general', 'type' => 'string'],
            ['key' => 'app_logo',       'value' => null,         'group' => 'general', 'type' => 'string'],
            ['key' => 'currency_code',  'value' => 'INR',        'group' => 'general', 'type' => 'string'],
            ['key' => 'currency_symbol','value' => '₹',          'group' => 'general', 'type' => 'string'],
            ['key' => 'timezone',       'value' => 'Asia/Kolkata','group' => 'general','type' => 'string'],
            ['key' => 'date_format',    'value' => 'd M Y',      'group' => 'general', 'type' => 'string'],

            // =====================
            // SMTP / EMAIL
            // =====================
            ['key' => 'mail_mailer',      'value' => 'smtp',         'group' => 'smtp', 'type' => 'string'],
            ['key' => 'mail_host',        'value' => 'smtp.gmail.com','group' => 'smtp', 'type' => 'string'],
            ['key' => 'mail_port',        'value' => '587',           'group' => 'smtp', 'type' => 'integer'],
            ['key' => 'mail_encryption',  'value' => 'tls',           'group' => 'smtp', 'type' => 'string'],
            ['key' => 'mail_username',    'value' => null,            'group' => 'smtp', 'type' => 'string'],
            ['key' => 'mail_password',    'value' => null,            'group' => 'smtp', 'type' => 'password'],
            ['key' => 'mail_from_address','value' => null,            'group' => 'smtp', 'type' => 'string'],
            ['key' => 'mail_from_name',   'value' => 'Tour CRM',     'group' => 'smtp', 'type' => 'string'],

            // =====================
            // API INTEGRATIONS
            // =====================
            ['key' => 'whatsapp_api_key',        'value' => null, 'group' => 'integrations', 'type' => 'password'],
            ['key' => 'whatsapp_phone_number_id', 'value' => null, 'group' => 'integrations', 'type' => 'string'],
            ['key' => 'whatsapp_business_id',     'value' => null, 'group' => 'integrations', 'type' => 'string'],

            ['key' => 'google_maps_api_key',    'value' => null, 'group' => 'integrations', 'type' => 'password'],
            ['key' => 'google_analytics_id',    'value' => null, 'group' => 'integrations', 'type' => 'string'],

            ['key' => 'payment_gateway',         'value' => 'razorpay', 'group' => 'integrations', 'type' => 'string'],
            ['key' => 'razorpay_key_id',         'value' => null,       'group' => 'integrations', 'type' => 'string'],
            ['key' => 'razorpay_key_secret',     'value' => null,       'group' => 'integrations', 'type' => 'password'],
            ['key' => 'stripe_mode',             'value' => 'test',     'group' => 'integrations', 'type' => 'string'],
            ['key' => 'stripe_key',              'value' => null,       'group' => 'integrations', 'type' => 'password'],
            ['key' => 'stripe_secret',           'value' => null,       'group' => 'integrations', 'type' => 'password'],

            ['key' => 'sms_provider',            'value' => 'twilio',   'group' => 'integrations', 'type' => 'string'],
            ['key' => 'twilio_account_sid',      'value' => null,       'group' => 'integrations', 'type' => 'string'],
            ['key' => 'twilio_auth_token',       'value' => null,       'group' => 'integrations', 'type' => 'password'],
            ['key' => 'twilio_from_number',      'value' => null,       'group' => 'integrations', 'type' => 'string'],

            // =====================
            // NOTIFICATIONS
            // =====================
            ['key' => 'notify_new_lead',         'value' => 'true',  'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_followup_due',     'value' => 'true',  'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_booking_confirmed','value' => 'true',  'group' => 'notifications', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded successfully!');
        $this->command->table(
            ['Group', 'Count'],
            collect($settings)->groupBy('group')->map(fn($g, $k) => [$k, count($g)])->values()->toArray()
        );
    }
}
