<?php

namespace App\Observers;

use App\Models\ProductCategory;
use App\Models\Notification;
use App\Models\User;

class ProductCategoryObserver
{
    /**
     * Handle the ProductCategory "created" event.
     */
    public function created(ProductCategory $category): void
    {
        $creator = $category->creator ?? auth()->user();
        if (!$creator) return;

        $name = $category->name ?: 'Category';

        // 1. Notify Creator
        $this->sendNotification($creator->id, "You have created a product category: {$name}.");

        // 2. Notify Admins if staff created
        if (method_exists($creator, 'isAdmin') && !$creator->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $creator->id) {
                    $this->sendNotification($admin->id, "{$creator->name} has created a product category: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the ProductCategory "updated" event.
     */
    public function updated(ProductCategory $category): void
    {
        $updater = auth()->user();
        if (!$updater) return;

        $name = $category->name ?: 'Category';

        // 1. Notify Updater
        $this->sendNotification($updater->id, "You have updated a product category: {$name}.");

        // 2. Notify Admins if staff updated
        if (method_exists($updater, 'isAdmin') && !$updater->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $updater->id) {
                    $this->sendNotification($admin->id, "{$updater->name} has updated a product category: {$name}.");
                }
            }
        }
    }

    /**
     * Handle the ProductCategory "deleted" event.
     */
    public function deleted(ProductCategory $category): void
    {
        $deleter = auth()->user();
        if (!$deleter) return;

        $name = $category->name ?: 'Category';

        // 1. Notify Deleter
        $this->sendNotification($deleter->id, "You have deleted a product category: {$name}.");

        // 2. Notify Admins if staff deleted
        if (method_exists($deleter, 'isAdmin') && !$deleter->isAdmin()) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $deleter->id) {
                    $this->sendNotification($admin->id, "{$deleter->name} has deleted a product category: {$name}.");
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
