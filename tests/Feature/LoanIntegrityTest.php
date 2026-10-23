<?php

namespace Tests\Feature;

use App\Filament\Pages\LoanManagement;
use App\Livewire\LoanLedgerTable;
use App\Livewire\LoanRequestManagementTable;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanAdjustment;
use App\Models\EmployeeLoanPayment;
use App\Models\PayrollPeriod;
use App\Models\PayrollSnapshot;
use App\Models\User;
use App\Services\EmployeeLoanRequestService;
use App\Services\LoanAdjustmentService;
use App\Services\LoanTermsService;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriodLockService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LoanIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $uid = 'LOAN'): Employee
    {
        return Employee::create(['uid' => $uid, 'firstname' => $uid, 'employment_type' => 'Permanent']);
    }

    private function period(int $month, bool $first = true): PayrollPeriod
    {
        return PayrollPeriod::create([
            'title' => 'Loan '.$month.($first ? ' first' : ' second'),
            'date_start' => $first ? sprintf('2026-%02d-26', $month - 1) : sprintf('2026-%02d-11', $month),
            'date_end' => sprintf('2026-%02d-%02d', $month, $first ? 10 : 25),
            'date_payout' => sprintf('2026-%02d-%02d', $month, $first ? 15 : 28), 'is_locked' => false,
        ]);
    }

    private function loan(Employee $employee, PayrollPeriod $period, float $amount = 1000, int $terms = 3, string $schedule = EmployeeLoan::SCHEDULE_FIRST_QUINCENA): EmployeeLoan
    {
        return EmployeeLoan::create([
            'employee_id' => $employee->id, 'loan_date' => '2026-01-01', 'loan_amount' => $amount,
            'interest_rate' => 0, 'loan_terms_months' => $terms, 'terms_basis' => 'calendar_month',
            'schedule' => $schedule, 'amortization_start_payroll_period_id' => $period->id,
        ]);
    }

    private function mockPayroll(Employee $employee): void
    {
        $calculator = Mockery::mock(PayrollCalculator::class);
        $calculator->shouldReceive('snapshotPeriod')->andReturnNull();
        $calculator->shouldReceive('includedEmployeeIds')->andReturn(collect([$employee->id]));
        $this->app->instance(PayrollCalculator::class, $calculator);
    }

    private function loanManager(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->givePermissionTo(Permission::findOrCreate('View:LoanManagement', 'web'));

        return $user;
    }

    public function test_active_loan_can_reschedule_only_future_payments(): void
    {
        $employee = $this->employee();
        $first = $this->period(2);
        $second = $this->period(3);
        $third = $this->period(4);
        $loan = $this->loan($employee, $first);
        $this->mockPayroll($employee);
        $service = app(PayrollPeriodLockService::class);
        $service->lock($first);

        app(LoanAdjustmentService::class)->adjust($loan, [
            'type' => 'reschedule', 'remaining_deductions' => 2,
            'effective_payroll_period_id' => $second->id,
            'reason' => 'Correct the remaining installments',
        ], $this->loanManager());

        $service->lock($second);
        $service->lock($third);
        $this->assertSame(333.33, (float) $loan->payments()->where('payroll_period_id', $first->id)->first()->amount);
        $this->assertSame(333.34, (float) $loan->payments()->where('payroll_period_id', $second->id)->first()->amount);
        $this->assertSame(333.33, (float) $loan->payments()->where('payroll_period_id', $third->id)->first()->amount);
        $this->assertSame(0.0, $loan->fresh()->balance_amount);
    }

    public function test_corrected_total_recalculates_remaining_payments_and_survives_relock(): void
    {
        $employee = $this->employee();
        $first = $this->period(2);
        $second = $this->period(3);
        $third = $this->period(4);
        $loan = $this->loan($employee, $first);
        $this->mockPayroll($employee);
        $service = app(PayrollPeriodLockService::class);
        $service->lock($first);

        app(LoanAdjustmentService::class)->adjust($loan, [
            'type' => 'correct_total', 'new_total' => 900,
            'remaining_deductions' => 2,
            'effective_payroll_period_id' => $second->id,
            'reason' => 'Correct the original total owed',
        ], $this->loanManager());

        $service->lock($second);
        $this->assertSame(283.34, (float) $loan->payments()->where('payroll_period_id', $second->id)->first()->amount);
        $service->unlock($second);
        $service->lock($second);
        $service->lock($third);
        $this->assertSame(333.33, (float) $loan->payments()->where('payroll_period_id', $first->id)->first()->amount);
        $this->assertSame(283.33, (float) $loan->payments()->where('payroll_period_id', $third->id)->first()->amount);
        $this->assertSame(900.0, $loan->fresh()->total_amount);
        $this->assertSame(900.0, (float) $loan->fresh()->paid_amount);
        $this->assertDatabaseCount('employee_loan_adjustments', 1);
    }

    public function test_corrected_loan_amount_is_audited_and_does_not_rewrite_locked_history(): void
    {
        $employee = $this->employee();
        $first = $this->period(2);
        $second = $this->period(3);
        $loan = $this->loan($employee, $first);
        $this->mockPayroll($employee);
        $service = app(PayrollPeriodLockService::class);
        $service->lock($first);

        $adjustment = app(LoanAdjustmentService::class)->adjust($loan, [
            'type' => 'correct_total', 'new_loan_amount' => 850, 'new_total' => 900,
            'remaining_deductions' => 2, 'effective_payroll_period_id' => $second->id,
            'reason' => 'Correct principal and total owed',
        ], $this->loanManager());

        $this->assertSame(1000.0, (float) $adjustment->previous_loan_amount);
        $this->assertSame(850.0, (float) $loan->fresh()->loan_amount);
        $this->assertSame(50.0, (float) $loan->fresh()->loan_interest);
        $this->assertSame(1000.0, $loan->fresh()->totalAmountForPeriod($first));
        $this->assertSame(900.0, $loan->fresh()->totalAmountForPeriod($second));
        $this->assertSame(333.33, (float) $loan->payments()->first()->amount);
        $service->lock($second);
        $this->assertSame(283.34, (float) $loan->payments()->where('payroll_period_id', $second->id)->first()->amount);
    }

    public function test_voided_history_can_be_corrected_without_erasing_audit_events(): void
    {
        $employee = $this->employee();
        $period = $this->period(2);
        $loan = $this->loan($employee, $period);
        $this->mockPayroll($employee);
        $service = app(PayrollPeriodLockService::class);
        $service->lock($period);
        $service->unlock($period);

        app(LoanAdjustmentService::class)->adjust($loan, [
            'type' => 'correct_total', 'new_total' => 900,
            'remaining_deductions' => 3,
            'effective_payroll_period_id' => $period->id,
            'reason' => 'Correct test loan total',
        ], $this->loanManager());

        $service->lock($period);
        $payment = $loan->payments()->first();
        $this->assertSame(300.0, (float) $payment->amount);
        $this->assertSame(['Posted', 'Voided', 'Posted'], $payment->events()->orderBy('id')->pluck('status')->all());
        $this->assertSame(600.0, $loan->fresh()->balance_amount);
    }

    public function test_earlier_payroll_cannot_unlock_while_later_loan_payment_is_posted(): void
    {
        $employee = $this->employee();
        $first = $this->period(2);
        $second = $this->period(3);
        $this->loan($employee, $first);
        $this->mockPayroll($employee);
        $service = app(PayrollPeriodLockService::class);
        $service->lock($first);
        $service->lock($second);

        $this->expectException(ValidationException::class);
        $service->unlock($first);
    }

    public function test_payroll_lock_rolls_back_when_snapshot_loan_deduction_disagrees(): void
    {
        $employee = $this->employee();
        $period = $this->period(2);
        $loan = $this->loan($employee, $period);
        $calculator = Mockery::mock(PayrollCalculator::class);
        $calculator->shouldReceive('includedEmployeeIds')->andReturn(collect([$employee->id]));
        $calculator->shouldReceive('snapshotPeriod')->once()->andReturnUsing(function (PayrollPeriod $lockedPeriod) use ($employee): void {
            PayrollSnapshot::create([
                'payroll_period_id' => $lockedPeriod->id,
                'employee_id' => $employee->id,
                'row_number' => 1,
                'data' => ['loan_payment' => 0],
            ]);
        });
        $this->app->instance(PayrollCalculator::class, $calculator);

        try {
            app(PayrollPeriodLockService::class)->lock($period);
            $this->fail('Expected snapshot reconciliation failure');
        } catch (ValidationException) {
            $this->assertFalse($period->fresh()->is_locked);
            $this->assertSame(0.0, (float) $loan->fresh()->paid_amount);
            $this->assertDatabaseCount('employee_loan_payments', 0);
        }
    }

    public function test_active_loan_with_payment_history_opens_adjustment_form(): void
    {
        $employee = $this->employee();
        $period = $this->period(2);
        $loan = $this->loan($employee, $period);
        $this->mockPayroll($employee);
        app(PayrollPeriodLockService::class)->lock($period);
        $this->actingAs($this->loanManager());
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::test(LoanManagement::class)
            ->call('mountTableAction', 'edit', (string) $loan->id)
            ->assertSet('mountedActions.0.name', 'edit')
            ->setTableActionData([
                'type' => EmployeeLoanAdjustment::TYPE_RESCHEDULE,
                'remaining_deductions' => 2,
                'effective_payroll_period_id' => $this->period(3)->id,
                'reason' => 'Correct the remaining repayment schedule.',
            ])
            ->callMountedTableAction()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_loan_adjustments', [
            'employee_loan_id' => $loan->id,
            'type' => EmployeeLoanAdjustment::TYPE_RESCHEDULE,
            'remaining_deductions' => 2,
        ]);
    }

    public function test_final_payment_collects_exact_balance_in_preview_and_posting(): void
    {
        $employee = $this->employee();
        $periods = collect([2, 3, 4])->map(fn ($month) => $this->period($month));
        $loan = $this->loan($employee, $periods->first());
        $this->mockPayroll($employee);
        foreach ($periods as $period) {
            app(PayrollPeriodLockService::class)->lock($period);
        }
        $this->assertSame(1000.0, (float) $loan->fresh()->paid_amount);
        $this->assertSame(0.0, $loan->fresh()->balance_amount);
        $this->assertSame('Paid', $loan->fresh()->status);
        $this->assertSame(333.34, (float) $loan->payments()->latest('id')->first()->amount);
        $preview = app(LoanTermsService::class)->preview($loan->getAttributes(), 'amortization_start_payroll_period_id');
        $this->assertSame(333.34, $preview['final_payment']);
    }

    public function test_lock_unlock_relock_preserves_all_audit_events_without_double_deduction(): void
    {
        $employee = $this->employee();
        $period = $this->period(2);
        $loan = $this->loan($employee, $period, 1000, 1);
        $this->mockPayroll($employee);
        $service = app(PayrollPeriodLockService::class);
        $service->lock($period);
        $service->lock($period);
        $service->unlock($period);
        $this->assertSame(0.0, (float) $loan->fresh()->paid_amount);
        $service->lock($period);
        $this->assertSame(1000.0, (float) $loan->fresh()->paid_amount);
        $this->assertDatabaseCount('employee_loan_payments', 1);
        $this->assertDatabaseCount('employee_loan_payment_events', 3);
        $this->assertSame(['Posted', 'Voided', 'Posted'], $loan->payments()->first()->events->pluck('status')->all());
    }

    public function test_original_terms_stay_protected_but_active_loan_can_be_soft_deleted(): void
    {
        $employee = $this->employee();
        $period = $this->period(2);
        $loan = $this->loan($employee, $period);
        $this->mockPayroll($employee);
        app(PayrollPeriodLockService::class)->lock($period);
        try {
            $loan->refresh()->update(['loan_amount' => 500]);
            $this->fail('Expected protected terms');
        } catch (ValidationException) {
            $this->assertSame(1000.0, (float) $loan->fresh()->loan_amount);
        }
        $loan->fresh()->delete();
        $this->assertSoftDeleted('employee_loans', ['id' => $loan->id]);
        $this->assertSame(333.33, (float) $loan->payments()->where('payroll_period_id', $period->id)->first()->amount);
        $next = $this->period(3);
        app(PayrollPeriodLockService::class)->lock($next);
        $this->assertDatabaseMissing('employee_loan_payments', ['employee_loan_id' => $loan->id, 'payroll_period_id' => $next->id]);
    }

    public function test_hr_delete_action_soft_deletes_active_loan_with_history(): void
    {
        $employee = $this->employee();
        $period = $this->period(2);
        $loan = $this->loan($employee, $period);
        $this->mockPayroll($employee);
        app(PayrollPeriodLockService::class)->lock($period);
        $this->actingAs($this->loanManager());
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::test(LoanManagement::class)
            ->callTableAction('delete', (string) $loan->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('employee_loans', ['id' => $loan->id]);
        $this->assertDatabaseHas('employee_loan_payments', [
            'employee_loan_id' => $loan->id,
            'payroll_period_id' => $period->id,
            'status' => EmployeeLoanPayment::STATUS_POSTED,
        ]);
    }

    public function test_legacy_deleted_loan_is_reversed_when_payroll_unlocks(): void
    {
        $employee = $this->employee();
        $period = $this->period(2);
        $loan = $this->loan($employee, $period);
        $this->mockPayroll($employee);
        $service = app(PayrollPeriodLockService::class);
        $service->lock($period);
        DB::table('employee_loans')->where('id', $loan->id)->update(['deleted_at' => now()]);
        $service->unlock($period);
        $this->assertSame(0.0, (float) EmployeeLoan::withTrashed()->find($loan->id)->paid_amount);
        $this->assertSame('Voided', EmployeeLoanPayment::first()->status);
    }

    public function test_employee_ledger_denies_other_employee_on_mount_and_locks_identifier(): void
    {
        $employee = $this->employee();
        $user = User::factory()->create(['username' => 'ledger-owner', 'role' => 'employee']);
        $employee->update(['user_id' => $user->id]);
        $period = $this->period(2);
        $own = $this->loan($employee, $period);
        $other = $this->loan($this->employee('OTHER'), $period, 9000);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('employee'));
        Livewire::test(LoanLedgerTable::class, ['loanId' => $other->id])->assertForbidden();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(LoanLedgerTable::class, ['loanId' => $own->id])->set('loanId', $other->id);
    }

    public function test_request_table_and_review_service_deny_employee_reviewers(): void
    {
        Notification::fake();
        $user = User::factory()->create(['username' => 'unprivileged', 'role' => 'employee']);
        $employee = $this->employee();
        $employee->update(['user_id' => $user->id]);
        $period = $this->period(2);
        $data = $this->data($period);
        $request = app(EmployeeLoanRequestService::class)->create($employee, $data);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('employee'));
        Livewire::test(LoanRequestManagementTable::class)->assertForbidden();
        $this->expectException(HttpException::class);
        app(EmployeeLoanRequestService::class)->approve($request, $data, $user);
    }

    private function data(PayrollPeriod $period): array
    {
        return ['loan_type' => 'Company Loan', 'loan_amount' => 1000, 'interest_rate' => 0, 'loan_terms_months' => 3,
            'payment_amount' => 333.33, 'schedule' => EmployeeLoan::SCHEDULE_FIRST_QUINCENA, 'reason' => 'Test',
            'amortization_start_payroll_period_id' => $period->id, 'preferred_start_payroll_period_id' => $period->id];
    }

    public function test_explicit_quincena_supports_alternative_period_dates(): void
    {
        $employee = $this->employee();
        $period = PayrollPeriod::create(['title' => 'Alternative', 'date_start' => '2026-02-01', 'date_end' => '2026-02-15', 'date_payout' => '2026-02-16', 'is_locked' => false, 'loan_quincena' => 'first']);
        $loan = $this->loan($employee, $period, 1000, 1);
        $this->assertSame(1000.0, $loan->paymentAmountForPeriod($period));
        $period->update(['loan_quincena' => 'second']);
        $this->assertSame(0.0, $loan->paymentAmountForPeriod($period));
    }

    public function test_shared_validation_rejects_zero_principal_fractional_terms_and_locked_start(): void
    {
        $period = $this->period(2);
        $service = app(LoanTermsService::class);
        foreach (['loan_amount' => 0, 'loan_terms_months' => 1.5, 'interest_rate' => 101] as $key => $badValue) {
            try {
                $service->validate(array_replace($this->data($period), [$key => $badValue]), 'amortization_start_payroll_period_id', 'calendar_month');
                $this->fail('Expected validation failure');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($key, $exception->errors());
            }
        }
        $period->update(['is_locked' => true]);
        $this->expectException(ValidationException::class);
        $service->validate($this->data($period), 'amortization_start_payroll_period_id', 'calendar_month');
    }

    public function test_legacy_terms_preserve_payroll_deduction_count(): void
    {
        $period = $this->period(2);
        $data = $this->data($period);
        $data['loan_terms_months'] = 4;
        $data['payment_amount'] = 250;
        $data['loan_interest'] = 0;
        $values = app(LoanTermsService::class)->validate($data, 'amortization_start_payroll_period_id', 'payroll_period');
        $this->assertSame(250.0, $values['payment_amount']);
        $this->assertSame(4, EmployeeLoan::scheduledDeductionsCount(4, 'Every Payroll', 'payroll_period'));
    }

    public function test_hr_create_shows_preview_opens_ledger_and_records_no_payment_yet(): void
    {
        $user = User::withoutEvents(fn () => User::firstOrCreate(['username' => 'masteradmin'], [
            'name' => 'Master Admin', 'email' => 'master-loan@example.test', 'password' => 'password', 'role' => 'admin',
        ]));
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('hr'));
        $employee = $this->employee();
        $period = $this->period(2);
        Livewire::test(LoanManagement::class)
            ->call('showLoanTab', 'information')
            ->assertSee('Amortization Preview')->assertSee('Create Loan')
            ->set('data', array_replace($this->data($period), ['employee_id' => $employee->id, 'loan_date' => '2026-01-01', 'terms_basis' => 'calendar_month']))
            ->call('createLoan')->assertHasNoErrors()->assertSet('activeLoanTab', 'list')
            ->assertSet('mountedActions.0.name', 'ledger');
        $this->assertDatabaseCount('employee_loans', 1);
        $this->assertDatabaseCount('employee_loan_payments', 0);
    }

    public function test_permissioned_hr_reviewer_cannot_approve_own_request(): void
    {
        $reviewer = User::factory()->create(['username' => 'self-review', 'role' => 'hr']);
        $reviewer->givePermissionTo(Permission::findOrCreate('View:LoanManagement', 'web'));
        $employee = $this->employee();
        $employee->update(['user_id' => $reviewer->id]);
        $data = $this->data($this->period(2));
        $service = app(EmployeeLoanRequestService::class);
        $request = $service->create($employee, $data);
        $this->expectException(ValidationException::class);
        $service->approve($request, $data, $reviewer);
    }

    public function test_existing_rounding_remainder_is_collected_without_rewriting_old_payments(): void
    {
        $employee = $this->employee();
        $periods = collect([2, 3, 4])->map(fn ($month) => $this->period($month));
        $loan = $this->loan($employee, $periods->first());
        foreach ($periods as $period) {
            EmployeeLoanPayment::create(['employee_loan_id' => $loan->id, 'payroll_period_id' => $period->id,
                'amount' => 333.33, 'balance_after' => 0.01, 'processed_at' => now(), 'status' => 'Posted']);
        }
        $loan->update(['paid_amount' => 999.99]);
        $this->mockPayroll($employee);
        app(PayrollPeriodLockService::class)->lock($this->period(5));
        $this->assertSame(0.0, $loan->fresh()->balance_amount);
        $this->assertSame(0.01, (float) $loan->payments()->latest('id')->first()->amount);
        $this->assertSame(3, $loan->payments()->where('amount', 333.33)->count());
    }

    public function test_interest_and_legacy_validation_have_consistent_expected_amounts(): void
    {
        $data = $this->data($this->period(2));
        $data['loan_amount'] = 10000;
        $data['interest_rate'] = 2;
        $data['schedule'] = EmployeeLoan::SCHEDULE_EVERY_PAYROLL;
        $values = app(LoanTermsService::class)->validate($data, 'amortization_start_payroll_period_id', 'calendar_month');
        $this->assertSame(600.0, $values['loan_interest']);
        $this->assertSame(1766.67, $values['payment_amount']);
        $preview = app(LoanTermsService::class)->preview($data, 'amortization_start_payroll_period_id');
        $this->assertSame(1766.65, $preview['final_payment']);
        $this->expectException(ValidationException::class);
        app(LoanTermsService::class)->validate($data, 'amortization_start_payroll_period_id', 'payroll_period');
    }
}
