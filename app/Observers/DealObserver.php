<?php

namespace App\Observers;

use App\Models\Deal;
use App\Models\Notification;
use App\Models\User;

class DealObserver
{
    /**
     * Handle the Deal "created" event.
     */
    public function created(Deal $deal): void
    {
        $creator = $deal->creator ?? auth()->user();
        if (!$creator) return;

        $name = $deal->title ?: 'Deal';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a deal: {$name}.");

        // 2. Notify Assignee
        if ($deal->assigned_user_id && $deal->assigned_user_id !== $creator->id) {
            $this->sendNotification($deal->assigned_user_id, "A deal has been assigned to you: {$name}.");
        }

        // 3. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a deal: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Deal "updated" event.
     */
    public function updated(Deal $deal): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $deal->title ?: 'Deal';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a deal: {$name}.");

        // 2. Notify Assignee
        if ($deal->assigned_user_id && $deal->assigned_user_id !== $updater->id) {
            $this->sendNotification($deal->assigned_user_id, "A deal assigned to you has been updated: {$name}.");
        }

        // 3. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a deal: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Deal "deleted" event.
     */
    public function deleted(Deal $deal): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $deal->title ?: 'Deal';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a deal: {$name}.");

        // 2. Notify Assignee
        if ($deal->assigned_user_id && $deal->assigned_user_id !== $deleter->id) {
            $this->sendNotification($deal->assigned_user_id, "A deal assigned to you has been deleted: {$name}.");
        }

        // 3. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a deal: {$name}.");
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