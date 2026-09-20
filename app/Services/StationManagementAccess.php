<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;

class StationManagementAccess
{
    /**
     * Determine if the user or employee is authorized to access station management.
     */
    public static function canAccessStationManagement(User|Employee|null $user): bool
    {
        $employee = static::resolveEmployee($user);

        if (! $employee) {
            return false;
        }

        if ($employee->trashed()) {
            return false;
        }

        if ((bool) $employee->is_station_manager) {
            return true;
        }

        return count(static::getManagedBranchIds($employee)) > 0;
    }

    /**
     * Get the list of branch IDs managed by this user/employee.
     *
     * @return array<int>
     */
    public static function getManagedBranchIds(User|Employee|null $user): array
    {
        $employee = static::resolveEmployee($user);

        if (! $employee) {
            return [];
        }

        $managed = $employee->managed_branches;

        if (is_string($managed)) {
            $managed = json_decode($managed, true);
        }

        $branchIds = collect(is_array($managed) ? $managed : [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['branch_id'] ?? null))
            ->map(fn (array $item): int => (int) $item['branch_id'])
            ->values();

        // Fallback: If marked as station manager but managed_branches list is empty,
        // scope to their primary assigned branch if present.
        if ($branchIds->isEmpty() && (bool) $employee->is_station_manager && $employee->branch_id) {
            $branchIds->push((int) $employee->branch_id);
        }

        return $branchIds->unique()->values()->all();
    }

    /**
     * Get branch metadata array (branch_id, branch_name) managed by this user/employee.
     *
     * @return array<int, array{branch_id: int, branch_name: string}>
     */
    public static function getAssignedBranches(User|Employee|null $user): array
    {
        $employee = static::resolveEmployee($user);

        if (! $employee) {
            return [];
        }

        $branchIds = static::getManagedBranchIds($employee);

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
     * Check if user/employee is authorized to manage a specific target employee.
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

        return static::canManageBranch($user, (int) $targetEmployee->branch_id);
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
