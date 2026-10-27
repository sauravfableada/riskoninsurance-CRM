<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;
use App\Policies\Concerns\OwnsRecord;

class MeetingPolicy
{
    use OwnsRecord;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_meetings');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_meetings') || $this->ownsRecord($user, $meeting);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('create_meetings');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('edit_meetings') || $this->ownsRecord($user, $meeting);
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('delete_meetings') || $this->ownsRecord($user, $meeting);
    }
}
