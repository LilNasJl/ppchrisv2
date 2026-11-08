<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodEmployeeAdjustment;
use App\Services\PayrollCalculator;
use Tests\TestCase;

class PayrollWorkDaysCountingTest extends TestCase
{
    private function testCalculator(): PayrollCalculator
    {
        return new class extends PayrollCalculator
        {
            protected function payrollAdjustment(Employee $employee, PayrollPeriod $period): ?PayrollPeriodEmployeeAdjustment
            {
                return null;
            }

            protected function loanPaymentDeduction(Employee $employee, PayrollPeriod $period): float
            {
                return 0.0;
            }

            protected function deductions(Employee $employee): array
            {
                return [
                    'uniform' => 0.0,
                    'other_deductions' => 0.0,
                    'sss_loan' => 0.0,
                    'sss_ee' => 0.0,
                    'hdmf_loan' => 0.0,
                    'hdmf_ee' => 0.0,
                    'phic_ee' => 0.0,
                ];
            }

            protected function dtrs(Employee $employee, PayrollPeriod $period): \Illuminate\Support\Collection
            {
                return collect($employee->testDtrs ?? []);
            }
        };
    }

    public function test_monthly_employee_saturday_absence_deducts_half_day(): void
    {
        $employee = new Employee([
            'rate_type' => 'monthly',
            'monthly_rate' => 26000.00,
            'daily_rate' => 1000.00,
        ]);

        $period = new PayrollPeriod([
            'id' => 1,
            'date_start' => '2026-10-26',
            'date_end' => '2026-11-10',
        ]);

        $satAbsence = (new Dtr)->forceFill([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-10-31', // Saturday
            'schedule_type' => 'Absent',
            'day_part' => 'whole_day',
            'is_absent' => true,
            'absence_minutes' => 180,
        ]);

        $employee->testDtrs = [$satAbsence];
        $employee->setRelation('designation', null);
        $employee->setRelation('department', null);
        $employee->setRelation('branch', null);
        $employee->setRelation('employeeDeductions', collect());

        $settings = new PayrollCalculationSetting([
            'regular_work_days_per_month' => 26,
            'regular_half_month_days' => 13,
            'work_hours_per_day' => 8,
            'half_day_work_day_value' => 0.5,
        ]);
        $employee->setRelation('preloadedPayrollSettings', $settings);

        $calculator = $this->testCalculator();
        $row = $calculator->row($employee, $period);

        // Standard 13 days - 3 hours (180 mins) Saturday absence = 12.63 days worked
        $this->assertEquals(12.63, $row['days_worked']);
        $this->assertEquals(0.0, $row['halfday']);
        $this->assertEquals(375.0, $row['absent']); // 3 hours * (1000/8) = 375
    }

    public function test_daily_employee_partial_shift_awards_half_day_pay(): void
    {
        $employee = new Employee([
            'rate_type' => 'daily',
            'daily_rate' => 600.00,
        ]);

        $period = new PayrollPeriod([
            'id' => 2,
            'date_start' => '2026-10-26',
            'date_end' => '2026-11-10',
        ]);

        // Employee worked 4 hours of an 8-hour Shift1
        $halfShift = (new Dtr)->forceFill([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-10-27',
            'time_in' => '04:30:00',
            'date_out' => '2026-10-27',
            'time_out' => '08:30:00',
            'schedule_type' => 'Shift1',
            'schedule_start' => '04:30:00',
            'schedule_end' => '12:30:00',
            'credited_work_hrs' => 240,
            'undertime' => 240,
            'daily_rate' => 600.00,
            'is_absent' => false,
        ]);

        $employee->testDtrs = [$halfShift];
        $employee->setRelation('designation', null);
        $employee->setRelation('department', null);
        $employee->setRelation('branch', null);
        $employee->setRelation('employeeDeductions', collect());

        $settings = new PayrollCalculationSetting([
            'regular_work_days_per_month' => 26,
            'regular_half_month_days' => 13,
            'work_hours_per_day' => 8,
            'half_day_work_day_value' => 0.5,
        ]);
        $employee->setRelation('preloadedPayrollSettings', $settings);

        $calculator = $this->testCalculator();
        $row = $calculator->row($employee, $period);

        $this->assertEquals(0.5, $row['days_worked']);
        $this->assertEquals(300.0, $row['total_pay']); // 0.5 * 600 = 300
    }
}
