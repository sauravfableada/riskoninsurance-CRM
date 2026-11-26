<?php

use App\Mail\TicketCreatedMail;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\FallbackMailService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

if (!function_exists('setMailConfig')) {
    /**
     * Dynamically set mail configuration from the settings table.
     */
    function setMailConfig(): void
    {
        app(FallbackMailService::class)->configurePrimaryMailer();
    }
}

if (!function_exists('send_with_fallback_mail')) {
    /**
     * Send a Mailable using the centralized FallbackMailService.
     *
     * @param string|array<int, string> $to
     */
    function send_with_fallback_mail(string|array $to, Mailable $mailable, array $context = []): bool
    {
        return app(FallbackMailService::class)->sendMailable($to, $mailable, $context);
    }
}

if (!function_exists('send_ticket_created_notification')) {
    /**
     * Send a created ticket notification email to the ticket customer and admins.
     */
    function send_ticket_created_notification(SupportTicket $ticket): bool
    {
        try {
            $mailService = app(FallbackMailService::class);
            $mailService->configurePrimaryMailer();

            // Notify admins
            $adminEmails = User::role(['super-admin'])->pluck('email')->filter()->values()->toArray();
            if (!empty($adminEmails)) {
                $mailService->sendMailable($adminEmails, new TicketCreatedMail($ticket), [
                    'feature' => 'support_ticket_admin',
                    'ticket_id' => $ticket->id,
                    'idempotency_key' => "ticket_{$ticket->id}_admins",
                ]);
            }

            if (!$ticket->customer || empty($ticket->customer->email)) {
                return false;
            }

            return $mailService->sendMailable($ticket->customer->email, new TicketCreatedMail($ticket), [
                'feature' => 'support_ticket_customer',
                'ticket_id' => $ticket->id,
                'idempotency_key' => "ticket_{$ticket->id}_customer",
            ]);
        } catch (\Throwable $e) {
            Log::error('send_ticket_created_notification failed: ' . $e->getMessage());
            return false;
        }
    }
}
