<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\Notification;
use App\Models\User;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        $creator = $project->creator ?? auth()->user();
        if (!$creator) return;

        $name = $project->name ?: 'Project';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a project: {$name}.");

        // 2. Notify Assignee
        if ($project->assigned_user_id && $project->assigned_user_id !== $creator->id) {
            $this->sendNotification($project->assigned_user_id, "A project has been assigned to you: {$name}.");
        }

        // 3. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a project: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $project->name ?: 'Project';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a project: {$name}.");

        // 2. Notify Assignee
        if ($project->assigned_user_id && $project->assigned_user_id !== $updater->id) {
            $this->sendNotification($project->assigned_user_id, "A project assigned to you has been updated: {$name}.");
        }

        // 3. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a project: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $project->name ?: 'Project';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a project: {$name}.");

        // 2. Notify Assignee
        if ($project->assigned_user_id && $project->assigned_user_id !== $deleter->id) {
            $this->sendNotification($project->assigned_user_id, "A project assigned to you has been deleted: {$name}.");
        }

        // 3. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a project: {$name}.");
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
