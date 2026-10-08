<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\LeaveApprovalLevel;
use App\Models\LeaveApprovalWorkflow;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\DtrChangeRequestService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MissingDtrEntriesAndDeleteRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
        Filament::setCurrentPanel(Filament::getPanel('employee'));
        Storage::fake('local');
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
            $table->time('shift1_start')->nullable();
            $table->time('shift1_end')->nullable();
            $table->time('shift2_start')->nullable();
            $table->time('shift2_end')->nullable();
            $table->time('shift3_start')->nullable();
            $table->time('shift3_end')->nullable();
            $table->time('broken_shift1_start')->nullable();
            $table->time('broken_shift1_end')->nullable();
            $table->time('broken_shift2_start')->nullable();
            $table->time('broken_shift2_end')->nullable();
            $table->time('broken_shift3_start')->nullable();
            $table->time('broken_shift3_end')->nullable();
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

        Schema::create('holiday_types', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->decimal('rate', 8, 2)->default(100);
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('holiday_type_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('title')->default('Holiday');
            $table->boolean('is_recurring')->default(false);
            $table->string('month_day', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('holiday_employee_exclusions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('date')->nullable();
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
            $table->string('fingerprint_id', 50)->nullable();
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
            $table->string('schedule_type')->default('Regular');
            $table->string('schedule_start')->nullable();
            $table->string('schedule_end')->nullable();
            $table->string('day_part')->nullable();
            $table->string('entry_source')->default('manual');
            $table->integer('credited_overtime')->default(0);
            $table->integer('credited_early_clock_in')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('late')->default(0);
            $table->integer('overtime')->default(0);
            $table->integer('early_clock_in')->default(0);
            $table->integer('work_hrs')->default(0);
            $table->integer('credited_work_hrs')->default(0);
            $table->boolean('overtime_approved')->default(false);
            $table->boolean('early_clock_in_approved')->default(false);
            $table->string('overtime_status')->nullable();
            $table->decimal('daily_rate', 10, 2)->nullable()->default(500);
            $table->boolean('is_absent')->default(false);
            $table->integer('absence_minutes')->default(0);
            $table->string('comment')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->boolean('is_imported')->default(false);
            $table->boolean('is_holiday')->nullable()->default(false);
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->string('holiday_type')->nullable();
            $table->decimal('holiday_rate', 8, 2)->nullable()->default(0);
            $table->boolean('holiday_excluded')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('fingerprint_id')->nullable();
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->default('Regular');
            $table->string('schedule_start')->nullable();
            $table->string('schedule_end')->nullable();
            $table->string('day_part')->nullable();
            $table->string('entry_source')->default('manual');
            $table->integer('credited_overtime')->default(0);
            $table->integer('credited_early_clock_in')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('late')->default(0);
            $table->integer('overtime')->default(0);
            $table->integer('early_clock_in')->default(0);
            $table->integer('work_hrs')->default(0);
            $table->integer('credited_work_hrs')->default(0);
            $table->boolean('overtime_approved')->default(false);
            $table->boolean('early_clock_in_approved')->default(false);
            $table->string('overtime_status')->nullable();
            $table->decimal('daily_rate', 10, 2)->nullable()->default(500);
            $table->boolean('is_absent')->default(false);
            $table->integer('absence_minutes')->default(0);
            $table->string('comment')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_imported')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->unsignedBigInteger('manual_edited_by_employee_id')->nullable();
            $table->timestamp('manual_edited_at')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->text('review_reason')->nullable();
            $table->boolean('is_holiday')->nullable()->default(false);
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->string('holiday_type')->nullable();
            $table->decimal('holiday_rate', 8, 2)->nullable()->default(0);
            $table->boolean('holiday_excluded')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('leave_approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->string('active_scope_key')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_approval_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('leave_approval_workflows')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('label', 100);
            $table->unsignedBigInteger('approver_employee_id');
            $table->unsignedBigInteger('alternate_employee_id')->nullable();
        });

        Schema::create('dtr_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 36)->nullable();
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
            $table->string('status')->default('Pending');
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
    }

    protected function setupEmployeeAndBranch(): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Metro Station',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
        ]);

        $user = User::query()->create([
            'name' => 'John Employee',
            'email' => 'john@example.com',
            'role' => 'employee',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'company_id' => 'EMP-001',
            'fingerprint_id' => 'FP-001',
            'branch_id' => $branch->id,
            'daily_rate' => 500,
        ]);

        $sicUser = User::query()->create([
            'name' => 'Jane SIC',
            'email' => 'jane@example.com',
            'role' => 'employee',
        ]);

        $sic = Employee::query()->create([
            'user_id' => $sicUser->id,
            'firstname' => 'Jane',
            'lastname' => 'SIC',
            'company_id' => 'SIC-001',
            'branch_id' => $branch->id,
            'is_station_manager' => true,
            'managed_branches' => [$branch->id],
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Default Branch Approval Flow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $sic->id,
        ]);

        $period = PayrollPeriod::query()->create([
            'title' => 'October 1-15 2026',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-15',
            'date_payout' => '2026-10-20',
            'is_locked' => false,
        ]);

        return [$employee, $branch, $period, $sic, $sicUser];
    }

    protected function createValidAttachment(): string
    {
        $file = UploadedFile::fake()->image('proof.png', 400, 400);
        return $file->store('dtr-change-requests', 'local');
    }

    /**
     * Scenario 1: Multiple Missing Entries (Multi-Level Approval)
     * Employee submits 3 missing DTR entries (Oct 1, Oct 2, Oct 3).
     * Workflow: SIC -> RC
     * SIC approves -> Still pending RC, entries NOT yet in official DTR.
     * RC approves -> Final level, all 3 entries created in DTR & EmployeeVisibleDtr.
     */
    public function test_scenario_1_multiple_missing_entries_multi_level(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $rc = Employee::query()->create([
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '2-Level Approval',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);

        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        $request = $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                ['date_in' => '2026-10-01', 'time_in' => '08:00 AM', 'date_out' => '2026-10-01', 'time_out' => '05:00 PM', 'schedule_type' => 'Regular'],
                ['date_in' => '2026-10-02', 'time_in' => '08:00 AM', 'date_out' => '2026-10-02', 'time_out' => '05:00 PM', 'schedule_type' => 'Regular'],
                ['date_in' => '2026-10-03', 'time_in' => '08:00 AM', 'date_out' => '2026-10-03', 'time_out' => '05:00 PM', 'schedule_type' => 'Regular'],
            ],
            'explanation' => 'Forgot to punch during field assignment.',
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => 'proof.png',
        ]);

        $this->assertSame(2, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertSame(DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES, $request->request_type);

        // Official DTR must NOT have entries yet
        $this->assertSame(0, Dtr::query()->where('employee_id', $employee->id)->orWhere('fingerprint_id', $employee->fingerprint_id)->count());

        // Level 1: SIC Approves
        $request = $service->approve($request, $sic, 'Endorsed by SIC.');
        $this->assertSame(2, $request->current_level);
        $this->assertSame($rc->id, $request->assigned_employee_id);
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertSame(0, Dtr::query()->where('fingerprint_id', $employee->fingerprint_id)->count());

        // Level 2: RC Approves (Final Level)
        $request = $service->approve($request, $rc, 'Final approval by RC.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        // Official DTR and EmployeeVisibleDtr now have all 3 entries
        $this->assertSame(3, Dtr::query()->where('fingerprint_id', $employee->fingerprint_id)->count());
        $this->assertSame(3, EmployeeVisibleDtr::query()->where('employee_id', $employee->id)->count());

        $dtr1 = Dtr::query()->where('fingerprint_id', $employee->fingerprint_id)->whereDate('date_in', '2026-10-01')->first();
        $this->assertNotNull($dtr1);
        $this->assertSame('08:00:00', $dtr1->time_in);
        $this->assertSame('17:00:00', $dtr1->time_out);
    }

    /**
     * Scenario 2: Single-Level Missing Entry Approval
     * Employee submits 2 missing DTR entries.
     * SIC approves -> Immediately Approved and entries created.
     */
    public function test_scenario_2_single_level_missing_entry_approval(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();
        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        $request = $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                ['date_in' => '2026-10-05', 'time_in' => '08:00 AM', 'date_out' => '2026-10-05', 'time_out' => '05:00 PM'],
                ['date_in' => '2026-10-06', 'time_in' => '08:00 AM', 'date_out' => '2026-10-06', 'time_out' => '05:00 PM'],
            ],
            'explanation' => 'Missed attendance on Oct 5 and 6.',
            'attachment_path' => $attachmentPath,
        ]);

        $this->assertSame(1, $request->total_levels);
        $this->assertSame(1, $request->current_level);

        $request = $service->approve($request, $sic, 'Approved by SIC.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        $this->assertSame(2, Dtr::query()->where('fingerprint_id', $employee->fingerprint_id)->count());
        $this->assertSame(2, EmployeeVisibleDtr::query()->where('employee_id', $employee->id)->count());
    }

    /**
     * Missing DTR entries request calculates and reflects overtime_status properly
     */
    public function test_missing_dtr_entries_reflects_overtime_status_properly(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();
        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        // Submit missing entry with overtime: 8:00 AM to 6:30 PM (90 mins overtime after 5:00 PM)
        $request = $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                ['date_in' => '2026-10-05', 'time_in' => '08:00 AM', 'date_out' => '2026-10-05', 'time_out' => '06:30 PM'],
            ],
            'explanation' => 'Rendered overtime on Oct 5.',
            'attachment_path' => $attachmentPath,
        ]);

        $request = $service->approve($request, $sic, 'Approved by SIC.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        $official = Dtr::query()->where('fingerprint_id', $employee->fingerprint_id)->whereDate('date_in', '2026-10-05')->first();
        $this->assertNotNull($official);
        $this->assertGreaterThanOrEqual(30, $official->overtime);
        $this->assertSame('Pending', $official->overtime_status);

        $visible = EmployeeVisibleDtr::query()->where('employee_id', $employee->id)->whereDate('date_in', '2026-10-05')->first();
        $this->assertNotNull($visible);
        $this->assertSame($official->overtime, $visible->overtime);
        $this->assertSame('Pending', $visible->overtime_status);
    }

    /**
     * Scenario 3: Duplicate Existing DTR Check
     * Employee attempts to submit missing entry for date where DTR already exists.
     * Expected: ValidationException thrown.
     */
    public function test_scenario_3_duplicate_existing_dtr_blocked(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        // Create an existing DTR on Oct 2
        Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-02',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-02',
            'time_out' => '17:00:00',
        ]);

        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        $this->expectException(ValidationException::class);

        $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                ['date_in' => '2026-10-02', 'time_in' => '08:00 AM', 'date_out' => '2026-10-02', 'time_out' => '05:00 PM'],
            ],
            'explanation' => 'Trying to submit duplicate entry.',
            'attachment_path' => $attachmentPath,
        ]);
    }

    /**
     * Scenario 4: Invalid Attachment Blocked
     * Non-image (e.g. PDF) or image larger than 2.5MB.
     */
    public function test_scenario_4_invalid_attachment_blocked(): void
    {
        [$employee, $branch, $period] = $this->setupEmployeeAndBranch();
        $service = app(DtrChangeRequestService::class);

        // 1. PDF attachment
        $pdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $pdfPath = $pdf->store('dtr-change-requests', 'local');

        try {
            $service->submitMissingDtrEntries($employee, [
                'payroll_period_id' => $period->id,
                'entries' => [
                    ['date_in' => '2026-10-01', 'time_in' => '08:00 AM', 'date_out' => '2026-10-01', 'time_out' => '05:00 PM'],
                ],
                'explanation' => 'PDF attached.',
                'attachment_path' => $pdfPath,
                'attachment_original_name' => 'document.pdf',
            ]);
            $this->fail('Expected ValidationException for PDF attachment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('attachment_path', $e->errors());
        }

        // 2. Oversized image (> 2.5 MB, e.g. 3 MB)
        $largeImage = UploadedFile::fake()->createWithContent('large.png', "\x89PNG\r\n\x1a\n" . str_repeat('0', 3000 * 1024));
        $largePath = $largeImage->store('dtr-change-requests', 'local');

        try {
            $service->submitMissingDtrEntries($employee, [
                'payroll_period_id' => $period->id,
                'entries' => [
                    ['date_in' => '2026-10-01', 'time_in' => '08:00 AM', 'date_out' => '2026-10-01', 'time_out' => '05:00 PM'],
                ],
                'explanation' => 'Large file attached.',
                'attachment_path' => $largePath,
                'attachment_original_name' => 'large.png',
            ]);
            $this->fail('Expected ValidationException for oversized attachment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('attachment_path', $e->errors());
        }
    }

    /**
     * Scenario 5: Locked Payroll Period Blocked
     */
    public function test_scenario_5_locked_payroll_period_blocked(): void
    {
        [$employee, $branch, $period] = $this->setupEmployeeAndBranch();
        $period->update(['is_locked' => true]);

        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        $this->expectException(ValidationException::class);

        $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                ['date_in' => '2026-10-01', 'time_in' => '08:00 AM', 'date_out' => '2026-10-01', 'time_out' => '05:00 PM'],
            ],
            'explanation' => 'Locked period attempt.',
            'attachment_path' => $attachmentPath,
        ]);
    }

    /**
     * Scenario 6: Missing Entry Rejected
     * SIC rejects -> Request Rejected, no DTR entries created.
     */
    public function test_scenario_6_missing_entry_rejected(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();
        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        $request = $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                ['date_in' => '2026-10-07', 'time_in' => '08:00 AM', 'date_out' => '2026-10-07', 'time_out' => '05:00 PM'],
                ['date_in' => '2026-10-08', 'time_in' => '08:00 AM', 'date_out' => '2026-10-08', 'time_out' => '05:00 PM'],
            ],
            'explanation' => 'Forgot punches on field assignment.',
            'attachment_path' => $attachmentPath,
        ]);

        $request = $service->reject($request, $sic, 'Insufficient log proof.');
        $this->assertSame(DtrChangeRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('Insufficient log proof.', $request->rejection_reason);

        // No records created
        $this->assertSame(0, Dtr::query()->where('fingerprint_id', $employee->fingerprint_id)->count());
        $this->assertSame(0, EmployeeVisibleDtr::query()->where('employee_id', $employee->id)->count());
    }

    /**
     * Scenario 7: Delete Time Entry (Multi-Level Approval)
     * Workflow: Employee -> SIC -> RC
     * SIC approves -> Record remains in database.
     * RC approves (final) -> Record permanently deleted.
     */
    public function test_scenario_7_delete_time_entry_multi_level(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $rc = Employee::query()->create([
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '2-Level Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);

        $dtr = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-04',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-04',
            'time_out' => '17:00:00',
            'work_hrs' => 480,
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-04',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-04',
            'time_out' => '17:00:00',
            'work_hrs' => 480,
        ]);

        $service = app(DtrChangeRequestService::class);

        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Accidental biometric swipe on rest day.',
        ]);

        $this->assertSame(2, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);

        // Record still exists
        $this->assertDatabaseHas('dtrs', ['id' => $dtr->id]);

        // Level 1: SIC approves
        $request = $service->approve($request, $sic, 'Endorsed for deletion.');
        $this->assertSame(2, $request->current_level);
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertDatabaseHas('dtrs', ['id' => $dtr->id]);

        // Level 2: RC approves (Final)
        $request = $service->approve($request, $rc, 'Final deletion approval.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        // Record is permanently deleted across both official and visible tables
        $this->assertDatabaseMissing('dtrs', ['id' => $dtr->id]);
        $this->assertDatabaseMissing('employee_visible_dtrs', ['id' => $visibleDtr->id]);

        // Audit trail preserved in Change Request
        $this->assertNotNull($request->original_values);
        $this->assertSame('08:00:00', $request->original_values['time_in']);
        $this->assertSame('Accidental biometric swipe on rest day.', $request->description);
    }

    /**
     * Scenario 8: Delete Time Entry Rejected
     * SIC rejects -> DTR remains unchanged.
     */
    public function test_scenario_8_delete_time_entry_rejected(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-09',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-09',
            'time_out' => '17:00:00',
        ]);

        $service = app(DtrChangeRequestService::class);

        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Please delete this entry.',
        ]);

        $request = $service->reject($request, $sic, 'Legitimate attendance, cannot delete.');
        $this->assertSame(DtrChangeRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('Legitimate attendance, cannot delete.', $request->rejection_reason);

        // Record still intact
        $this->assertDatabaseHas('dtrs', ['id' => $dtr->id]);
    }

    /**
     * Scenario 9: Single-Level Delete
     * Workflow: Employee -> SIC
     * SIC approves -> Record permanently deleted immediately.
     */
    public function test_scenario_9_single_level_delete(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-10',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-10',
            'time_out' => '17:00:00',
        ]);

        $service = app(DtrChangeRequestService::class);

        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Duplicate clock in test.',
        ]);

        $request = $service->approve($request, $sic, 'Approved deletion.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        $this->assertDatabaseMissing('dtrs', ['id' => $dtr->id]);
    }

    /**
     * Scenario 10: Duplicate Delete Request Blocked
     * Pending Delete request already exists for this DTR.
     */
    public function test_scenario_10_duplicate_delete_request_blocked(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-11',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-11',
            'time_out' => '17:00:00',
        ]);

        $service = app(DtrChangeRequestService::class);

        $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Initial delete request.',
        ]);

        $this->expectException(ValidationException::class);

        // Attempting second delete request for same DTR
        $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Second delete request attempt.',
        ]);
    }

    /**
     * Scenario 11: All-or-Nothing conflict handling during final approval
     * If an attendance record was added while the request was pending,
     * final approval must stop without saving partial records.
     */
    public function test_scenario_11_all_or_nothing_conflict_on_final_approval(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();
        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        $request = $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                ['date_in' => '2026-10-12', 'time_in' => '08:00 AM', 'date_out' => '2026-10-12', 'time_out' => '05:00 PM'],
                ['date_in' => '2026-10-13', 'time_in' => '08:00 AM', 'date_out' => '2026-10-13', 'time_out' => '05:00 PM'],
            ],
            'explanation' => 'Field assignment missing entries.',
            'attachment_path' => $attachmentPath,
        ]);

        // While pending, an admin inserts a DTR for 2026-10-13
        Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-13',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-13',
            'time_out' => '17:00:00',
        ]);

        // Approving must fail due to conflict and NOT create partial entry for 2026-10-12
        try {
            $service->approve($request, $sic, 'Approving missing entries.');
            $this->fail('Expected exception for conflicting entry during final approval.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('2026-10-13', $e->getMessage());
        }

        // Verify 2026-10-12 was NOT created (all-or-nothing protection)
        $this->assertNull(
            Dtr::query()
                ->where('fingerprint_id', $employee->fingerprint_id)
                ->whereDate('date_in', '2026-10-12')
                ->first()
        );
    }
    /**
     * Scenario 12: Same date multiple entries allowed when schedule types differ
     */
    public function test_scenario_12_same_date_with_different_schedule_types_allowed(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();
        $branch->update([
            'shift1_start' => '06:00:00',
            'shift1_end' => '14:00:00',
            'shift2_start' => '14:00:00',
            'shift2_end' => '22:00:00',
        ]);
        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        // Submit Shift 1 and Shift 2 on the SAME date (2026-10-01)
        $request = $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                [
                    'date_in' => '2026-10-01',
                    'time_in' => '06:00 AM',
                    'date_out' => '2026-10-01',
                    'time_out' => '02:00 PM',
                    'schedule_type' => 'Shift1',
                ],
                [
                    'date_in' => '2026-10-01',
                    'time_in' => '02:00 PM',
                    'date_out' => '2026-10-01',
                    'time_out' => '10:00 PM',
                    'schedule_type' => 'Shift2',
                ],
            ],
            'explanation' => 'Worked both morning and afternoon shifts on Oct 1.',
            'attachment_path' => $attachmentPath,
        ]);

        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertCount(2, $request->proposed_values['entries']);

        // Approve and verify records created
        $request = $service->approve($request, $sic, 'Approved dual shift.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        $created = Dtr::query()->where('fingerprint_id', $employee->fingerprint_id)->whereDate('date_in', '2026-10-01')->get();
        $this->assertCount(2, $created);
        $this->assertTrue($created->contains('schedule_type', 'Shift1'));
        $this->assertTrue($created->contains('schedule_type', 'Shift2'));
    }

    /**
     * Scenario 13: Same date duplicate schedule type blocked
     */
    public function test_scenario_13_same_date_with_same_schedule_type_blocked(): void
    {
        [$employee, $branch, $period] = $this->setupEmployeeAndBranch();
        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        $this->expectException(ValidationException::class);

        // Submit Shift 1 twice on the SAME date
        $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                [
                    'date_in' => '2026-10-01',
                    'time_in' => '06:00 AM',
                    'date_out' => '2026-10-01',
                    'time_out' => '02:00 PM',
                    'schedule_type' => 'Shift1',
                ],
                [
                    'date_in' => '2026-10-01',
                    'time_in' => '06:15 AM',
                    'date_out' => '2026-10-01',
                    'time_out' => '02:15 PM',
                    'schedule_type' => 'Shift 1',
                ],
            ],
            'explanation' => 'Attempting duplicate shift on same date.',
            'attachment_path' => $attachmentPath,
        ]);
    }

    /**
     * Scenario 14: Existing record on date allows missing entry with different schedule type
     */
    public function test_scenario_14_existing_record_allows_different_schedule_type(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        // Existing official record for Shift 1 on Oct 1
        Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-01',
            'time_in' => '06:00:00',
            'date_out' => '2026-10-01',
            'time_out' => '14:00:00',
            'schedule_type' => 'Shift1',
        ]);

        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        // Submit missing Shift 2 for Oct 1 (different schedule type)
        $request = $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                [
                    'date_in' => '2026-10-01',
                    'time_in' => '02:00 PM',
                    'date_out' => '2026-10-01',
                    'time_out' => '10:00 PM',
                    'schedule_type' => 'Shift2',
                ],
            ],
            'explanation' => 'Worked extra shift on Oct 1.',
            'attachment_path' => $attachmentPath,
        ]);

        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);

        $request = $service->approve($request, $sic, 'Approved.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        $records = Dtr::query()->where('fingerprint_id', $employee->fingerprint_id)->whereDate('date_in', '2026-10-01')->get();
        $this->assertCount(2, $records);
    }

    /**
     * Scenario 15: Schedule times resolution utility from branch
     */
    public function test_scenario_15_resolve_branch_schedule_times(): void
    {
        [$employee, $branch] = $this->setupEmployeeAndBranch();
        $branch->update([
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
            'shift1_start' => '05:30:00',
            'shift1_end' => '13:30:00',
            'shift2_start' => '13:30:00',
            'shift2_end' => '21:30:00',
            'broken_shift1_start' => '08:00:00',
            'broken_shift1_end' => '12:00:00',
        ]);

        $service = app(DtrChangeRequestService::class);

        // Shift 1
        [$s1Start, $s1End] = $service->resolveBranchScheduleTimes($branch, 'Shift1', formatForInput: true);
        $this->assertSame('05:30 AM', $s1Start);
        $this->assertSame('01:30 PM', $s1End);

        // Shift 2
        [$s2Start, $s2End] = $service->resolveBranchScheduleTimes($branch, 'Shift2', formatForInput: true);
        $this->assertSame('01:30 PM', $s2Start);
        $this->assertSame('09:30 PM', $s2End);

        // Broken Shift 1
        [$b1Start, $b1End] = $service->resolveBranchScheduleTimes($branch, 'Broken1', formatForInput: true);
        $this->assertSame('08:00 AM', $b1Start);
        $this->assertSame('12:00 PM', $b1End);

        // Regular
        [$regStart, $regEnd] = $service->resolveBranchScheduleTimes($branch, 'Regular', formatForInput: true);
        $this->assertSame('08:00 AM', $regStart);
        $this->assertSame('05:00 PM', $regEnd);

        // Saturday
        [$satStart, $satEnd] = $service->resolveBranchScheduleTimes($branch, 'Saturday', formatForInput: true);
        $this->assertSame('08:00 AM', $satStart);
        $this->assertSame('11:00 AM', $satEnd);
    }

    /**
     * Scenario 16: Deleting specific DTR UID among multiple records on the exact same date
     * E.g. October 1:
     * - UID 101: Normal attendance
     * - UID 102: Forgot To Punch entry
     * - UID 103: Another valid attendance entry
     * Deleting UID 101 must delete ONLY UID 101.
     * UID 102 (Forgot To Punch), UID 103, and other dates/employees must remain untouched!
     */
    public function test_delete_time_entry_deletes_only_selected_uid_among_multiple_records_on_same_date_preserving_forgot_to_punch(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        // Second employee in same branch
        $otherUser = User::query()->create(['name' => 'Other Emp', 'role' => 'employee']);
        $otherEmployee = Employee::query()->create([
            'user_id' => $otherUser->id,
            'firstname' => 'Other',
            'lastname' => 'Worker',
            'company_id' => 'EMP-002',
            'branch_id' => $branch->id,
            'fingerprint_id' => '1002',
        ]);

        // Record 1: Normal attendance on Oct 1 (Target for deletion)
        $record1Normal = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-01',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-01',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
            'work_hrs' => 480,
            'comment' => 'Normal attendance',
        ]);

        // Record 2: Forgot To Punch attendance on Oct 1 (MUST NOT BE DELETED!)
        $record2ForgotToPunch = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-01',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-01',
            'time_out' => null,
            'schedule_type' => 'Shift1',
            'is_absent' => true,
            'comment' => 'Forgot to punch entry',
        ]);

        // Record 3: Another valid attendance entry on Oct 1 (MUST NOT BE DELETED!)
        $record3Another = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-01',
            'time_in' => '18:00:00',
            'date_out' => '2026-10-01',
            'time_out' => '22:00:00',
            'schedule_type' => 'Overtime',
            'work_hrs' => 240,
            'comment' => 'Another valid entry',
        ]);

        // Record 4: Attendance on a different date (Oct 2) in same period (MUST NOT BE DELETED!)
        $record4OtherDate = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-02',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-02',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
            'work_hrs' => 480,
        ]);

        // Record 5: Other employee's attendance on Oct 1 (MUST NOT BE DELETED!)
        $record5OtherEmp = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $otherEmployee->id,
            'fingerprint_id' => $otherEmployee->fingerprint_id,
            'date_in' => '2026-10-01',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-01',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
            'work_hrs' => 480,
        ]);

        $service = app(DtrChangeRequestService::class);

        // Employee submits Delete Time Entry request specifically for Record 1 (Normal Attendance)
        $request = $service->submitFromDtr($employee, $record1Normal, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Delete normal punch only, keep other entries on same date.',
        ]);

        $this->assertSame($record1Normal->id, $request->dtr_id);
        $this->assertSame($record1Normal->id, $request->targetDtrUid());
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);

        // All 5 records still exist before approval
        $this->assertDatabaseHas('dtrs', ['id' => $record1Normal->id]);
        $this->assertDatabaseHas('dtrs', ['id' => $record2ForgotToPunch->id]);
        $this->assertDatabaseHas('dtrs', ['id' => $record3Another->id]);
        $this->assertDatabaseHas('dtrs', ['id' => $record4OtherDate->id]);
        $this->assertDatabaseHas('dtrs', ['id' => $record5OtherEmp->id]);

        // Approver approves the request
        $request = $service->approve($request, $sic, 'Approved deletion of Record 1.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        // ONLY Record 1 is deleted!
        $this->assertDatabaseMissing('dtrs', ['id' => $record1Normal->id]);

        // All other records MUST remain completely untouched!
        $this->assertDatabaseHas('dtrs', [
            'id' => $record2ForgotToPunch->id,
            'date_in' => '2026-10-01',
            'schedule_type' => 'Shift1',
            'comment' => 'Forgot to punch entry',
        ]);
        $this->assertDatabaseHas('dtrs', [
            'id' => $record3Another->id,
            'date_in' => '2026-10-01',
            'schedule_type' => 'Overtime',
            'comment' => 'Another valid entry',
        ]);
        $this->assertDatabaseHas('dtrs', [
            'id' => $record4OtherDate->id,
            'date_in' => '2026-10-02',
        ]);
        $this->assertDatabaseHas('dtrs', [
            'id' => $record5OtherEmp->id,
            'fingerprint_id' => $otherEmployee->fingerprint_id,
            'date_in' => '2026-10-01',
        ]);
    }

    /**
     * Scenario 17: Shift-based, broken time, and missing Time Out entries on the same date.
     */
    public function test_delete_time_entry_shift_based_broken_and_missing_punch_records_on_same_date(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        // Entry A: Shift 1
        $entryA = EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-05',
            'time_in' => '06:00:00',
            'date_out' => '2026-10-05',
            'time_out' => '14:00:00',
            'schedule_type' => 'Shift1',
        ]);

        // Entry B: Shift 2 with missing Time Out (Target for deletion)
        $entryB = EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-05',
            'time_in' => '14:00:00',
            'date_out' => null,
            'time_out' => null,
            'schedule_type' => 'Shift2',
        ]);

        // Entry C: Broken shift
        $entryC = EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-05',
            'time_in' => '18:00:00',
            'date_out' => '2026-10-05',
            'time_out' => '22:00:00',
            'schedule_type' => 'Broken1',
        ]);

        $service = app(DtrChangeRequestService::class);

        // Delete only Entry B
        $request = $service->submitFromDtr($employee, $entryB, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Delete incomplete Shift 2 punch.',
        ]);

        $this->assertSame($entryB->id, $request->employee_visible_dtr_id);
        $this->assertSame($entryB->id, $request->targetDtrUid());

        $request = $service->approve($request, $sic, 'Approved.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        // ONLY Entry B is deleted
        $this->assertDatabaseMissing('employee_visible_dtrs', ['id' => $entryB->id]);

        // Entry A and Entry C are untouched
        $this->assertDatabaseHas('employee_visible_dtrs', ['id' => $entryA->id]);
        $this->assertDatabaseHas('employee_visible_dtrs', ['id' => $entryC->id]);
    }

    /**
     * Scenario 18: If the stored UID no longer exists, final approval stops
     * and returns exact message: 'The selected DTR entry no longer exists or has already been deleted.'
     */
    public function test_delete_time_entry_already_deleted_or_missing_uid_fails_with_exact_error(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-06',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-06',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
        ]);

        // Another record on same date
        $otherDtrSameDate = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-06',
            'time_in' => '18:00:00',
            'date_out' => '2026-10-06',
            'time_out' => '22:00:00',
            'schedule_type' => 'Shift2',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Will be deleted before approval.',
        ]);

        // Record is manually removed or deleted in database before approver decides
        $dtr->forceDelete();
        $this->assertDatabaseMissing('dtrs', ['id' => $dtr->id]);

        // Final approver attempts to approve
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The selected DTR entry no longer exists or has already been deleted.');

        $service->approve($request, $sic, 'Attempting approval');

        // Other record on same date MUST still exist (no date fallback occurred!)
        $this->assertDatabaseHas('dtrs', ['id' => $otherDtrSameDate->id]);
    }

    /**
     * Scenario 19: Server-side validation blocks deletion if record does not belong to requesting employee.
     */
    public function test_delete_time_entry_mismatched_employee_or_period_fails_validation(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $otherUser = User::query()->create(['name' => 'Other Emp', 'role' => 'employee']);
        $otherEmployee = Employee::query()->create([
            'user_id' => $otherUser->id,
            'firstname' => 'Other',
            'lastname' => 'Worker',
            'company_id' => 'EMP-002',
            'branch_id' => $branch->id,
            'fingerprint_id' => '1002',
        ]);

        $otherDtr = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $otherEmployee->id,
            'fingerprint_id' => '1002',
            'date_in' => '2026-10-07',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-07',
            'time_out' => '17:00:00',
        ]);

        $service = app(DtrChangeRequestService::class);

        // Tampered request with wrong employee_id
        $request = DtrChangeRequest::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'dtr_id' => $otherDtr->id,
            'dtr_date' => '2026-10-07',
            'date_from' => '2026-10-07',
            'date_to' => '2026-10-07',
            'assigned_employee_id' => $sic->id,
            'employee_name_snapshot' => $employee->full_name,
            'employee_company_id_snapshot' => $employee->company_id,
            'branch_name_snapshot' => $branch->branch_name,
            'payroll_period_title_snapshot' => $period->title,
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'description' => 'Tampered delete request.',
            'status' => DtrChangeRequest::STATUS_PENDING,
            'current_level' => 1,
            'total_levels' => 1,
            'workflow_snapshot' => [['level' => 1, 'label' => 'SIC', 'approver_employee_id' => $sic->id, 'approver_name' => $sic->full_name]],
            'approvals_history' => [],
            'proposed_values' => ['action' => 'delete', 'dtr_uid' => $otherDtr->id],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The selected DTR entry does not belong to the requesting employee.');

        $service->approve($request, $sic, 'Attempting approval');
    }

    /**
     * Scenario 20: Submitting Delete request for one entry on a date does not block
     * submitting another Delete request for a different entry on that same date.
     */
    public function test_multiple_delete_requests_allowed_for_different_entries_on_same_date_but_blocks_same_entry_duplicate(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $entry1 = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-08',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-08',
            'time_out' => '12:00:00',
            'schedule_type' => 'Shift1',
        ]);

        $entry2 = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-08',
            'time_in' => '13:00:00',
            'date_out' => '2026-10-08',
            'time_out' => '17:00:00',
            'schedule_type' => 'Shift2',
        ]);

        $service = app(DtrChangeRequestService::class);

        // Submit delete for Entry 1 -> succeeds
        $req1 = $service->submitFromDtr($employee, $entry1, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Delete shift 1.',
        ]);
        $this->assertSame($entry1->id, $req1->dtr_id);

        // Submit delete for Entry 2 on same date -> also succeeds!
        $req2 = $service->submitFromDtr($employee, $entry2, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Delete shift 2.',
        ]);
        $this->assertSame($entry2->id, $req2->dtr_id);

        // Attempting to submit duplicate for Entry 1 again -> blocked!
        $this->expectException(ValidationException::class);
        $service->submitFromDtr($employee, $entry1, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Duplicate delete for shift 1.',
        ]);
    }

    /**
     * Scenario 21: targetDtrRecord does not query non-existent employee_id column on dtrs table,
     * and missing_dtr_entries requests return null for targetDtrRecord.
     */
    public function test_target_dtr_record_does_not_query_employee_id_on_dtrs_table(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $service = app(DtrChangeRequestService::class);

        // 1. Missing DTR entries request: targetDtrRecord() returns null immediately
        $missingReq = $service->submitMissingDtrEntries($employee, [
            'payroll_period_id' => $period->id,
            'entries' => [
                [
                    'date_in' => '2026-10-01',
                    'time_in' => '08:00 AM',
                    'date_out' => '2026-10-01',
                    'time_out' => '05:00 PM',
                    'schedule_type' => 'Regular',
                ],
            ],
            'explanation' => 'Missing entry for Oct 1.',
            'attachment_path' => $this->createValidAttachment(),
            'attachment_original_name' => 'proof.png',
        ]);

        $this->assertNull($missingReq->targetDtrRecord());

        // Approving the missing entry request should finalize without errors
        $missingReq = $service->approve($missingReq, $sic, 'Approved missing entries.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $missingReq->status);

        // 2. Official DTR lookup fallback in targetDtrRecord queries by fingerprint_id, not employee_id
        $officialDtr = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-02',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-02',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
        ]);

        $customReq = DtrChangeRequest::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'dtr_date' => '2026-10-02',
            'date_from' => '2026-10-02',
            'date_to' => '2026-10-02',
            'assigned_employee_id' => $sic->id,
            'employee_name_snapshot' => $employee->full_name,
            'employee_company_id_snapshot' => $employee->company_id,
            'branch_name_snapshot' => $branch->branch_name,
            'payroll_period_title_snapshot' => $period->title,
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'description' => 'Time discrepancy on Oct 2.',
            'status' => DtrChangeRequest::STATUS_PENDING,
            'current_level' => 1,
            'total_levels' => 1,
            'workflow_snapshot' => [['level' => 1, 'label' => 'SIC', 'approver_employee_id' => $sic->id, 'approver_name' => $sic->full_name]],
            'approvals_history' => [],
            'proposed_values' => ['time_in' => '08:15:00'],
        ]);

        // When employee_visible_dtrs has no record, targetDtrRecord resolves the official Dtr using fingerprint_id
        $found = $customReq->targetDtrRecord();
        $this->assertNotNull($found);
        $this->assertSame($officialDtr->id, $found->id);
    }

    /**
     * Scenario 22: Deleting a visible DTR entry permanently deletes both the visible record
     * and its official DTR counterpart in the dtrs table.
     */
    public function test_delete_time_entry_permanently_deletes_both_visible_and_official_dtr_counterparts(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();
        $service = app(DtrChangeRequestService::class);

        // Official record in dtrs
        $official = Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-02',
            'time_in' => '04:10:33',
            'date_out' => '2026-10-02',
            'time_out' => '12:10:40',
            'schedule_type' => 'Shift1',
        ]);

        // Visible record in employee_visible_dtrs
        $visible = EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'date_in' => '2026-10-02',
            'time_in' => '04:10:33',
            'date_out' => '2026-10-02',
            'time_out' => '12:10:40',
            'schedule_type' => 'Shift1',
        ]);

        // Submit delete request from visible entry
        $request = $service->submitFromDtr($employee, $visible, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Delete Oct 2 entry.',
        ]);

        $this->assertSame($visible->id, $request->employee_visible_dtr_id);

        // Approve delete request
        $service->approve($request, $sic, 'Approved deletion.');

        // Both tables have the record permanently removed!
        $this->assertDatabaseMissing('employee_visible_dtrs', ['id' => $visible->id]);
        $this->assertDatabaseMissing('dtrs', ['id' => $official->id]);
    }

    public function test_pf0014_and_sic_missing_dtr_entries_self_approval_routing_across_workflows(): void
    {
        $mainOffice = Branch::query()->create([
            'branch_name' => 'MAIN OFFICE',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '18:00:00',
        ]);

        $macoBranch = Branch::query()->create([
            'branch_name' => 'Maco',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '18:00:00',
        ]);

        $period = PayrollPeriod::query()->create([
            'title' => 'Oct 1 - 15, 2026',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-15',
            'date_payout' => '2026-10-20',
            'is_locked' => false,
        ]);

        $hrUser = User::query()->create([
            'name' => 'HR Admin',
            'email' => 'hr@example.com',
            'role' => 'hr',
        ]);

        $sicUser = User::query()->create(['name' => 'Rhonalyn Degamo', 'role' => 'employee']);
        $sic = Employee::query()->create([
            'user_id' => $sicUser->id,
            'uid' => '9',
            'company_id' => 'PF-0009',
            'fingerprint_id' => '9',
            'firstname' => 'Rhonalyn',
            'lastname' => 'Degamo',
            'branch_id' => $mainOffice->id,
        ]);

        $rcUser = User::query()->create(['name' => 'Franz Kevin Gaslang', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'uid' => '14',
            'company_id' => 'PF-0014',
            'fingerprint_id' => '14',
            'firstname' => 'Franz Kevin',
            'lastname' => 'Gaslang',
            'branch_id' => $mainOffice->id,
        ]);

        // Company default workflow with 0 levels (as in live DB)
        LeaveApprovalWorkflow::query()->create([
            'name' => 'Company default - HR review',
            'branch_id' => null,
            'is_active' => true,
        ]);

        // Maco station workflow where PF-0009 is Level 1 (SIC) and PF-0014 is Level 2 (RC)
        $macoWorkflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Maco',
            'branch_id' => $macoBranch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $macoWorkflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $macoWorkflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);

        $attachmentPath = $this->createValidAttachment();
        $service = app(DtrChangeRequestService::class);

        // 1. Level 1 SIC (PF-0009) submits Missing DTR Entries -> skips self-approval, routes to Level 2 RC (PF-0014)
        $sicRequest = $service->submitMissingDtrEntries($sic, [
            'payroll_period_id' => $period->id,
            'explanation' => 'SIC field visit.',
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => 'proof.png',
            'entries' => [
                [
                    'date_in' => '2026-10-03',
                    'time_in' => '08:00 AM',
                    'date_out' => '2026-10-03',
                    'time_out' => '06:00 PM',
                    'schedule_type' => 'Regular',
                ],
            ],
        ]);

        $this->assertSame(1, $sicRequest->total_levels);
        $this->assertSame(1, $sicRequest->current_level);
        $this->assertSame($rc->id, $sicRequest->assigned_employee_id);
        $this->assertFalse($sicRequest->canBeReviewedBy($sic));
        $this->assertTrue($sicRequest->canBeReviewedBy($rc));

        // RC approves -> RC is final approver, so DTR record is created immediately
        $sicRequest = $service->approve($sicRequest, $rc, 'Approved by RC.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $sicRequest->status);
        $this->assertDatabaseHas('dtrs', [
            'fingerprint_id' => '9',
            'payroll_period_id' => $period->id,
            'date_in' => '2026-10-03',
        ]);

        // 2. Level 2 RC (PF-0014) submits Missing DTR Entries for 8:00 AM - 6:00 PM Regular schedule
        // -> No higher workflow approver exists -> routes directly to HR Final Approval without error!
        $rcRequest = $service->submitMissingDtrEntries($rc, [
            'payroll_period_id' => $period->id,
            'explanation' => 'RC station audit 8:00 AM to 6:00 PM.',
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => 'proof.png',
            'entries' => [
                [
                    'date_in' => '2026-10-04',
                    'time_in' => '08:00 AM',
                    'date_out' => '2026-10-04',
                    'time_out' => '06:00 PM',
                    'schedule_type' => 'Regular',
                ],
            ],
        ]);

        $this->assertSame(1, $rcRequest->total_levels);
        $this->assertSame(1, $rcRequest->current_level);
        $this->assertNull($rcRequest->assigned_employee_id);
        $this->assertTrue($rcRequest->isPendingHrApproval());
        $this->assertSame('Pending HR Final Approval', $rcRequest->display_status);
        $this->assertFalse($rcRequest->canBeReviewedBy($rc));
        $this->assertTrue($rcRequest->canBeReviewedBy($hrUser));

        // HR approves -> finalizes and creates the missing DTR entry for PF-0014
        $rcRequest = $service->approve($rcRequest, $hrUser, 'Approved by HR.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $rcRequest->status);
        $this->assertDatabaseHas('dtrs', [
            'fingerprint_id' => '14',
            'payroll_period_id' => $period->id,
            'date_in' => '2026-10-04',
        ]);
        $this->assertDatabaseHas('employee_visible_dtrs', [
            'employee_id' => $rc->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-10-04',
        ]);
    }
}
