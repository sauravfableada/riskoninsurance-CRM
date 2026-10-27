<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait OwnsRecord
{
    protected function ownsRecord(User $user, object $record): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $userId = (int) $user->id;

        if (isset($record->assigned_user_id) && !empty($record->assigned_user_id)) {
            return (int) $record->assigned_user_id === $userId
                || (isset($record->user_id) && (int) $record->user_id === $userId);
        }

        if (isset($record->user_id) && !empty($record->user_id)) {
            return (int) $record->user_id === $userId;
        }

        return isset($record->created_by) && (int) $record->created_by === $userId;
    }
}
