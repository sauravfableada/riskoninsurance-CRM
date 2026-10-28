<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Notification;

class StaffObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $creator = request()->user() ?? auth()->user();
        $isSubAgent = ($user->type === 'sub_agents');
        $label = $isSubAgent ? 'sub-agent' : 'staff';

        $this->sendNotification($user->id, "Your {$label} account has been created.");

        if ($creator && $creator->id !== $user->id) {
            $this->sendNotification($creator->id, "You have created a {$label}: {$user->name}.");
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $updater = request()->user() ?? auth()->user();
        $isSubAgent = ($user->type === 'sub_agents');
        $label = $isSubAgent ? 'sub-agent' : 'staff';

        if ($updater && $updater->id !== $user->id) {
            $this->sendNotification($updater->id, "You have updated a {$label}: {$user->name}.");
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $deleter = request()->user() ?? auth()->user();
        $isSubAgent = ($user->type === 'sub_agents');
        $label = $isSubAgent ? 'sub-agent' : 'staff';

        if ($deleter && $deleter->id !== $user->id) {
            $this->sendNotification($deleter->id, "You have deleted a {$label}: {$user->name}.");
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
