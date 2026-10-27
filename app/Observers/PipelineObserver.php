<?php

namespace App\Observers;

use App\Models\Pipeline;
use App\Models\Notification;
use App\Models\User;

class PipelineObserver
{
    /**
     * Handle the Pipeline "created" event.
     */
    public function created(Pipeline $pipeline): void
    {
        $creator = $pipeline->creator ?? auth()->user();
        if (!$creator) return;

        $name = $pipeline->pipeline_name ?: 'Pipeline';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a pipeline: {$name}.");

        // 2. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a pipeline: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Pipeline "updated" event.
     */
    public function updated(Pipeline $pipeline): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $pipeline->pipeline_name ?: 'Pipeline';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a pipeline: {$name}.");

        // 2. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a pipeline: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Pipeline "deleted" event.
     */
    public function deleted(Pipeline $pipeline): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $pipeline->pipeline_name ?: 'Pipeline';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a pipeline: {$name}.");

        // 2. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a pipeline: {$name}.");
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
