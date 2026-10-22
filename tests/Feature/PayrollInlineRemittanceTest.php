<?php

namespace Tests\Feature;

use App\Livewire\PayrollDetailTable;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodEmployeeAdjustment;
use App\Models\User;
use App\Services\EmployeeDeductionService;
use App\Services\PayrollCalculator;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollInlineRemittanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_remittances_persist_to_employee_and_future_payroll_while_shortages_remain_period_specific(): void
    {
        [$employee, $current, $next] = $this->payrollScenario();
        $service = app(EmployeeDeductionService::class);
        $amounts = [
            'sss_loan' => '125.25',
            'sss_ee' => '275.00',
            'hdmf_loan' => '90.50',
            'hdmf_ee' => '100.00',
            'phic_ee' => '137.50',
        ];

        foreach ($amounts as $key => $amount) {
            $service->updatePermanentRemittance($current, $employee, PayrollCalculator::DEFAULT_DEDUCTION_TITLES[$key], $amount);
        }

        PayrollPeriodEmployeeAdjustment::query()->create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $current->id,
            'shortages' => 17.25,
        ]);

        foreach ($amounts as $key => $amount) {
            $employeeDeduction = EmployeeDeduction::query()
                ->where('employee_id', $employee->id)
                ->whereHas('deduction', fn ($query) => $query->where('title', PayrollCalculator::DEFAULT_DEDUCTION_TITLES[$key]))
                ->sole();

            $this->assertSame($amount, $employeeDeduction->amount);
            $this->assertTrue($employeeDeduction->active);
            $this->assertSame(Deduction::TERM_PERMANENT, $employeeDeduction->term_type);
        }

        $calculator = app(PayrollCalculator::class);
        $currentRow = $calculator->row($employee->fresh(), $current);
        $nextRow = $calculator->row($employee->fresh(), $next);

        foreach ($amounts as $key => $amount) {
            $this->assertSame((float) $amount, $currentRow[$key]);
            $this->assertSame((float) $amount, $nextRow[$key]);
        }

        $this->assertSame(17.25, $currentRow['shortages']);
        $this->assertSame(0.0, $nextRow['shortages']);
    }

    public function test_locked_period_and_invalid_amounts_cannot_change_permanent_deductions(): void
    {
        [$employee, $period] = $this->payrollScenario();
        $service = app(EmployeeDeductionService::class);
        $title = PayrollCalculator::DEFAULT_DEDUCTION_TITLES['sss_ee'];

        foreach (['-1', '12.345'] as $invalid) {
            try {
                $service->updatePermanentRemittance($period, $employee, $title, $invalid);
                $this->fail('Invalid remittance amount was accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseMissing('employee_deductions', ['employee_id' => $employee->id, 'amount' => $invalid]);
            }
        }

        $service->updatePermanentRemittance($period, $employee, $title, '275.00');
        $period->update(['is_locked' => true]);

        $this->expectException(ValidationException::class);
        try {
            $service->updatePermanentRemittance($period, $employee, $title, '300.00');
        } finally {
            $this->assertDatabaseHas('employee_deductions', [
                'employee_id' => $employee->id,
                'amount' => 275,
            ]);
        }
    }

    public function test_payroll_table_inline_edits_update_employee_deductions_but_not_permanent_shortages(): void
    {
        [$employee, $period, $next] = $this->payrollScenario();
        $admin = User::query()->where('username', 'masteradmin')->firstOrFail();
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        $table = Livewire::actingAs($admin)->test(PayrollDetailTable::class, [
            'periodId' => $period->id,
            'branchId' => $employee->branch_id,
            'initialPreset' => 'all',
        ]);
        $adjustment = PayrollPeriodEmployeeAdjustment::query()
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->sole();

        foreach (['sss_loan', 'sss_ee', 'hdmf_loan', 'hdmf_ee', 'phic_ee'] as $column) {
            $this->assertInstanceOf(TextInputColumn::class, $table->instance()->getTable()->getColumn($column));
        }

        $amounts = [
            'sss_loan' => '125.25',
            'sss_ee' => '275.00',
            'hdmf_loan' => '90.50',
            'hdmf_ee' => '100.00',
            'phic_ee' => '137.50',
        ];

        foreach ($amounts as $column => $amount) {
            $table->call('updateTableColumnState', $column, (string) $adjustment->id, $amount)
                ->assertHasNoErrors();
        }

        $table->call('updateTableColumnState', 'shortages', (string) $adjustment->id, '20.00')
            ->assertHasNoErrors();

        $nextRow = app(PayrollCalculator::class)->row($employee->fresh(), $next);
        foreach ($amounts as $column => $amount) {
            $this->assertSame((float) $amount, $nextRow[$column]);
        }
        $this->assertSame(0.0, app(PayrollCalculator::class)->row($employee->fresh(), $next)['shortages']);
        $this->assertSame('20.00', $adjustment->fresh()->shortages);

        $period->update(['is_locked' => true]);
        $table->call('updateTableColumnState', 'sss_ee', (string) $adjustment->id, '350.00');
        $this->assertSame(275.0, app(PayrollCalculator::class)->row($employee->fresh(), $next)['sss_ee']);
    }

    private function payrollScenario(): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Main Office',
            'branch_address' => 'Main Office',
            'mobile_no' => '09123456789',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '18:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);
        $user = User::factory()->create(['username' => 'PF9001', 'role' => 'employee']);
        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'uid' => 'PAYROLL-TEST-1',
            'firstname' => 'Payroll',
            'lastname' => 'Employee',
            'hired_date' => '2026-01-01',
            'employment_type' => 'Permanent',
            'rate_type' => 'monthly',
            'monthly_rate' => 26000,
            'branch_id' => $branch->id,
        ]);

        foreach (Deduction::REMITTANCE_TITLES as $title) {
            Deduction::query()->firstOrCreate(['title' => $title], [
                'description' => $title,
                'category' => Deduction::CATEGORY_REMITTANCE,
                'term_type' => Deduction::TERM_PERMANENT,
            ]);
        }

        $current = PayrollPeriod::query()->create([
            'title' => 'Oct 1 - 15, 2026',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-15',
            'date_payout' => '2026-10-20',
            'is_locked' => false,
        ]);
        $next = PayrollPeriod::query()->create([
            'title' => 'Oct 16 - 31, 2026',
            'date_start' => '2026-10-16',
            'date_end' => '2026-10-31',
            'date_payout' => '2026-11-05',
            'is_locked' => false,
        ]);

        return [$employee, $current, $next];
    }
}
