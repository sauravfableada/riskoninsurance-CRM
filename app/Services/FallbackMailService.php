<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FallbackMailService
{
    public const SENSITIVE_SETTING_KEYS = [
        'mail_password',
        'mail_fallback_password',
    ];

    public function getMailSettings(): array
    {
        if (!Schema::hasTable('settings')) {
            return [];
        }

        $rows = Setting::query()
            ->where(function ($q) {
                $q->where('key', 'like', 'mail_%');
            })
            ->pluck('value', 'key')
            ->toArray();

        if (!empty($rows['mail_fallback_password'])) {
            $rows['mail_fallback_password'] = $this->decryptSecret((string) $rows['mail_fallback_password']);
        }

        return $rows;
    }

    public function encryptSecret(string $plain): string
    {
        try {
            return Crypt::encryptString($plain);
        } catch (Throwable) {
            return $plain;
        }
    }

    public function decryptSecret(string $stored): string
    {
        if ($stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            // Support legacy/plain values if not encrypted.
            return $stored;
        }
    }

    public function configurePrimaryMailer(?array $settings = null): void
    {
        $settings ??= $this->getMailSettings();

        if (!empty($settings['mail_host'])) {
            Config::set('mail.mailers.smtp.host', $settings['mail_host']);
            Config::set('mail.mailers.smtp.port', !empty($settings['mail_port']) ? (int) $settings['mail_port'] : 587);
            Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?? null);
            Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?? null);
            Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption'] ?? null);

            if (!empty($settings['mail_from_address'])) {
                Config::set('mail.from.address', $settings['mail_from_address']);
            } elseif (!empty($settings['mail_username'])) {
                Config::set('mail.from.address', $settings['mail_username']);
            }

            if (!empty($settings['mail_from_name'])) {
                Config::set('mail.from.name', $settings['mail_from_name']);
            }

            try {
                Mail::purge('smtp');
            } catch (Throwable) {
                // Ignore if mailer hasn't been resolved yet.
            }
        }
    }

    public function configureFallbackMailer(?array $settings = null): array
    {
        $settings ??= $this->getMailSettings();

        $host = $this->firstNonEmpty($settings['mail_fallback_host'] ?? null, $settings['mail_host'] ?? null, Config::get('mail.mailers.smtp.host'));
        $port = $this->firstNonEmpty($settings['mail_fallback_port'] ?? null, $settings['mail_port'] ?? null, Config::get('mail.mailers.smtp.port', 587));
        $username = $this->firstNonEmpty($settings['mail_fallback_username'] ?? null, $settings['mail_fallback_email'] ?? null, $settings['mail_username'] ?? null, Config::get('mail.mailers.smtp.username'));
        $password = $this->firstNonEmpty($settings['mail_fallback_password'] ?? null, $settings['mail_password'] ?? null, Config::get('mail.mailers.smtp.password'));
        $encryption = array_key_exists('mail_fallback_encryption', $settings) && $settings['mail_fallback_encryption'] !== null
            ? $settings['mail_fallback_encryption']
            : ($settings['mail_encryption'] ?? Config::get('mail.mailers.smtp.encryption'));

        $fromAddress = $this->firstNonEmpty(
            $settings['mail_fallback_from_address'] ?? null,
            $settings['mail_fallback_email'] ?? null,
            $username,
            Config::get('mail.from.address')
        );
        $fromName = $this->firstNonEmpty(
            $settings['mail_fallback_from_name'] ?? null,
            $settings['mail_from_name'] ?? null,
            Config::get('mail.from.name')
        );

        Config::set('mail.mailers.smtp_fallback.transport', 'smtp');
        Config::set('mail.mailers.smtp_fallback.host', $host);
        Config::set('mail.mailers.smtp_fallback.port', $port !== null ? (int) $port : 587);
        Config::set('mail.mailers.smtp_fallback.username', $username);
        Config::set('mail.mailers.smtp_fallback.password', $password);
        Config::set('mail.mailers.smtp_fallback.encryption', $encryption);

        try {
            Mail::purge('smtp_fallback');
        } catch (Throwable) {
            // Ignore if mailer hasn't been resolved yet.
        }

        return [
            'host' => $host,
            'port' => $port,
            'from_address' => $fromAddress,
            'from_name' => $fromName,
        ];
    }

    public function isFallbackEnabled(?array $settings = null): bool
    {
        $settings ??= $this->getMailSettings();

        $enabled = ($settings['mail_fallback_enabled'] ?? '0') === '1'
            || ($settings['mail_fallback_enabled'] ?? false) === true;

        if (!$enabled) {
            return false;
        }

        $hasBackupAddress = !empty($settings['mail_fallback_email']) || !empty($settings['mail_fallback_from_address']);
        $hasBackupHost = !empty($settings['mail_fallback_host']) || !empty($settings['mail_host']) || !empty(Config::get('mail.mailers.smtp.host'));

        return $hasBackupAddress && $hasBackupHost;
    }

    /**
     * Send an email via primary SMTP first, automatically falling back to backup SMTP on failure.
     *
     * @param callable(string $mailerName, ?string $fromAddress, ?string $fromName): void $sendCallback
     * @param array<string, mixed> $context
     */
    public function send(callable $sendCallback, array $context = []): bool
    {
        $settings = $this->getMailSettings();
        $this->configurePrimaryMailer($settings);

        $idempotencyKey = !empty($context['idempotency_key'])
            ? 'mail_sent_' . sha1((string) $context['idempotency_key'])
            : null;

        if ($idempotencyKey && Cache::has($idempotencyKey)) {
            Log::info('Skipping duplicate email delivery for idempotency key.', [
                'context' => $this->safeContext($context),
            ]);
            return true;
        }

        $primaryMailer = (string) ($context['primary_mailer'] ?? Config::get('mail.default', 'smtp'));
        $primaryFromAddress = Config::get('mail.from.address');
        $primaryFromName = Config::get('mail.from.name');

        try {
            $sendCallback($primaryMailer, $primaryFromAddress, $primaryFromName);

            if ($idempotencyKey) {
                Cache::put($idempotencyKey, true, now()->addMinutes(15));
            }

            return true;
        } catch (Throwable $primaryException) {
            Log::warning('Primary SMTP email delivery failed.', [
                'mailer' => $primaryMailer,
                'host' => Config::get("mail.mailers.{$primaryMailer}.host"),
                'error' => $primaryException->getMessage(),
                'context' => $this->safeContext($context),
            ]);

            if (!$this->isFallbackEnabled($settings)) {
                Log::info('Fallback email delivery not attempted because fallback is disabled or not configured.', [
                    'context' => $this->safeContext($context),
                ]);
                return false;
            }

            $fallbackMeta = $this->configureFallbackMailer($settings);

            Log::info('Attempting fallback SMTP email delivery.', [
                'mailer' => 'smtp_fallback',
                'host' => $fallbackMeta['host'],
                'from_address' => $fallbackMeta['from_address'],
                'context' => $this->safeContext($context),
            ]);

            try {
                // Temporarily align global from address so Mailables without explicit from() use fallback address.
                $originalFromAddress = Config::get('mail.from.address');
                $originalFromName = Config::get('mail.from.name');

                if (!empty($fallbackMeta['from_address'])) {
                    Config::set('mail.from.address', $fallbackMeta['from_address']);
                }
                if (!empty($fallbackMeta['from_name'])) {
                    Config::set('mail.from.name', $fallbackMeta['from_name']);
                }

                try {
                    $sendCallback('smtp_fallback', $fallbackMeta['from_address'], $fallbackMeta['from_name']);
                } finally {
                    Config::set('mail.from.address', $originalFromAddress);
                    Config::set('mail.from.name', $originalFromName);
                }

                if ($idempotencyKey) {
                    Cache::put($idempotencyKey, true, now()->addMinutes(15));
                }

                Log::info('Fallback SMTP email delivery succeeded.', [
                    'mailer' => 'smtp_fallback',
                    'host' => $fallbackMeta['host'],
                    'from_address' => $fallbackMeta['from_address'],
                    'context' => $this->safeContext($context),
                ]);

                return true;
            } catch (Throwable $fallbackException) {
                Log::error('Fallback SMTP email delivery failed.', [
                    'mailer' => 'smtp_fallback',
                    'host' => $fallbackMeta['host'],
                    'error' => $fallbackException->getMessage(),
                    'context' => $this->safeContext($context),
                ]);

                return false;
            }
        }
    }

    public function sendMailable(string|array $to, Mailable $mailable, array $context = []): bool
    {
        return $this->send(function (string $mailerName, ?string $fromAddress, ?string $fromName) use ($to, $mailable) {
            if (!empty($fromAddress) && $mailerName === 'smtp_fallback') {
                $mailable->from($fromAddress, $fromName ?: config('app.name'));
            }

            Mail::mailer($mailerName)->to($to)->send(clone $mailable);
        }, array_merge($context, [
            'mailable' => get_class($mailable),
            'recipient_count' => is_array($to) ? count($to) : 1,
        ]));
    }

    private function firstNonEmpty(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function safeContext(array $context): array
    {
        return collect($context)
            ->except(['password', 'mail_password', 'mail_fallback_password', 'secret', 'token'])
            ->all();
    }
}
