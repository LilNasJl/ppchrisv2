<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Station\ReviewDtrChangeRequests;
use App\Models\Branch;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\DtrChangeRequestService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DtrChangeRequestWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
        Filament::setCurrentPanel(Filament::getPanel('employee'));
    }

    protected function createTestTables(): void
    {
        Schema::dropAllTables();

        Schema::create('counters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('uid')->default(0);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Test User');
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->default('secret');
            $table->string('remember_token', 100)->nullable();
            $table->string('role', 30)->default('employee');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_name');
            $table->string('branch_address')->nullable();
            $table->string('mobile_no')->nullable();
            $table->unsignedBigInteger('employee_id')->default(0);
            $table->unsignedInteger('no_of_shifts')->default(1);
            $table->time('reg_sched_start')->nullable();
            $table->time('reg_sched_end')->nullable();
            $table->boolean('is_24hrs')->default(false);
            $table->boolean('has_broken_time')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->date('date_start');
            $table->date('date_end');
            $table->date('date_payout');
            $table->text('description')->nullable();
            $table->string('attachment_path', 2048)->nullable();
            $table->string('attachment_original_name', 255)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payroll_calculation_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->decimal('regular_work_days_per_month', 8, 2)->default(26);
            $table->decimal('regular_half_month_days', 8, 2)->default(13);
            $table->decimal('work_hours_per_day', 8, 2)->default(8);
            $table->decimal('half_day_work_day_value', 8, 2)->default(0.5);
            $table->decimal('overtime_rate_multiplier', 8, 2)->default(1);
            $table->decimal('regular_holiday_rate', 8, 2)->default(200);
            $table->decimal('special_holiday_rate', 8, 2)->default(30);
            $table->integer('late_grace_minutes')->default(0);
            $table->decimal('holiday_overtime_premium_rate', 8, 2)->default(0);
            $table->boolean('unworked_regular_holiday_pay_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('uid', 20)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('company_id', 20)->nullable();
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->boolean('is_station_manager')->default(false);
            $table->json('managed_branches')->nullable();
            $table->string('employment_type')->default('Permanent');
            $table->string('employment_status')->default('Regular');
            $table->string('schedule_type')->default('regular');
            $table->string('rate_type')->default('daily');
            $table->decimal('daily_rate', 10, 2)->nullable()->default(0);
            $table->decimal('monthly_rate', 10, 2)->nullable()->default(0);
            $table->double('allowance')->default(0);
            $table->double('salary_adjustment')->default(0);
            $table->unsignedInteger('kids')->default(0);
            $table->double('leave_credits')->default(10);
            $table->double('birthday_leave_credits')->default(1);
            $table->unsignedInteger('leave_credits_year')->nullable();
            $table->date('end_status_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->nullable();
            $table->string('schedule_start')->nullable();
            $table->string('schedule_end')->nullable();
            $table->double('late')->nullable()->default(0);
            $table->double('undertime')->nullable()->default(0);
            $table->double('overtime')->nullable()->default(0);
            $table->double('early_clock_in')->nullable()->default(0);
            $table->double('credited_early_clock_in')->nullable()->default(0);
            $table->double('credited_overtime')->nullable()->default(0);
            $table->double('work_hrs')->nullable()->default(0);
            $table->double('credited_work_hrs')->nullable()->default(0);
            $table->string('overtime_status')->nullable()->default('n/a');
            $table->boolean('early_clock_in_approved')->nullable()->default(false);
            $table->boolean('overtime_approved')->nullable()->default(false);
            $table->boolean('is_absent')->nullable()->default(false);
            $table->unsignedInteger('absence_minutes')->nullable()->default(0);
            $table->string('day_part')->nullable()->default('whole_day');
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('fingerprint_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->nullable();
            $table->integer('credited_overtime')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('late')->default(0);
            $table->integer('early_overtime')->default(0);
            $table->boolean('overtime_approved')->default(false);
            $table->boolean('early_overtime_approved')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->unsignedBigInteger('manual_edited_by_employee_id')->nullable();
            $table->timestamp('manual_edited_at')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->text('review_reason')->nullable();
            $table->string('dtr_source')->nullable();
            $table->unsignedBigInteger('dtr_source_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dtr_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('assigned_employee_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_employee_id')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('request_type');
            $table->text('description')->nullable();
            $table->string('attachment_path', 2048)->nullable();
            $table->string('attachment_original_name', 255)->nullable();
            $table->string('status')->default('pending');
            $table->text('reviewer_remarks')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('employee_seen_at')->nullable();
            $table->string('employee_name_snapshot')->nullable();
            $table->string('employee_company_id_snapshot')->nullable();
            $table->string('branch_name_snapshot')->nullable();
            $table->string('payroll_period_title_snapshot')->nullable();
            $table->unsignedBigInteger('dtr_id')->nullable();
            $table->unsignedBigInteger('employee_visible_dtr_id')->nullable();
            $table->date('dtr_date')->nullable();
            $table->unsignedInteger('current_level')->default(1);
            $table->unsignedInteger('total_levels')->default(1);
            $table->string('current_step_label', 100)->nullable();
            $table->json('workflow_snapshot')->nullable();
            $table->json('approvals_history')->nullable();
            $table->json('original_values')->nullable();
            $table->json('proposed_values')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('first_level_editor_id')->nullable();
            $table->timestamp('first_level_edited_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->string('active_scope_key')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_approval_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('sequence')->default(1);
            $table->string('label')->default('Level 1');
            $table->unsignedBigInteger('approver_employee_id');
        });
    }

    public function test_submission_routes_to_the_active_station_manager_without_modifying_dtr(): void
    {
        [$employee, $period, $owner] = $this->context();

        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));

        $this->assertSame($owner->id, $request->assigned_employee_id);
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

    public function test_submission_requires_active_approval_workflow(): void
    {
        [$employee, $period] = $this->context();
        \App\Models\LeaveApprovalWorkflow::query()->update(['is_active' => false]);

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

    public function test_assigned_station_manager_can_approve_a_pending_request(): void
    {
        [$employee, $period, $owner] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));

        $reviewed = app(DtrChangeRequestService::class)->approve($request, $owner, 'Verified against the biometric log.');

        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $reviewed->status);
        $this->assertSame($owner->id, $reviewed->reviewed_by_employee_id);
        $this->assertNotNull($reviewed->reviewed_at);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_unassigned_station_manager_cannot_review_the_request(): void
    {
        [$employee, $period] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));
        $otherBranch = $this->branch('Other Branch');
        $other = Employee::query()->create([
            'uid' => '0099',
            'firstname' => 'Other',
            'lastname' => 'Manager',
            'branch_id' => $otherBranch->id,
            'employment_type' => 'Permanent',
            'rate_type' => 'daily',
            'daily_rate' => 500,
        ]);
        $otherFlow = \App\Models\LeaveApprovalWorkflow::query()->create([
            'name' => 'Other Branch Workflow',
            'branch_id' => $otherBranch->id,
            'is_active' => true,
        ]);
        \App\Models\LeaveApprovalLevel::query()->create([
            'workflow_id' => $otherFlow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $other->id,
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

    public function test_station_manager_navigation_badge_counts_pending_requests(): void
    {
        [$employee, $period, $owner] = $this->context();
        $request = app(DtrChangeRequestService::class)->submit($employee, $this->requestData($period));
        $this->actingAs($owner->user, 'web');

        $this->assertSame('1', ReviewDtrChangeRequests::getNavigationBadge());

        app(DtrChangeRequestService::class)->approve($request, $owner);

        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());
    }

    public function test_forgot_to_punch_rejected_for_complete_dtr_entry(): void
    {
        [$employee, $period, $owner] = $this->context();

        $dtr = \App\Models\Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $employee->branch_id,
            'fingerprint_id' => $employee->uid,
            'date_in' => '2026-08-15',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-15',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The selected DTR entry is not a Forgot To Punch record.');

        app(DtrChangeRequestService::class)->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Attempting forgot to punch on complete entry.',
        ]);
    }

    public function test_forgot_to_punch_allowed_for_incomplete_or_forgot_entry(): void
    {
        [$employee, $period, $owner] = $this->context();

        $dtr = \App\Models\Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $employee->branch_id,
            'fingerprint_id' => $employee->uid,
            'date_in' => '2026-08-16',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-16',
            'time_out' => null,
            'schedule_type' => 'Forgot to Punch',
        ]);

        $request = app(DtrChangeRequestService::class)->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Missed punching out.',
        ]);

        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertSame(DtrChangeRequest::TYPE_FORGOT_TO_PUNCH, $request->request_type);
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
        $owner = Employee::query()->create([
            'uid' => '0002',
            'firstname' => 'Manager',
            'lastname' => 'One',
            'branch_id' => $branch->id,
            'employment_type' => 'Permanent',
            'rate_type' => 'daily',
            'daily_rate' => 600,
        ]);
        $ownerUser = User::factory()->create([
            'username' => 'PF0002',
            'role' => 'employee',
            'employee_id' => $owner->id,
        ]);
        $owner->update(['user_id' => $ownerUser->id]);
        $owner->setRelation('user', $ownerUser);
        $ownerUser->setRelation('employee', $owner);

        $workflow = \App\Models\LeaveApprovalWorkflow::query()->create([
            'name' => 'Test Branch Approval Flow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        \App\Models\LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $owner->id,
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
