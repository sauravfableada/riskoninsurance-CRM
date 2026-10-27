<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Models\Notification;
use App\Models\User;

class SupportTicketObserver
{
    /**
     * Handle the SupportTicket "created" event.
     */
    public function created(SupportTicket $ticket): void
    {
        $creator = $ticket->creator ?? auth()->user();
        if (!$creator) return;

        $name = $ticket->ticket_name ?: 'Ticket';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a support ticket: {$name}.");

        // 2. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a support ticket: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the SupportTicket "updated" event.
     */
    public function updated(SupportTicket $ticket): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $ticket->ticket_name ?: 'Ticket';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a support ticket: {$name}.");

        // 2. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a support ticket: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the SupportTicket "deleted" event.
     */
    public function deleted(SupportTicket $ticket): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $ticket->ticket_name ?: 'Ticket';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a support ticket: {$name}.");

        // 2. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a support ticket: {$name}.");
                }
            }
        }
    }

    /**
     * Store notification.
     */
    private function sendNotification(int $userId, string $message): void
    {
        Notification::create([
            'user_id' => $userId,
            'notification_text' => $message,
            'is_read' => 0,
        ]);
    }
}
