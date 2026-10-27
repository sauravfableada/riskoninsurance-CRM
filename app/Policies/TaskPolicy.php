<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\OwnsRecord;

class TaskPolicy
{
    use OwnsRecord;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_tasks');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_tasks') || $this->ownsRecord($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('create_tasks');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('edit_tasks') || $this->ownsRecord($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('delete_tasks') || $this->ownsRecord($user, $task);
    }
}
