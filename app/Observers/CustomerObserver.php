<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\Notification;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        $creator = $customer->creator;

        if (!$creator) {
            return;
        }

        $customerName = $customer->name;

        $this->sendNotification($creator->id, "You have created a customer: {$customerName}.");

        if ($customer->user_id && $customer->user_id !== $creator->id) {
            $this->sendNotification($customer->user_id, "Wellcome {$customerName}.");
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
