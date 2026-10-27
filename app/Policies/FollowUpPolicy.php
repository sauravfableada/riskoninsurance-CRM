<?php

namespace App\Policies;

use App\Models\FollowUp;
use App\Models\User;
use App\Policies\Concerns\OwnsRecord;

class FollowUpPolicy
{
    use OwnsRecord;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_followups');
    }

    public function view(User $user, FollowUp $followUp): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_followups') || $this->ownsRecord($user, $followUp);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('create_followups');
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('edit_followups') || $this->ownsRecord($user, $followUp);
    }

    public function delete(User $user, FollowUp $followUp): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('delete_followups') || $this->ownsRecord($user, $followUp);
    }
}
