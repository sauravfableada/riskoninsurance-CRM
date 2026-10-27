<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\OwnsRecord;

class InvoicePolicy
{
    use OwnsRecord;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_invoices');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('view_invoices') || $this->ownsRecord($user, $invoice);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('create_invoices');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('edit_invoices') || $this->ownsRecord($user, $invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->hasMatrixPermission('delete_invoices') || $this->ownsRecord($user, $invoice);
    }
}
