<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\Notification;
use App\Models\User;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $creator = $task->owner ?? auth()->user();
        if (!$creator) return;

        $name = $task->title ?: 'Task';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a task: {$name}.");

        // 2. Notify Assignee
        if ($task->assigned_user_id && $task->assigned_user_id !== $creator->id) {
            $this->sendNotification($task->assigned_user_id, "A task has been assigned to you: {$name}.");
        }

        // 3. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a task: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $task->title ?: 'Task';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a task: {$name}.");

        // 2. Notify Assignee
        if ($task->assigned_user_id && $task->assigned_user_id !== $updater->id) {
            $this->sendNotification($task->assigned_user_id, "A task assigned to you has been updated: {$name}.");
        }

        // 3. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a task: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $task->title ?: 'Task';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a task: {$name}.");

        // 2. Notify Assignee
        if ($task->assigned_user_id && $task->assigned_user_id !== $deleter->id) {
            $this->sendNotification($task->assigned_user_id, "A task assigned to you has been deleted: {$name}.");
        }

        // 3. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a task: {$name}.");
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
