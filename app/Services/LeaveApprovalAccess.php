<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveApprovalWorkflow;
use App\Models\LeaveRequestApproval;
use App\Models\User;

class LeaveApprovalAccess
{
    public static function hr(?User $user): bool
    {
        return $user && ! $user->is_disabled && in_array($user->role, ['hr', 'admin'], true);
    }

    public static function review(?User $user): bool
    {
        return self::hr($user) && ($user->role === 'admin' || $user->can('Review:Leave') || $user->can('Update:Leave'));
    }

    public static function configure(?User $user): bool
    {
        return self::hr($user) && ($user->role === 'admin' || $user->can('Manage:LeaveWorkflow'));
    }

    public static function override(?User $user): bool
    {
        return self::review($user) && ($user->role === 'admin' || $user->can('Override:Leave'));
    }

    public static function employee(?Employee $employee): bool
    {
        return $employee && ! $employee->trashed() && ! $employee->hasEndedEmployment()
            && $employee->user && $employee->user->role === 'employee' && ! $employee->user->is_disabled;
    }

    public static function inbox(?User $user): bool
    {
        if (! $user || $user->role !== 'employee' || ! self::employee($user->employee)) {
            return false;
        }

        $id = $user->employee->id;

        // Retain access to previously assigned decisions even after a configuration change.
        return LeaveRequestApproval::where('approver_employee_id', $id)->exists()
            || LeaveApprovalWorkflow::where('is_active', true)->whereHas('levels', fn ($q) => $q
                ->where('approver_employee_id', $id)->orWhere('alternate_employee_id', $id))->exists();
    }

    public static function view(User $user, Leave $leave): bool
    {
        if ($user->is_disabled) {
            return false;
        }
        if (self::hr($user)) {
            return $user->role === 'admin' || $user->can('View:Leave') || self::review($user);
        }
        if (! self::employee($user->employee) || $user->role !== 'employee') {
            return false;
        }

        return (int) $leave->employee_id === (int) $user->employee->id
            || $leave->approvalSteps()->where('approver_employee_id', $user->employee->id)
                ->whereNotNull('activated_at')->exists();
    }
}
