<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodBranchExclusion;
use App\Models\PayrollPeriodEmployeeExclusion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollRosterExclusionService
{
    public function replaceBranches(PayrollPeriod $period, array $branchIds): void
    {
        $this->replace($period, $branchIds, Branch::class, PayrollPeriodBranchExclusion::class, 'branch_id', 'branch_ids');
    }

    public function replaceEmployees(PayrollPeriod $period, array $employeeIds): void
    {
        $this->replace($period, $employeeIds, Employee::class, PayrollPeriodEmployeeExclusion::class, 'employee_id', 'employee_ids');
    }

    /**
     * @param  class-string<Model>  $sourceModel
     * @param  class-string<Model>  $exclusionModel
     */
    private function replace(
        PayrollPeriod $period,
        array $selectedIds,
        string $sourceModel,
        string $exclusionModel,
        string $foreignKey,
        string $field
    ): void {
        foreach ($selectedIds as $id) {
            if ((! is_int($id) && (! is_string($id) || ! ctype_digit($id))) || (int) $id < 1) {
                throw ValidationException::withMessages([$field => 'Select valid roster entries.']);
            }
        }

        $ids = collect($selectedIds)->map(fn (int|string $id): int => (int) $id)->unique()->values()->all();

        DB::transaction(function () use ($period, $ids, $sourceModel, $exclusionModel, $foreignKey, $field): void {
            $currentPeriod = PayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();

            if ($currentPeriod->is_locked) {
                throw ValidationException::withMessages([$field => 'Unlock the payroll period before changing roster exclusions.']);
            }

            if ($sourceModel::query()->whereKey($ids)->count() !== count($ids)) {
                throw ValidationException::withMessages([$field => 'Select valid roster entries.']);
            }

            $exclusionModel::query()
                ->where('payroll_period_id', $currentPeriod->id)
                ->whereNotIn($foreignKey, $ids)
                ->delete();

            foreach ($ids as $id) {
                $exclusionModel::query()->firstOrCreate([
                    'payroll_period_id' => $currentPeriod->id,
                    $foreignKey => $id,
                ]);
            }
        });
    }
}
