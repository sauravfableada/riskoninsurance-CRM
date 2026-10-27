<?php

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\User;

class PipelinePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasMatrixPermission('view_pipeline');
    }

    public function view(User $user, Pipeline $pipeline): bool
    {
        return $user->hasMatrixPermission('view_pipeline') || (int) $pipeline->created_by === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasMatrixPermission('create_pipeline');
    }

    public function update(User $user, Pipeline $pipeline): bool
    {
        return $user->hasMatrixPermission('edit_pipeline') || (int) $pipeline->created_by === (int) $user->id;
    }

    public function delete(User $user, Pipeline $pipeline): bool
    {
        return $user->hasMatrixPermission('delete_pipeline') || (int) $pipeline->created_by === (int) $user->id;
    }
}
