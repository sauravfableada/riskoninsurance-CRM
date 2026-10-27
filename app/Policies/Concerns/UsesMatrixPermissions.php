<?php

namespace App\Policies\Concerns;

use App\Models\User;

abstract class UsesMatrixPermissions
{
    protected string $module;

    /**
     * Admin-role users remain an explicit bypass; all other users are evaluated
     * strictly through the matrix permission system.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->allows($user, 'edit');
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->allows($user, 'delete');
    }

    protected function allows(User $user, string $action): bool
    {
        return $user->hasMatrixPermission(sprintf('%s_%s', $action, $this->module));
    }
}
