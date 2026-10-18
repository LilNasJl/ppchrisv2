<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveApprovalWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StationManagementAccess
{
    /**
     * Determine if the user or employee has an active, enabled account eligible for approver duties.
     */
    public static function isActiveApproverAccount(User|Employee|null $user): bool
    {
        if ($user instanceof User && $user->is_disabled) {
            return false;
        }

        $employee = static::resolveEmployee($user);

        if (! $employee || $employee->trashed() || $employee->hasEndedEmployment()) {
            return false;
        }

        if ($employee->relationLoaded('user')) {
            $boundUser = $employee->getRelation('user');
            if ($boundUser instanceof User && $boundUser->is_disabled) {
                return false;
            }
        } elseif ($employee->user_id && Schema::hasTable('users')) {
            $boundUser = $employee->user;
            if ($boundUser instanceof User && $boundUser->is_disabled) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve all active Approval Workflows where the employee is assigned as an approver at ANY level.
     *
     * @return Collection<int, LeaveApprovalWorkflow>
     */
    public static function getActiveApproverWorkflows(User|Employee|null $user): Collection
    {
        if (! static::isActiveApproverAccount($user)) {
            return collect();
        }

        if (! Schema::hasTable('leave_approval_workflows') || ! Schema::hasTable('leave_approval_levels')) {
            return collect();
        }

        $employee = static::resolveEmployee($user);
        if (! $employee) {
            return collect();
        }

        return LeaveApprovalWorkflow::query()
            ->with('levels')
            ->where('is_active', true)
            ->whereHas('levels', fn (Builder $q): Builder => $q->where('approver_employee_id', $employee->id))
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public static function getManagedBiometricsDeviceIds(User|Employee|null $user): array
    {
        return static::getActiveApproverWorkflows($user)
            ->flatMap(fn (LeaveApprovalWorkflow $workflow): array => $workflow->device_ids ?? [])
            ->map(fn ($id): string => (string) $id)
            ->filter(fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Determine if the user or employee is authorized to access Station Management.
     * Access is dynamically determined strictly from active Approval Workflow assignments.
     */
    public static function canAccessStationManagement(User|Employee|null $user): bool
    {
        return static::getActiveApproverWorkflows($user)->isNotEmpty();
    }

    /**
     * Get the combined list of branch IDs covered by all active workflows where this user/employee is an approver.
     *
     * @return array<int>
     */
    public static function getManagedBranchIds(User|Employee|null $user): array
    {
        $workflows = static::getActiveApproverWorkflows($user);

        if ($workflows->isEmpty()) {
            return [];
        }

        $branchIds = collect();

        foreach ($workflows as $workflow) {
            $empScopeId = $workflow->getAttribute('employee_id');
            $branchScopeId = $workflow->getAttribute('branch_id');
            $deptScopeId = $workflow->getAttribute('department_id');
            $desigScopeId = $workflow->getAttribute('designation_id');

            // Company-wide workflow (all scope columns null)
            if (! $empScopeId && ! $branchScopeId && ! $deptScopeId && ! $desigScopeId) {
                $allBranchIds = Branch::query()->pluck('id')->map(fn ($id): int => (int) $id);
                $branchIds = $branchIds->merge($allBranchIds);

                continue;
            }

            if ($branchScopeId) {
                $branchIds->push((int) $branchScopeId);
            }

            if ($empScopeId) {
                $targetBranchId = Employee::query()->whereKey($empScopeId)->value('branch_id');
                if ($targetBranchId) {
                    $branchIds->push((int) $targetBranchId);
                }
            }

            if ($deptScopeId && ! $branchScopeId) {
                $deptBranchIds = Employee::query()
                    ->activeEmployment()
                    ->where('department_id', (int) $deptScopeId)
                    ->whereNotNull('branch_id')
                    ->distinct()
                    ->pluck('branch_id')
                    ->map(fn ($id): int => (int) $id);
                $branchIds = $branchIds->merge($deptBranchIds);
            }

            if ($desigScopeId && ! $branchScopeId) {
                $desigBranchIds = Employee::query()
                    ->activeEmployment()
                    ->where('designation_id', (int) $desigScopeId)
                    ->whereNotNull('branch_id')
                    ->distinct()
                    ->pluck('branch_id')
                    ->map(fn ($id): int => (int) $id);
                $branchIds = $branchIds->merge($desigBranchIds);
            }
        }

        return $branchIds->filter(fn (int $id): bool => $id > 0)->unique()->values()->all();
    }

    /**
     * Scope an Employee query to only records covered by the approver's active workflow scopes.
     */
    public static function scopeManagedEmployeesQuery(Builder $query, User|Employee|null $user): Builder
    {
        $workflows = static::getActiveApproverWorkflows($user);

        if ($workflows->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        $table = $query->getModel()->getTable();

        return $query->where(function (Builder $outer) use ($workflows, $table): void {
            foreach ($workflows as $workflow) {
                $empScopeId = $workflow->getAttribute('employee_id');
                $branchScopeId = $workflow->getAttribute('branch_id');
                $deptScopeId = $workflow->getAttribute('department_id');
                $desigScopeId = $workflow->getAttribute('designation_id');

                $outer->orWhere(function (Builder $inner) use ($table, $empScopeId, $branchScopeId, $deptScopeId, $desigScopeId): void {
                    if ($empScopeId) {
                        $inner->where("{$table}.id", (int) $empScopeId);
                    }
                    if ($branchScopeId) {
                        $inner->where("{$table}.branch_id", (int) $branchScopeId);
                    }
                    if ($deptScopeId) {
                        $inner->where("{$table}.department_id", (int) $deptScopeId);
                    }
                    if ($desigScopeId) {
                        $inner->where("{$table}.designation_id", (int) $desigScopeId);
                    }
                });
            }
        });
    }

    /**
     * Get branch metadata array (branch_id, branch_name) managed by this user/employee.
     *
     * @return array<int, array{branch_id: int, branch_name: string}>
     */
    public static function getAssignedBranches(User|Employee|null $user): array
    {
        $branchIds = static::getManagedBranchIds($user);

        if ($branchIds === []) {
            return [];
        }

        return Branch::query()
            ->whereIn('id', $branchIds)
            ->orderBy('branch_name')
            ->get(['id', 'branch_name'])
            ->map(fn (Branch $b): array => [
                'branch_id' => (int) $b->id,
                'branch_name' => (string) $b->branch_name,
            ])
            ->values()
            ->all();
    }

    /**
     * Check if user/employee is authorized to manage a specific branch.
     */
    public static function canManageBranch(User|Employee|null $user, int|Branch|string|null $branch): bool
    {
        if (blank($branch)) {
            return false;
        }

        $branchId = $branch instanceof Branch
            ? (int) $branch->id
            : (is_numeric($branch) ? (int) $branch : Branch::resolvePublicId($branch));

        if (! $branchId) {
            return false;
        }

        return in_array($branchId, static::getManagedBranchIds($user), true);
    }

    /**
     * Check if user/employee is authorized to manage a specific target employee across any active workflow scope.
     */
    public static function canManageEmployee(User|Employee|null $user, Employee|int|string|null $target): bool
    {
        if (blank($target)) {
            return false;
        }

        $targetEmployee = $target instanceof Employee
            ? $target
            : (is_numeric($target) ? Employee::query()->find($target) : Employee::findByPublicKey($target));

        if (! $targetEmployee || ! $targetEmployee->branch_id) {
            return false;
        }

        $workflows = static::getActiveApproverWorkflows($user);

        if ($workflows->isEmpty()) {
            return false;
        }

        foreach ($workflows as $workflow) {
            $empScopeId = $workflow->getAttribute('employee_id');
            $branchScopeId = $workflow->getAttribute('branch_id');
            $deptScopeId = $workflow->getAttribute('department_id');
            $desigScopeId = $workflow->getAttribute('designation_id');

            if ($empScopeId && (int) $targetEmployee->id !== (int) $empScopeId) {
                continue;
            }
            if ($branchScopeId && (int) $targetEmployee->branch_id !== (int) $branchScopeId) {
                continue;
            }
            if ($deptScopeId && (int) $targetEmployee->department_id !== (int) $deptScopeId) {
                continue;
            }
            if ($desigScopeId && (int) $targetEmployee->designation_id !== (int) $desigScopeId) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Scope a DtrChangeRequest query to only requests covered by the approver's active workflow scopes.
     */
    public static function scopeManagedChangeRequestsQuery(Builder $query, User|Employee|null $user): Builder
    {
        $workflows = static::getActiveApproverWorkflows($user);

        if ($workflows->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        $approverEmployee = static::resolveEmployee($user);
        $table = $query->getModel()->getTable();

        return $query->where(function (Builder $outer) use ($workflows, $approverEmployee, $table): void {
            foreach ($workflows as $workflow) {
                $empScopeId = $workflow->getAttribute('employee_id');
                $branchScopeId = $workflow->getAttribute('branch_id');
                $deptScopeId = $workflow->getAttribute('department_id');
                $desigScopeId = $workflow->getAttribute('designation_id');

                $outer->orWhere(function (Builder $inner) use ($table, $empScopeId, $branchScopeId, $deptScopeId, $desigScopeId): void {
                    if ($empScopeId) {
                        $inner->where("{$table}.employee_id", (int) $empScopeId);
                    }
                    if ($branchScopeId) {
                        $inner->where("{$table}.branch_id", (int) $branchScopeId);
                    }
                    if ($deptScopeId) {
                        $inner->whereHas('employee', fn (Builder $empQ): Builder => $empQ->where('department_id', (int) $deptScopeId));
                    }
                    if ($desigScopeId) {
                        $inner->whereHas('employee', fn (Builder $empQ): Builder => $empQ->where('designation_id', (int) $desigScopeId));
                    }
                });

                $subordinateIds = static::getSubordinateApproverIdsInWorkflow($workflow, $approverEmployee?->id);
                if ($subordinateIds !== []) {
                    $outer->orWhereIn("{$table}.employee_id", $subordinateIds);
                }
            }
        });
    }

    /**
     * Check if user/employee is authorized for a specific DtrChangeRequest based on active workflow scopes.
     */
    public static function canManageChangeRequest(User|Employee|null $user, \App\Models\DtrChangeRequest $request): bool
    {
        $workflows = static::getActiveApproverWorkflows($user);

        if ($workflows->isEmpty()) {
            return false;
        }

        $approverEmployee = static::resolveEmployee($user);
        $targetEmployee = null;

        foreach ($workflows as $workflow) {
            $subordinateIds = static::getSubordinateApproverIdsInWorkflow($workflow, $approverEmployee?->id);
            if ($subordinateIds !== [] && in_array((int) $request->employee_id, $subordinateIds, true)) {
                return true;
            }

            $empScopeId = $workflow->getAttribute('employee_id');
            $branchScopeId = $workflow->getAttribute('branch_id');
            $deptScopeId = $workflow->getAttribute('department_id');
            $desigScopeId = $workflow->getAttribute('designation_id');

            if ($empScopeId && (int) $request->employee_id !== (int) $empScopeId) {
                continue;
            }
            if ($branchScopeId && (int) $request->branch_id !== (int) $branchScopeId) {
                continue;
            }
            if ($deptScopeId || $desigScopeId) {
                $targetEmployee ??= $request->employee ?: Employee::query()->find($request->employee_id);
                if (! $targetEmployee) {
                    continue;
                }
                if ($deptScopeId && (int) $targetEmployee->department_id !== (int) $deptScopeId) {
                    continue;
                }
                if ($desigScopeId && (int) $targetEmployee->designation_id !== (int) $desigScopeId) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get employee IDs of approvers at levels strictly below the given approver in a workflow.
     *
     * @return array<int>
     */
    protected static function getSubordinateApproverIdsInWorkflow(LeaveApprovalWorkflow $workflow, ?int $approverEmployeeId): array
    {
        if (! $approverEmployeeId) {
            return [];
        }

        $orderedLevels = $workflow->levels->values();
        $myHighestIndex = -1;

        foreach ($orderedLevels as $idx => $level) {
            if ((int) $level->approver_employee_id === (int) $approverEmployeeId) {
                $myHighestIndex = $idx;
            }
        }

        if ($myHighestIndex <= 0) {
            return [];
        }

        $subordinateIds = [];
        foreach ($orderedLevels as $idx => $level) {
            if ($idx < $myHighestIndex && (int) $level->approver_employee_id !== (int) $approverEmployeeId) {
                $subordinateIds[] = (int) $level->approver_employee_id;
            }
        }

        return array_values(array_unique($subordinateIds));
    }

    /**
     * Resolve the Employee model from User or Employee.
     */
    public static function resolveEmployee(User|Employee|null $user): ?Employee
    {
        if ($user instanceof Employee) {
            return $user;
        }

        if ($user instanceof User) {
            return $user->employee;
        }

        return null;
    }
}
