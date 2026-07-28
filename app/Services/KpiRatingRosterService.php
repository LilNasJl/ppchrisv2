<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\KpiAccount;
use App\Models\KpiRatingCycle;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class KpiRatingRosterService
{
    public function createCycle(KpiAccount $account, CarbonInterface|string $ratingDate): KpiRatingCycle
    {
        $date = Carbon::parse($ratingDate, config('app.timezone', 'Asia/Manila'))->startOfDay();

        return DB::transaction(function () use ($account, $date): KpiRatingCycle {
            $cycle = KpiRatingCycle::query()->firstOrCreate(
                [
                    'kpi_account_id' => $account->getKey(),
                    'rating_date' => $date->toDateString(),
                ],
                [
                    'title' => 'KPI Rating - '.$date->format('M d, Y'),
                    'scope_type' => $account->scope_type,
                    'status' => 'draft',
                ],
            );

            if ($cycle->targets()->doesntExist()) {
                $this->snapshotTargets($cycle, $account);
            }

            return $cycle->loadCount('targets');
        });
    }

    public function snapshotTargets(KpiRatingCycle $cycle, KpiAccount $account): void
    {
        match ($account->scope_type) {
            KpiAccount::SCOPE_BRANCH => $this->snapshotBranches($cycle, $account),
            KpiAccount::SCOPE_DEPARTMENT => $this->snapshotDepartmentEmployees($cycle, $account),
            KpiAccount::SCOPE_EMPLOYEE => $this->snapshotSelectedEmployees($cycle, $account),
            default => null,
        };
    }

    protected function snapshotBranches(KpiRatingCycle $cycle, KpiAccount $account): void
    {
        $account->branches()
            ->orderBy('branch_name')
            ->get()
            ->each(function (Branch $branch) use ($cycle): void {
                $cycle->targets()->firstOrCreate(
                    ['target_key' => 'branch:'.$branch->getKey()],
                    [
                        'target_type' => KpiAccount::SCOPE_BRANCH,
                        'branch_id' => $branch->getKey(),
                        'target_name' => $branch->branch_name,
                        'branch_name' => $branch->branch_name,
                        'status' => 'pending',
                    ],
                );
            });
    }

    protected function snapshotDepartmentEmployees(KpiRatingCycle $cycle, KpiAccount $account): void
    {
        $departmentIds = $account->departments()->pluck('departments.id');

        $this->employeeQuery()
            ->whereIn('department_id', $departmentIds)
            ->get()
            ->each(fn (Employee $employee) => $this->snapshotEmployee($cycle, $employee));
    }

    protected function snapshotSelectedEmployees(KpiRatingCycle $cycle, KpiAccount $account): void
    {
        $employeeIds = $account->employees()->pluck('employees.id');

        $this->employeeQuery()
            ->whereIn('id', $employeeIds)
            ->get()
            ->each(fn (Employee $employee) => $this->snapshotEmployee($cycle, $employee));
    }

    protected function snapshotEmployee(KpiRatingCycle $cycle, Employee $employee): void
    {
        $cycle->targets()->firstOrCreate(
            ['target_key' => 'employee:'.$employee->getKey()],
            [
                'target_type' => KpiAccount::SCOPE_EMPLOYEE,
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->getKey(),
                'target_name' => $employee->full_name,
                'branch_name' => $employee->branch?->branch_name,
                'department_name' => $employee->department?->name,
                'designation_name' => $employee->designation?->title,
                'status' => 'pending',
            ],
        );
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->activeEmployment()
            ->with(['branch', 'department', 'designation'])
            ->orderBy('lastname')
            ->orderBy('middlename')
            ->orderBy('firstname');
    }
}
