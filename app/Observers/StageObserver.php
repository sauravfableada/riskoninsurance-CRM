<?php

namespace App\Observers;

use App\Models\Stage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StageObserver
{
    /**
     * Handle the Stage "created" event.
     */
    public function created(Stage $stage): void
    {
        $creator = Auth::user();
        if (!$creator) return;

        $name = $stage->name ?: 'Stage';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a stage: {$name}.");

        // 2. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a stage: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Stage "updated" event.
     */
    public function updated(Stage $stage): void
    {
        $updater = Auth::user();
        if (!$updater) return;

        $name = $stage->name ?: 'Stage';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a stage: {$name}.");

        // 2. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a stage: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Stage "deleted" event.
     */
    public function deleted(Stage $stage): void
    {
        $deleter = Auth::user();
        if (!$deleter) return;

        $name = $stage->name ?: 'Stage';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a stage: {$name}.");

        // 2. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a stage: {$name}.");
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
