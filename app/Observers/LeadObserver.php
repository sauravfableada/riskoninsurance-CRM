<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\Notification;
use App\Models\User;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        $creator = $lead->creator ?? auth()->user();
        if (!$creator) return;

        $name = $lead->name ?: 'Lead';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a lead: {$name}.");

        // 2. Notify Assignee
        if ($lead->assigned_user_id && $lead->assigned_user_id !== $creator->id) {
            $this->sendNotification($lead->assigned_user_id, "A lead has been assigned to you: {$name}.");
        }

        // 3. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a lead: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Lead "updated" event.
     */
    public function updated(Lead $lead): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $lead->name ?: 'Lead';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a lead: {$name}.");

        // 2. Notify Assignee
        if ($lead->assigned_user_id && $lead->assigned_user_id !== $updater->id) {
            $this->sendNotification($lead->assigned_user_id, "A lead assigned to you has been updated: {$name}.");
        }

        // 3. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a lead: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Lead "deleted" event.
     */
    public function deleted(Lead $lead): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $lead->name ?: 'Lead';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a lead: {$name}.");

        // 2. Notify Assignee
        if ($lead->assigned_user_id && $lead->assigned_user_id !== $deleter->id) {
            $this->sendNotification($lead->assigned_user_id, "A lead assigned to you has been deleted: {$name}.");
        }

        // 3. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a lead: {$name}.");
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
