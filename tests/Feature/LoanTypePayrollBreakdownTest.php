<?php

namespace Tests\Feature;

use App\Filament\Pages\LoanManagement;
use App\Filament\Pages\LoanTypes;
use App\Livewire\PayrollDetailTable;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Models\LoanType;
use App\Models\PayrollPeriod;
use App\Models\PayrollSnapshot;
use App\Models\User;
use App\Services\LoanPaymentBreakdown;
use App\Services\PayrollCalculator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LoanTypePayrollBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->givePermissionTo(Permission::findOrCreate('View:LoanManagement', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('View:EmployeePayroll', 'web'));

        return $user;
    }

    private function period(bool $locked = false): PayrollPeriod
    {
        return PayrollPeriod::create([
            'title' => 'Loan type test',
            'date_start' => '2026-10-11',
            'date_end' => '2026-10-25',
            'date_payout' => '2026-10-28',
            'is_locked' => $locked,
        ]);
    }

    private function loan(Employee $employee, PayrollPeriod $period, string $type, float $amount): EmployeeLoan
    {
        return EmployeeLoan::create([
            'employee_id' => $employee->id,
            'amortization_start_payroll_period_id' => $period->id,
            'loan_type' => $type,
            'loan_date' => '2026-10-01',
            'loan_amount' => $amount,
            'loan_interest' => 0,
            'loan_terms_months' => 2,
            'terms_basis' => EmployeeLoan::TERMS_BASIS_PAYROLL_PERIOD,
            'schedule' => EmployeeLoan::SCHEDULE_EVERY_PAYROLL,
            'payment_amount' => $amount / 2,
            'status' => EmployeeLoan::STATUS_ACTIVE,
        ]);
    }

    public function test_hr_can_add_a_loan_type_and_employee_cannot_access_catalog(): void
    {
        $this->actingAs($this->manager());
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::test(LoanTypes::class)
            ->callAction('addType', ['name' => 'Housing Loan'])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('loan_types', ['name' => 'Housing Loan']);
        $this->assertSame('Housing Loan', LoanType::options()['Housing Loan']);
        Livewire::test(LoanManagement::class)
            ->call('showLoanTab', 'information')
            ->assertSee('Housing Loan')
            ->assertSee('Add Loan Type');
        Livewire::test(PayrollDetailTable::class, [
            'periodId' => $this->period()->id,
            'showLoanTypes' => true,
            'showColumnPresets' => false,
            'initialPreset' => 'all',
        ])->assertSee('Housing Loan');

        $this->actingAs(User::factory()->create(['role' => 'employee']));
        $this->assertFalse(LoanTypes::canAccess());
    }

    public function test_breakdown_uses_scheduled_open_amounts_and_posted_locked_amounts(): void
    {
        $employee = Employee::create(['uid' => 'TYPE-1', 'firstname' => 'Loan', 'employment_type' => 'Permanent']);
        $other = Employee::create(['uid' => 'TYPE-2', 'firstname' => 'Other', 'employment_type' => 'Permanent']);
        $period = $this->period();
        LoanType::create(['name' => 'Housing Loan']);
        $company = $this->loan($employee, $period, 'Company Loan', 200);
        $housing = $this->loan($employee, $period, 'Housing Loan', 100);
        $this->loan($other, $period, 'Company Loan', 80);
        $service = app(LoanPaymentBreakdown::class);

        $open = $service->forEmployees($period, collect([$employee->id]));
        $this->assertSame(['Company Loan' => 100.0, 'Housing Loan' => 50.0], $open[$employee->id]);
        $columns = LoanType::query()->whereIn('name', ['Company Loan', 'Housing Loan'])->pluck('name', 'id')->all();
        $row = $service->addColumns($period, collect([['employee_id' => $employee->id, 'loan_payment' => 150]]), $columns)->first();
        $this->assertSame(150.0, array_sum(array_intersect_key($row, array_flip(array_map(fn ($id) => 'loan_type_'.$id, array_keys($columns))))));

        $company->delete();
        EmployeeLoanPayment::create([
            'employee_loan_id' => $company->id, 'payroll_period_id' => $period->id,
            'amount' => 100, 'balance_after' => 100, 'status' => EmployeeLoanPayment::STATUS_POSTED,
        ]);
        EmployeeLoanPayment::create([
            'employee_loan_id' => $housing->id, 'payroll_period_id' => $period->id,
            'amount' => 50, 'balance_after' => 50, 'status' => EmployeeLoanPayment::STATUS_POSTED,
        ]);
        $period->update(['is_locked' => true]);

        $locked = $service->forEmployees($period->fresh(), collect([$employee->id]));
        $this->assertSame($open[$employee->id], $locked[$employee->id]);
        $this->assertSame(150.0, array_sum($locked[$employee->id]));
        $this->assertArrayNotHasKey($other->id, $locked);
    }

    public function test_payroll_print_table_replaces_aggregate_loan_column_with_type_columns(): void
    {
        $html = view('filament.pages.partials.payroll-detail-table', [
            'rows' => collect(),
            'loanTypeColumns' => [1 => 'Company Loan', 2 => 'Housing Loan'],
        ])->render();

        $this->assertStringContainsString('<th>Company Loan</th>', $html);
        $this->assertStringContainsString('<th>Housing Loan</th>', $html);
        $this->assertStringNotContainsString('<th>Loan Payment</th>', $html);
    }

    public function test_employee_payroll_and_payslip_show_posted_amount_by_type(): void
    {
        $this->actingAs($this->manager());
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        $employee = Employee::create(['uid' => 'TYPE-PRINT', 'firstname' => 'Loan', 'employment_type' => 'Permanent']);
        $period = $this->period(true);
        $loan = $this->loan($employee, $period, 'Company Loan', 200);
        EmployeeLoanPayment::create([
            'employee_loan_id' => $loan->id, 'payroll_period_id' => $period->id,
            'amount' => 100, 'balance_after' => 100, 'status' => EmployeeLoanPayment::STATUS_POSTED,
        ]);
        $loan->delete();
        PayrollSnapshot::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'row_number' => 1,
            'data' => array_replace(
                array_fill_keys(array_keys(app(PayrollCalculator::class)->payrollHeaders()), 0),
                ['employee_id' => $employee->id, 'loan_payment' => 100, 'total_deductions' => 100, 'net_pay' => 900],
            ),
        ]);

        $this->get('/hr/employee-payroll?employeeId='.$employee->publicKey().'&periodId='.$period->publicKey())
            ->assertSuccessful()
            ->assertSee('<dt>Company Loan</dt>', false)
            ->assertDontSee('<dt>Loan Payment</dt>', false)
            ->assertSee('<th>Company Loan</th>', false);

        $this->get(route('payroll.payslip.print', [
            'period' => $period->publicKey(),
            'employee' => $employee->publicKey(),
        ]))
            ->assertSuccessful()
            ->assertSee('<td>Company Loan</td><td>100.00</td>', false)
            ->assertDontSee('<td>Loan Payment</td>', false);
    }
}
