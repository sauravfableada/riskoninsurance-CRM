<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Notification;
use App\Models\User;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $creator = $invoice->creator ?? auth()->user();
        if (!$creator) return;

        $name = $invoice->number ?: 'Invoice';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created an invoice: {$name}.");

        // 2. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created an invoice: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $invoice->number ?: 'Invoice';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated an invoice: {$name}.");

        // 2. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated an invoice: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $invoice->number ?: 'Invoice';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted an invoice: {$name}.");

        // 2. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted an invoice: {$name}.");
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
