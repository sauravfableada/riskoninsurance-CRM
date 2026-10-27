<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Notification;

class StaffObserver
{
    /**
     * Handle the User "created" event for staff users.
     */
    public function created(User $user): void
    {
        $creator = request()->user() ?? auth()->user();

        $this->sendNotification($user->id, "Your staff account has been created.");

        if ($creator && $creator->id !== $user->id) {
            $this->sendNotification($creator->id, "You have created a staff user: {$user->name}.");
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
