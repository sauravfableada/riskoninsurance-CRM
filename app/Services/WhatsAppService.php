<?php

namespace App\Services;

use App\Models\WhatsappConfig;
use App\Models\WhatsappLog;
use App\Models\WhatsappMessageTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private ?WhatsappConfig $config = null;

    private function httpClient(bool $verifySsl = true)
    {
        $client = Http::timeout(15)->withOptions([
            'proxy' => '',
            'curl' => [
                CURLOPT_PROXY => '',
            ],
        ]);

        if (!$verifySsl || app()->environment(['local', 'development'])) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function normalizePhoneNumber(string $toNumber): string
    {
        $toNumber = preg_replace('/[^0-9]/', '', $toNumber);

        if (strlen($toNumber) === 10 && preg_match('/^[6-9]/', $toNumber)) {
            return '91' . $toNumber;
        }

        return $toNumber;
    }

    private function config(): ?WhatsappConfig
    {
        if ($this->config === null) {
            $this->config = WhatsappConfig::first();
        }

        return $this->config;
    }

    public function isConfigured(): bool
    {
        $cfg = $this->config();

        return $cfg
            && filled($cfg->access_token)
            && filled($cfg->phone_number_id);
    }

    public function sendTemplate(
        string $toNumber,
        string $templateName,
        array $variables = [],
        ?string $module = null,
        ?int $moduleId = null
    ): bool {
        if (!$this->isConfigured()) {
            Log::warning('WhatsApp: not configured, skipping send.', [
                'template' => $templateName,
                'module' => $module,
                'module_id' => $moduleId,
            ]);

            return false;
        }

        $cfg = $this->config();
        $toNumber = $this->normalizePhoneNumber($toNumber);

        if (empty($toNumber)) {
            Log::warning('WhatsApp: empty phone number, skipping.', [
                'template' => $templateName,
                'module' => $module,
                'module_id' => $moduleId,
            ]);

            return false;
        }

        $components = [];
        if (!empty($variables)) {
            $params = array_map(fn($value) => ['type' => 'text', 'text' => (string) $value], $variables);
            $components[] = ['type' => 'body', 'parameters' => $params];
        }

        $template = WhatsappMessageTemplate::where('name', $templateName)->first();
        $language = $template?->language ?: 'en';

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toNumber,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        $log = WhatsappLog::create([
            'to_number' => $toNumber,
            'template_name' => $templateName,
            'module' => $module,
            'module_id' => $moduleId,
            'variables' => $variables,
            'status' => 'pending',
            'sent_by' => auth()->id(),
        ]);

        Log::info('WhatsApp dispatch started', [
            'template' => $templateName,
            'module' => $module,
            'module_id' => $moduleId,
            'to' => $toNumber,
            'variables_count' => count($variables),
            'variables' => $variables,
            'language' => $language,
            'log_id' => $log->id,
        ]);

        try {
            $response = $this->httpClient()
                ->withToken($cfg->access_token)
                ->post("https://graph.facebook.com/v19.0/{$cfg->phone_number_id}/messages", $payload);

            $body = $response->json();

            if ($response->successful() && isset($body['messages'][0]['id'])) {
                $log->update([
                    'status' => 'sent',
                    'meta_message_id' => $body['messages'][0]['id'],
                ]);

                Log::info('WhatsApp dispatch succeeded', [
                    'template' => $templateName,
                    'module' => $module,
                    'module_id' => $moduleId,
                    'to' => $toNumber,
                    'meta_message_id' => $body['messages'][0]['id'],
                    'log_id' => $log->id,
                ]);

                return true;
            }

            $log->update([
                'status' => 'failed',
                'error_message' => json_encode($body),
            ]);

            Log::error('WhatsApp send failed', [
                'response' => $body,
                'template' => $templateName,
                'module' => $module,
                'module_id' => $moduleId,
                'to' => $toNumber,
                'log_id' => $log->id,
            ]);

            return false;
        } catch (\Throwable $e) {
            $isSslError = str_contains(strtolower($e->getMessage()), 'ssl certificate');

            if ($isSslError) {
                try {
                    $response = $this->httpClient(false)
                        ->withToken($cfg->access_token)
                        ->post("https://graph.facebook.com/v19.0/{$cfg->phone_number_id}/messages", $payload);

                    $body = $response->json();

                    if ($response->successful() && isset($body['messages'][0]['id'])) {
                        $log->update([
                            'status' => 'sent',
                            'meta_message_id' => $body['messages'][0]['id'],
                            'error_message' => null,
                        ]);

                        Log::info('WhatsApp dispatch succeeded after SSL retry', [
                            'template' => $templateName,
                            'module' => $module,
                            'module_id' => $moduleId,
                            'to' => $toNumber,
                            'meta_message_id' => $body['messages'][0]['id'],
                            'log_id' => $log->id,
                        ]);

                        return true;
                    }

                    $log->update([
                        'status' => 'failed',
                        'error_message' => json_encode($body),
                    ]);

                    Log::error('WhatsApp send failed after SSL retry', [
                        'response' => $body,
                        'template' => $templateName,
                        'module' => $module,
                        'module_id' => $moduleId,
                        'to' => $toNumber,
                        'log_id' => $log->id,
                    ]);

                    return false;
                } catch (\Throwable $retryException) {
                    $e = $retryException;
                }
            }

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('WhatsApp exception', [
                'error' => $e->getMessage(),
                'template' => $templateName,
                'module' => $module,
                'module_id' => $moduleId,
                'to' => $toNumber,
                'log_id' => $log->id,
            ]);

            return false;
        }
    }

    public function sendForModule(
        string $module,
        string $toNumber,
        array $variables = [],
        ?int $moduleId = null
    ): bool {
        $module = trim($module);

        Log::info('WhatsApp module send requested', [
            'module' => $module,
            'module_id' => $moduleId,
            'to' => $toNumber,
            'variables_count' => count($variables),
        ]);

        $template = WhatsappMessageTemplate::query()
            ->whereRaw('LOWER(TRIM(use_for_module)) = ?', [strtolower($module)])
            ->where('is_active', true)
            ->whereRaw('UPPER(TRIM(status)) = ?', ['APPROVED'])
            ->first();

        if (!$template) {
            Log::warning('WhatsApp: no active approved template mapped for module.', [
                'module' => $module,
                'module_id' => $moduleId,
                'to' => $toNumber,
            ]);

            return false;
        }

        Log::info('WhatsApp module mapped to template', [
            'module' => $module,
            'module_id' => $moduleId,
            'template' => $template->name,
            'template_status' => $template->status,
            'template_active' => $template->is_active,
            'to' => $toNumber,
        ]);

        return $this->sendTemplate($toNumber, $template->name, $variables, $module, $moduleId);
    }
}
