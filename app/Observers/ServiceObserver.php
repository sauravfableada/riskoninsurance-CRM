<?php

namespace App\Observers;

use App\Models\Service;
use App\Models\Notification;
use App\Models\User;

class ServiceObserver
{
    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        $creator = $service->creator ?? auth()->user();
        if (!$creator) return;

        $name = $service->service_name ?: 'Service';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a service: {$name}.");

        // 2. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a service: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $service->service_name ?: 'Service';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a service: {$name}.");

        // 2. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a service: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $service->service_name ?: 'Service';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a service: {$name}.");

        // 2. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a service: {$name}.");
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
