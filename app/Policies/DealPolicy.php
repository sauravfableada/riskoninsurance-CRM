<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;
use App\Policies\Concerns\OwnsRecord;

class DealPolicy
{
    use OwnsRecord;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_deals');
    }

    public function view(User $user, Deal $deal): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_deals') || $this->ownsRecord($user, $deal);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('create_deals');
    }

    public function update(User $user, Deal $deal): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('edit_deals') || $this->ownsRecord($user, $deal);
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('delete_deals') || $this->ownsRecord($user, $deal);
    }
}
