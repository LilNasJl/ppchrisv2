<?php

namespace App\Services;

use App\Models\EmployeeLoan;
use App\Models\User;

class LoanAccessService
{
    public function canManage(?User $user): bool
    {
        return $user && ! $user->is_disabled && ! $user->trashed()
            && in_array($user->role, ['hr', 'admin'], true)
            && $user->can('View:LoanManagement');
    }

    public function authorizeManagement(?User $user): void
    {
        abort_unless($this->canManage($user), 403);
    }

    public function authorizeLedger(?User $user, EmployeeLoan $loan): void
    {
        $owner = $user && ! $user->is_disabled && ! $user->trashed()
            && $user->role === 'employee' && $user->employee
            && ! $user->employee->hasEndedEmployment()
            && (int) $user->employee->id === (int) $loan->employee_id;

        abort_unless($owner || $this->canManage($user), 403);
    }
}
