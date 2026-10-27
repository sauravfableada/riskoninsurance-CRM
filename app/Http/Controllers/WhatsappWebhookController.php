<?php

namespace App\Http\Controllers;

use App\Models\WhatsappLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = config('services.whatsapp.verify_token', 'fablead_whatsapp_verify');

        if ($mode === 'subscribe' && $token === $expectedToken) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('WhatsApp webhook received', $payload);

        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['statuses'] ?? [] as $status) {
                    $wamid = $status['id'] ?? null;
                    $newStatus = strtolower($status['status'] ?? '');

                    if ($wamid && in_array($newStatus, ['delivered', 'read', 'failed'], true)) {
                        WhatsappLog::where('meta_message_id', $wamid)
                            ->update(['status' => $newStatus]);
                    }
                }

                foreach ($value['messages'] ?? [] as $message) {
                    Log::info('WhatsApp incoming message', $message);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
