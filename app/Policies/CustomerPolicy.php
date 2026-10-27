<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return method_exists($user, 'hasMatrixPermission') && $user->hasMatrixPermission('view_customers');
    }

    public function view(User $user, mixed $model): bool
    {
        if (!$model instanceof Customer) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasMatrixPermission') && $user->hasMatrixPermission('view_customers')) {
            return true;
        }

        return Customer::query()
            ->visibleToUser($user)
            ->whereKey($model->getKey())
            ->exists();
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return method_exists($user, 'hasMatrixPermission') && $user->hasMatrixPermission('create_customers');
    }

    public function update(User $user, mixed $model): bool
    {
        if (!$model instanceof Customer) {
            return false;
        }

        if (method_exists($user, 'hasMatrixPermission') && $user->hasMatrixPermission('edit_customers')) {
            return true;
        }

        return $user->isAdmin() || $model->isOwnedBy($user);
    }

    public function delete(User $user, mixed $model): bool
    {
        if (!$model instanceof Customer) {
            return false;
        }

        if (method_exists($user, 'hasMatrixPermission') && $user->hasMatrixPermission('delete_customers')) {
            return true;
        }

        return $user->isAdmin() || $model->isOwnedBy($user);
    }
}
