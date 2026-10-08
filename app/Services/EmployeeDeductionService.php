<?php

namespace App\Services;

use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use Illuminate\Support\Collection;

class EmployeeDeductionService
{
    public function defaultDeductions(): Collection
    {
        return Deduction::query()
            ->whereIn('category', [Deduction::CATEGORY_COMPANY, Deduction::CATEGORY_REMITTANCE])
            ->get();
    }

    public function ensureDefaultDeductions(Employee $employee, ?Collection $defaultDeductions = null): void
    {
        $deductions = $defaultDeductions ?? $this->defaultDeductions();

        if ($deductions->isEmpty()) {
            return;
        }

        if ($employee->relationLoaded('employeeDeductions')) {
            $existingIds = $employee->employeeDeductions
                ->pluck('deduction_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $missing = $deductions->reject(
                fn (Deduction $deduction): bool => in_array((int) $deduction->id, $existingIds, true)
            );

            if ($missing->isEmpty()) {
                return;
            }

            foreach ($missing as $deduction) {
                $created = $this->ensureEmployeeDeduction($employee, $deduction);
                $created->setRelation('deduction', $deduction);
                $employee->employeeDeductions->push($created);
            }

            return;
        }

        $deductions->each(fn (Deduction $deduction) => $this->ensureEmployeeDeduction($employee, $deduction));
    }

    public function ensureEmployeeDeduction(Employee $employee, Deduction $deduction): EmployeeDeduction
    {
        return EmployeeDeduction::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'deduction_id' => $deduction->id,
            ],
            [
                'amount' => 0,
                'term_type' => $deduction->term_type ?: Deduction::TERM_PERMANENT,
                'term_periods' => $deduction->term_type === Deduction::TERM_FIXED ? $deduction->term_periods : null,
                'remaining_terms' => $deduction->term_type === Deduction::TERM_FIXED ? $deduction->term_periods : null,
                'active' => true,
            ],
        );
    }

    public function activeEmployeeDeductions(Employee $employee, ?Collection $defaultDeductions = null): Collection
    {
        $this->ensureDefaultDeductions($employee, $defaultDeductions);

        if (
            $employee->relationLoaded('employeeDeductions')
            && $employee->employeeDeductions->every(fn (EmployeeDeduction $ed): bool => $ed->relationLoaded('deduction'))
        ) {
            return $employee->employeeDeductions
                ->filter(fn (EmployeeDeduction $employeeDeduction): bool => (bool) $employeeDeduction->active
                    && $employeeDeduction->deduction !== null
                    && $employeeDeduction->isActiveForPayroll())
                ->values();
        }

        return $employee->employeeDeductions()
            ->with('deduction')
            ->where('active', true)
            ->whereHas('deduction')
            ->get()
            ->filter(fn (EmployeeDeduction $employeeDeduction): bool => $employeeDeduction->isActiveForPayroll())
            ->values();
    }
}
