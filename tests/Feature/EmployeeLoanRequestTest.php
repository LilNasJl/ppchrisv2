<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Loan;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\EmployeeLoanRequestService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class EmployeeLoanRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_request_does_not_create_active_loan_before_approval(): void
    {
        $employee = $this->employee();
        $period = $this->openPayrollPeriod();

        $request = app(EmployeeLoanRequestService::class)->create(
            $employee,
            $this->requestData($period),
        );

        $this->assertSame(EmployeeLoanRequest::STATUS_PENDING, $request->status);
        $this->assertDatabaseCount('employee_loans', 0);
    }

    public function test_employee_loan_page_action_submits_a_pending_request(): void
    {
        Notification::fake();

        $employee = $this->employee();
        $period = $this->openPayrollPeriod();

        $this->actingAs($employee->user);
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        Livewire::test(Loan::class)
            ->mountAction('requestLoan')
            ->set('mountedActions.0.data', $this->requestData($period))
            ->callMountedAction()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_loan_requests', [
            'employee_id' => $employee->id,
            'loan_type' => 'Company Loan',
            'status' => EmployeeLoanRequest::STATUS_PENDING,
        ]);
    }

    public function test_approval_creates_one_active_loan_and_links_the_request(): void
    {
        Notification::fake();

        $employee = $this->employee();
        $reviewer = $this->hrUser();
        $period = $this->openPayrollPeriod();
        $service = app(EmployeeLoanRequestService::class);
        $request = $service->create($employee, $this->requestData($period));

        $loan = $service->approve($request, $this->approvalData($period), $reviewer);

        $request->refresh();

        $this->assertSame(EmployeeLoan::STATUS_ACTIVE, $loan->status);
        $this->assertSame(EmployeeLoanRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($loan->id, $request->approved_employee_loan_id);
        $this->assertDatabaseCount('employee_loans', 1);
    }

    public function test_approved_request_cannot_create_a_duplicate_loan(): void
    {
        Notification::fake();

        $employee = $this->employee();
        $reviewer = $this->hrUser();
        $period = $this->openPayrollPeriod();
        $service = app(EmployeeLoanRequestService::class);
        $request = $service->create($employee, $this->requestData($period));

        $service->approve($request, $this->approvalData($period), $reviewer);

        try {
            $service->approve($request, $this->approvalData($period), $reviewer);
            $this->fail('A reviewed request should not be approved twice.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('employee_loans', 1);
        }
    }

    public function test_rejection_records_review_without_creating_a_loan(): void
    {
        Notification::fake();

        $employee = $this->employee();
        $reviewer = $this->hrUser();
        $period = $this->openPayrollPeriod();
        $service = app(EmployeeLoanRequestService::class);
        $request = $service->create($employee, $this->requestData($period));

        $service->reject($request, 'Insufficient supporting information.', $reviewer);

        $request->refresh();

        $this->assertSame(EmployeeLoanRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('Insufficient supporting information.', $request->hr_comment);
        $this->assertDatabaseCount('employee_loans', 0);
    }

    public function test_payment_and_terms_must_cover_the_total_loan(): void
    {
        $employee = $this->employee();
        $period = $this->openPayrollPeriod();
        $data = $this->requestData($period);
        $data['loan_terms_months'] = 2;
        $data['payment_amount'] = 100;

        $this->expectException(ValidationException::class);

        app(EmployeeLoanRequestService::class)->create($employee, $data);
    }

    protected function employee(): Employee
    {
        $user = User::factory()->create([
            'username' => 'PF0001',
            'role' => 'employee',
            'is_disabled' => false,
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,
            'uid' => '0001',
            'firstname' => 'Juan',
            'middlename' => 'Santos',
            'lastname' => 'Dela Cruz',
            'employment_type' => 'Permanent',
            'rate_type' => 'monthly',
            'monthly_rate' => 26000,
        ]);
    }

    protected function hrUser(): User
    {
        return User::factory()->create([
            'username' => 'hrreviewer',
            'role' => 'hr',
            'is_disabled' => false,
        ]);
    }

    protected function openPayrollPeriod(): PayrollPeriod
    {
        return PayrollPeriod::query()->create([
            'title' => 'Jul 11 - 25, 2026',
            'date_start' => '2026-07-11',
            'date_end' => '2026-07-25',
            'date_payout' => '2026-07-31',
            'description' => 'Testing',
            'is_locked' => false,
        ]);
    }

    protected function requestData(PayrollPeriod $period): array
    {
        return [
            'loan_type' => 'Company Loan',
            'loan_amount' => 1000,
            'loan_interest' => 100,
            'loan_terms_months' => 11,
            'payment_amount' => 100,
            'schedule' => EmployeeLoan::SCHEDULE_EVERY_PAYROLL,
            'preferred_start_payroll_period_id' => $period->id,
            'reason' => 'Emergency expense',
        ];
    }

    protected function approvalData(PayrollPeriod $period): array
    {
        return [
            'loan_type' => 'Company Loan',
            'loan_date' => '2026-07-02',
            'loan_amount' => 1000,
            'loan_interest' => 100,
            'loan_terms_months' => 11,
            'payment_amount' => 100,
            'schedule' => EmployeeLoan::SCHEDULE_EVERY_PAYROLL,
            'amortization_start_payroll_period_id' => $period->id,
            'hr_comment' => 'Approved after review.',
        ];
    }
}
