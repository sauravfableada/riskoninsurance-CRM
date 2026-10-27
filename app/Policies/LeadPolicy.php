<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\OwnsRecord;

class LeadPolicy
{
    use OwnsRecord;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_leads');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_leads') || $this->ownsRecord($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('create_leads');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('edit_leads') || $this->ownsRecord($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('delete_leads') || $this->ownsRecord($user, $lead);
    }
}
