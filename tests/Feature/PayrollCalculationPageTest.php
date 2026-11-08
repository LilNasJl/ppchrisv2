<?php

namespace Tests\Feature;

use App\Filament\Pages\PayrollCalculation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodEmployeeAdjustment;
use App\Models\User;
use App\Services\EmployeeDeductionService;
use App\Services\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollCalculationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_calculation_page_is_accessible_to_admin_and_hr_but_forbidden_to_employees(): void
    {
        $admin = User::query()->where('username', 'masteradmin')->first()
            ?? User::withoutEvents(fn (): User => User::query()->create([
                'name' => 'Master Admin',
                'username' => 'masteradmin',
                'email' => 'masteradmin@example.test',
                'password' => 'password',
                'role' => 'admin',
            ]));

        $employeeUser = User::withoutEvents(fn (): User => User::query()->create([
            'name' => 'Regular Employee',
            'username' => 'emp01',
            'email' => 'emp01@example.test',
            'password' => 'password',
            'role' => 'employee',
        ]));

        $period = PayrollPeriod::query()->create([
            'title' => 'Oct 11 - 25, 2026',
            'date_start' => '2026-10-11',
            'date_end' => '2026-10-25',
            'date_payout' => '2026-10-30',
            'is_locked' => false,
        ]);

        $this->actingAs($admin);
        $this->assertTrue(PayrollCalculation::canAccess());
        $this->get('/hr/payroll-calculation?periodId='.$period->publicKey())
            ->assertOk();

        $this->actingAs($employeeUser);
        $this->assertFalse(PayrollCalculation::canAccess());
    }

    public function test_employee_payroll_calculation_values_can_be_overridden_while_preserving_locked_formulas(): void
    {
        $branch = Branch::query()->create([
            'branch_name' => 'MAIN OFFICE',
            'branch_address' => 'Test Address',
        ]);
        $department = Department::query()->create([
            'name' => 'Operations',
            'branch_id' => $branch->id,
        ]);
        $designation = Designation::query()->create([
            'title' => 'Staff',
            'rate_per_hour' => 0,
        ]);
        $empUser = User::withoutEvents(fn (): User => User::query()->create([
            'name' => 'Juan Dela Cruz',
            'username' => 'PF-0999',
            'email' => 'juan@example.test',
            'password' => 'password',
            'role' => 'employee',
        ]));
        $employee = Employee::withoutEvents(fn (): Employee => Employee::query()->create([
            'user_id' => $empUser->id,
            'uid' => 'PF-0999',
            'fingerprint_id' => '999',
            'firstname' => 'Juan',
            'lastname' => 'Dela Cruz',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_status' => 'Regular',
            'rate_type' => 'monthly',
            'monthly_rate' => 26000,
            'allowance' => 500,
        ]));

        $period = PayrollPeriod::query()->create([
            'title' => 'Oct 26 - Nov 10, 2026',
            'date_start' => '2026-10-26',
            'date_end' => '2026-11-10',
            'date_payout' => '2026-11-15',
            'is_locked' => false,
        ]);

        $deductionService = app(EmployeeDeductionService::class);
        $deductionService->updatePermanentRemittance($period, $employee, PayrollCalculator::DEFAULT_DEDUCTION_TITLES['sss_ee'], '500.00');
        $deductionService->updatePermanentRemittance($period, $employee, PayrollCalculator::DEFAULT_DEDUCTION_TITLES['hdmf_ee'], '100.00');
        $deductionService->updatePermanentRemittance($period, $employee, PayrollCalculator::DEFAULT_DEDUCTION_TITLES['phic_ee'], '250.00');

        $calculator = app(PayrollCalculator::class);
        $autoRow = $calculator->row($employee, $period);

        $this->assertFalse($autoRow['is_adjusted']);
        $this->assertEmpty($autoRow['overridden_fields']);
        $this->assertEquals(500.0, $autoRow['allowance']);
        $this->assertEquals(500.0, $autoRow['sss_ee']);

        // Override individual calculation values while keeping formulas locked
        PayrollPeriodEmployeeAdjustment::query()->updateOrCreate(
            [
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
            ],
            [
                'days_worked_override' => 13.0,
                'allowance_override' => 1000.0,
                'overtime_amount_override' => 450.0,
                'late_override' => 100.0,
                'absent_override' => 0.0,
                'sss_ee_override' => 600.0,
                'salary_adjustment' => 200.0,
                'shortages' => 50.0,
                'notes' => 'Verified DTR and statutory adjustment',
            ]
        );

        $adjustedRow = $calculator->row($employee->fresh(), $period);

        $this->assertTrue($adjustedRow['is_adjusted']);
        $this->assertContains('days_worked', $adjustedRow['overridden_fields']);
        $this->assertContains('allowance', $adjustedRow['overridden_fields']);
        $this->assertContains('overtime_amount', $adjustedRow['overridden_fields']);
        $this->assertContains('late', $adjustedRow['overridden_fields']);
        $this->assertContains('absent', $adjustedRow['overridden_fields']);
        $this->assertContains('sss_ee', $adjustedRow['overridden_fields']);

        // System values are preserved for reference/reset
        $this->assertEquals(500.0, $adjustedRow['system_values']['allowance']);
        $this->assertEquals(500.0, $adjustedRow['system_values']['sss_ee']);

        // Formulas recalculate from the overridden values:
        // Base Pay = 13,000.00 (26,000 * 13 / 26)
        // Gross Pay = Base(13,000) + Adj(200) + Allowance(1,000) + OT(450) = 14,650.00
        // Deductions = Late(100) + Absent(0) + Shortages(50) + SSS_EE(600) + HDMF_EE(100) + PHIC_EE(250) = 1,100.00
        // Net Pay = 14,650.00 - 1,100.00 = 13,550.00
        $this->assertEquals(13000.0, $adjustedRow['base_pay']);
        $this->assertEquals(14650.0, $adjustedRow['gross_pay']);
        $this->assertEquals(1100.0, $adjustedRow['total_deductions']);
        $this->assertEquals(13550.0, $adjustedRow['net_pay']);
        $this->assertSame('Verified DTR and statutory adjustment', $adjustedRow['adjustment_notes']);
    }

    public function test_admin_can_save_period_calculation_rules_and_reset_to_defaults(): void
    {
        $admin = User::withoutEvents(fn (): User => User::query()->create([
            'name' => 'HR Admin',
            'username' => 'hradmin_calc',
            'email' => 'hradmin_calc@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]));

        $period = PayrollPeriod::query()->create([
            'title' => 'Nov 11 - 25, 2026',
            'date_start' => '2026-11-11',
            'date_end' => '2026-11-25',
            'date_payout' => '2026-11-30',
            'is_locked' => false,
        ]);

        $this->actingAs($admin);

        Livewire::test(PayrollCalculation::class, ['periodId' => $period->publicKey()])
            ->assertSet('periodId', $period->id)
            ->set('regular_work_days_per_month', '25')
            ->set('regular_half_month_days', '12.5')
            ->set('work_hours_per_day', '8')
            ->set('overtime_rate_multiplier', '1.25')
            ->call('save')
            ->assertHasNoErrors();

        $savedSetting = PayrollCalculationSetting::forPeriod($period);
        $this->assertEquals(25.0, (float) $savedSetting->regular_work_days_per_month);
        $this->assertEquals(12.5, (float) $savedSetting->regular_half_month_days);
        $this->assertEquals(1.25, (float) $savedSetting->overtime_rate_multiplier);

        // Test reset to defaults
        Livewire::test(PayrollCalculation::class, ['periodId' => $period->publicKey()])
            ->call('resetToDefaults')
            ->assertSet('regular_work_days_per_month', '26')
            ->assertSet('regular_half_month_days', '13')
            ->assertSet('overtime_rate_multiplier', '1');
    }
}
