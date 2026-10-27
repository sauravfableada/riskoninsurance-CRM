<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Notification;
use App\Models\User;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $creator = $product->creator ?? auth()->user();
        if (!$creator) return;

        $name = $product->name ?: 'Product';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a product: {$name}.");

        // 2. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a product: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $product->name ?: 'Product';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a product: {$name}.");

        // 2. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a product: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $product->name ?: 'Product';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a product: {$name}.");

        // 2. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a product: {$name}.");
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
