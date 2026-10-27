<?php

namespace App\Policies;

use App\Policies\Concerns\UsesMatrixPermissions;

class SupportTicketPolicy extends UsesMatrixPermissions
{
    protected string $module = 'tickets';
}
