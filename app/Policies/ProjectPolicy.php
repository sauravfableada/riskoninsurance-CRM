<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\OwnsRecord;

class ProjectPolicy
{
    use OwnsRecord;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_projects');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_projects') || $this->ownsRecord($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('create_projects');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('edit_projects') || $this->ownsRecord($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('delete_projects') || $this->ownsRecord($user, $project);
    }
}
