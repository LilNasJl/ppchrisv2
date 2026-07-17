<?php

namespace App\Services;

use App\Models\Dtr;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class DtrRecordService
{
    public function query(Employee $employee, int $branchId, int $payrollPeriodId): Builder
    {
        $fingerprintId = $employee->fingerprint_id ?: $employee->uid;

        if (blank($fingerprintId) || $branchId < 1 || $payrollPeriodId < 1) {
            return Dtr::query()->whereRaw('1 = 0');
        }

        return Dtr::query()
            ->where('fingerprint_id', $fingerprintId)
            ->where('branch_id', $branchId)
            ->where('payroll_period_id', $payrollPeriodId);
    }
}
