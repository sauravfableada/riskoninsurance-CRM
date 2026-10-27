<?php

namespace App\Observers;

use App\Models\Meeting;
use App\Models\Notification;
use App\Models\User;

class MeetingObserver
{
    /**
     * Handle the Meeting "created" event.
     */
    public function created(Meeting $meeting): void
    {
        $creator = $meeting->creator ?? auth()->user();
        if (!$creator) return;

        $name = $meeting->title ?: 'Meeting';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a meeting: {$name}.");

        // 2. Notify Assignee
        if ($meeting->assigned_user_id && $meeting->assigned_user_id !== $creator->id) {
            $this->sendNotification($meeting->assigned_user_id, "A meeting has been assigned to you: {$name}.");
        }

        // 3. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a meeting: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Meeting "updated" event.
     */
    public function updated(Meeting $meeting): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $meeting->title ?: 'Meeting';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a meeting: {$name}.");

        // 2. Notify Assignee
        if ($meeting->assigned_user_id && $meeting->assigned_user_id !== $updater->id) {
            $this->sendNotification($meeting->assigned_user_id, "A meeting assigned to you has been updated: {$name}.");
        }

        // 3. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a meeting: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Meeting "deleted" event.
     */
    public function deleted(Meeting $meeting): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $meeting->title ?: 'Meeting';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a meeting: {$name}.");

        // 2. Notify Assignee
        if ($meeting->assigned_user_id && $meeting->assigned_user_id !== $deleter->id) {
            $this->sendNotification($meeting->assigned_user_id, "A meeting assigned to you has been deleted: {$name}.");
        }

        // 3. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a meeting: {$name}.");
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
