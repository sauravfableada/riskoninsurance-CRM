<?php

use App\Models\Notification;

if (!function_exists('sendNotification')) {
    /**
     * Create a notification for a user.
     *
     * @param int $userId
     * @param string $message
     * @return \App\Models\Notification
     */
    function sendNotification($userId, $message)
    {
        return Notification::create([
            'user_id' => $userId,
            'notification_text' => $message,
            'is_read' => 0,
        ]);
    }
}
