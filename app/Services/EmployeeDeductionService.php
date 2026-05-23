<?php

namespace App\Services;

use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use Illuminate\Support\Collection;

class EmployeeDeductionService
{
    public function ensureDefaultDeductions(Employee $employee): void
    {
        Deduction::query()
            ->whereIn('category', [Deduction::CATEGORY_COMPANY, Deduction::CATEGORY_REMITTANCE])
            ->get()
            ->each(fn (Deduction $deduction) => $this->ensureEmployeeDeduction($employee, $deduction));
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

    public function activeEmployeeDeductions(Employee $employee): Collection
    {
        $this->ensureDefaultDeductions($employee);

        return $employee->employeeDeductions()
            ->with('deduction')
            ->where('active', true)
            ->whereHas('deduction')
            ->get()
            ->filter(fn (EmployeeDeduction $employeeDeduction): bool => $employeeDeduction->isActiveForPayroll())
            ->values();
    }
}
