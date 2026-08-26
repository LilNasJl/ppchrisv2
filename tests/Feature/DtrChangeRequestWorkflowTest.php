<?php

namespace Tests\Feature;

use App\Filament\SicRc\Pages\DtrChangeRequests as SicRcDtrChangeRequests;
use App\Models\Branch;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use App\Services\DtrChangeRequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class DtrChangeRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_routes_to_the_active_sicrc_owner_without_modifying_dtr(): void
    {
        [$employee, $period, $owner] = $this->context();

        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));

        $this->assertSame($owner->id, $request->assigned_sic_rc_account_id);
        $this->assertSame($employee->branch_id, $request->branch_id);
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertDatabaseCount('dtrs', 0);
        $this->assertDatabaseCount('employee_visible_dtrs', 0);
    }

    public function test_submission_rejects_dates_outside_the_payroll_period(): void
    {
        [$employee, $period] = $this->context();
        $data = $this->requestData($period);
        $data['date_to'] = '2026-08-26';

        $this->expectException(ValidationException::class);

        app(DtrChangeRequestService::class)->submit($employee, $data);
    }

    public function test_submission_requires_exactly_one_active_branch_owner(): void
    {
        [$employee, $period, $owner] = $this->context();
        $owner->update(['is_active' => false]);

        $this->expectException(ValidationException::class);

        app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));
    }

    public function test_identical_pending_request_cannot_be_submitted_twice(): void
    {
        [$employee, $period] = $this->context();
        $data = $this->requestData($period);
        app(DtrChangeRequestService::class)->submit($employee, $data);

        $this->expectException(ValidationException::class);

        app(DtrChangeRequestService::class)->submit($employee, $data);
    }

    public function test_assigned_sicrc_account_can_approve_a_pending_request(): void
    {
        [$employee, $period, $owner] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));

        $reviewed = app(DtrChangeRequestService::class)->approve($request, $owner, 'Verified against the biometric log.');

        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $reviewed->status);
        $this->assertSame($owner->id, $reviewed->reviewed_by_sic_rc_account_id);
        $this->assertNotNull($reviewed->reviewed_at);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_unassigned_sicrc_account_cannot_review_the_request(): void
    {
        [$employee, $period] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));
        $other = SicRcAccount::query()->create([
            'username' => 'other-reviewer',
            'password' => 'password',
            'biometric_devices' => [['branch_id' => $this->branch('Other Branch')->id, 'branch_name' => 'Other Branch']],
            'is_active' => true,
        ]);

        $this->expectException(AuthorizationException::class);

        app(DtrChangeRequestService::class)->approve($request, $other);
    }

    public function test_reviewed_request_cannot_be_decided_again(): void
    {
        [$employee, $period, $owner] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));
        app(DtrChangeRequestService::class)->reject($request, $owner, 'The punches are already correct.');

        $this->expectException(ValidationException::class);

        app(DtrChangeRequestService::class)->approve($request, $owner);
    }

    public function test_rejection_requires_reviewer_remarks(): void
    {
        [$employee, $period, $owner] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));

        $this->expectException(ValidationException::class);

        app(DtrChangeRequestService::class)->reject($request, $owner, '');
    }

    public function test_reviewed_request_is_unseen_until_employee_opens_change_requests(): void
    {
        [$employee, $period, $owner] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));
        app(DtrChangeRequestService::class)->approve($request, $owner, 'Approved.');

        $this->assertSame(1, DtrChangeRequest::query()
            ->where('employee_id', $employee->id)
            ->reviewed()
            ->unseenByEmployee()
            ->count());

        DtrChangeRequest::query()
            ->where('employee_id', $employee->id)
            ->reviewed()
            ->unseenByEmployee()
            ->update(['employee_seen_at' => now()]);

        $this->assertSame(0, DtrChangeRequest::query()
            ->where('employee_id', $employee->id)
            ->reviewed()
            ->unseenByEmployee()
            ->count());
    }

    public function test_sicrc_navigation_badge_counts_pending_requests_for_assigned_branches(): void
    {
        [$employee, $period, $owner] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));
        $this->actingAs($owner, 'sicrc');

        $this->assertSame('1', SicRcDtrChangeRequests::getNavigationBadge());

        app(DtrChangeRequestService::class)->approve($request, $owner);

        $this->assertNull(SicRcDtrChangeRequests::getNavigationBadge());
    }

    private function context(): array
    {
        $branch = $this->branch('Test Branch');
        $employee = Employee::query()->create([
            'uid' => '0001',
            'firstname' => 'Juan',
            'middlename' => 'Dela',
            'lastname' => 'Cruz',
            'branch_id' => $branch->id,
            'employment_type' => 'Permanent',
            'rate_type' => 'daily',
            'daily_rate' => 500,
        ]);
        $period = PayrollPeriod::query()->create([
            'title' => 'Aug 11 - 25, 2026',
            'date_start' => '2026-08-11',
            'date_end' => '2026-08-25',
            'date_payout' => '2026-08-31',
            'description' => 'Test period',
            'is_locked' => false,
        ]);
        $owner = SicRcAccount::query()->create([
            'username' => 'branch-reviewer',
            'password' => 'password',
            'biometric_devices' => [['branch_id' => $branch->id, 'branch_name' => $branch->branch_name]],
            'is_active' => true,
        ]);

        return [$employee, $period, $owner];
    }

    private function branch(string $name): Branch
    {
        return Branch::query()->create([
            'branch_name' => $name,
            'branch_address' => 'Test Address',
            'mobile_no' => '09123456789',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '18:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);
    }

    private function requestData(PayrollPeriod $period): array
    {
        return [
            'payroll_period_id' => $period->id,
            'date_from' => '2026-08-20',
            'date_to' => '2026-08-20',
            'request_type' => DtrChangeRequest::TYPE_MISSING_PUNCH,
            'description' => 'My time out is missing from the D.T.R.',
        ];
    }
}
