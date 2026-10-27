<?php

use App\Mail\TicketCreatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

if (!function_exists('setMailConfig')) {
    /**
     * Dynamically set mail configuration from the settings table.
     */
    function setMailConfig()
    {
        $settings = DB::table('settings')->pluck('value', 'key')->toArray();

        // Check if common mail configuration exists to avoid setting empty values
        if (isset($settings['mail_host'])) {
            Config::set('mail.mailers.smtp.host', $settings['mail_host']);
            Config::set('mail.mailers.smtp.port', $settings['mail_port']);
            Config::set('mail.mailers.smtp.username', $settings['mail_username']);
            Config::set('mail.mailers.smtp.password', $settings['mail_password']);
            Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption']);

            if (!empty($settings['mail_from_address'])) {
                Config::set('mail.from.address', $settings['mail_from_address']);
            }
            if (!empty($settings['mail_from_name'])) {
                Config::set('mail.from.name', $settings['mail_from_name']);
            }
        }
    }
}

if (!function_exists('send_ticket_created_notification')) {
    /**
     * Send a created ticket notification email to the ticket customer.
     *
     * @param SupportTicket $ticket
     * @return bool
     */
    function send_ticket_created_notification(SupportTicket $ticket): bool
    {
        try {
            setMailConfig();

            // Notify admins
            $adminEmails = User::role(['super-admin'])->pluck('email')->filter()->toArray();
            if (!empty($adminEmails)) {
                Mail::to($adminEmails)->send(new TicketCreatedMail($ticket));
            }

            if (!$ticket->customer || empty($ticket->customer->email)) {
                return false;
            }

            Mail::to($ticket->customer->email)->send(new TicketCreatedMail($ticket));
            return true;
        } catch (\Exception $e) {
            \Log::error('send_ticket_created_notification failed: ' . $e->getMessage());
            return false;
        }
    }
}
