<?php

namespace App\Observers;

use App\Models\FollowUp;
use App\Models\Notification;
use App\Models\User;

class FollowUpObserver
{
    /**
     * Handle the FollowUp "created" event.
     */
    public function created(FollowUp $followUp): void
    {
        $creator = $followUp->creator ?? auth()->user();
        if (!$creator) return;

        $name = $followUp->purpose ?: 'Follow up';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a follow up: {$name}.");

        // 2. Notify Assignee
        if ($followUp->assigned_user_id && $followUp->assigned_user_id !== $creator->id) {
            $this->sendNotification($followUp->assigned_user_id, "A follow up has been assigned to you: {$name}.");
        }

        // 3. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a follow up: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the FollowUp "updated" event.
     */
    public function updated(FollowUp $followUp): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $followUp->purpose ?: 'Follow up';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a follow up: {$name}.");

        // 2. Notify Assignee
        if ($followUp->assigned_user_id && $followUp->assigned_user_id !== $updater->id) {
            $this->sendNotification($followUp->assigned_user_id, "A follow up assigned to you has been updated: {$name}.");
        }

        // 3. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a follow up: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the FollowUp "deleted" event.
     */
    public function deleted(FollowUp $followUp): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $followUp->purpose ?: 'Follow up';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a follow up: {$name}.");

        // 2. Notify Assignee
        if ($followUp->assigned_user_id && $followUp->assigned_user_id !== $deleter->id) {
            $this->sendNotification($followUp->assigned_user_id, "A follow up assigned to you has been deleted: {$name}.");
        }

        // 3. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a follow up: {$name}.");
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
