<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DtrSubmission;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use App\Services\OnFieldDtrService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OnFieldDtrWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        Storage::fake('local');
        Storage::disk('local')->put('dtr-proof-submissions/on-field.pdf', "%PDF-1.4\n%%EOF");
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

        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('date')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('month_day')->nullable();
            $table->string('type')->default('regular');
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->date('date_start');
            $table->date('date_end');
            $table->date('date_payout');
            $table->text('description')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payroll_calculation_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('uid', 20)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('company_id', 20)->nullable();
            $table->string('fingerprint_id', 191)->nullable();
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
            $table->unsignedBigInteger('on_field_dtr_submission_id')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('import_name')->nullable();
            $table->string('source_session_id')->nullable();
            $table->string('source_filename')->nullable();
            $table->string('source_file_hash')->nullable();
            $table->string('source_row_hash')->nullable();
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->nullable();
            $table->string('day_part')->nullable();
            $table->string('entry_source')->nullable();
            $table->time('schedule_start')->nullable();
            $table->time('schedule_end')->nullable();
            $table->integer('absence_minutes')->default(0);
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->text('comment')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->boolean('is_imported')->default(false);
            $table->boolean('is_holiday')->default(false);
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->string('holiday_type')->nullable();
            $table->decimal('holiday_rate', 5, 2)->nullable();
            $table->boolean('holiday_excluded')->default(false);
            $table->double('total_hours')->default(0);
            $table->integer('late')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('overtime')->default(0);
            $table->integer('early_clock_in')->default(0);
            $table->integer('credited_early_clock_in')->default(0);
            $table->integer('credited_overtime')->default(0);
            $table->double('work_hrs')->default(0);
            $table->double('credited_work_hrs')->default(0);
            $table->string('overtime_status')->default('n/a');
            $table->boolean('early_clock_in_approved')->default(false);
            $table->boolean('overtime_approved')->default(false);
            $table->string('dtr_source')->nullable();
            $table->unsignedBigInteger('dtr_source_id')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('on_field_dtr_submission_id')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('import_name')->nullable();
            $table->string('source_session_id')->nullable();
            $table->string('source_filename')->nullable();
            $table->string('source_file_hash')->nullable();
            $table->string('source_row_hash')->nullable();
            $table->string('latest_source_row_hash')->nullable();
            $table->json('latest_source_payload')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->nullable();
            $table->string('day_part')->nullable();
            $table->string('entry_source')->nullable();
            $table->time('schedule_start')->nullable();
            $table->time('schedule_end')->nullable();
            $table->integer('absence_minutes')->default(0);
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->text('comment')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->boolean('is_imported')->default(false);
            $table->boolean('is_holiday')->default(false);
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->string('holiday_type')->nullable();
            $table->decimal('holiday_rate', 5, 2)->nullable();
            $table->boolean('holiday_excluded')->default(false);
            $table->double('total_hours')->default(0);
            $table->integer('early_clock_in')->default(0);
            $table->integer('credited_early_clock_in')->default(0);
            $table->integer('credited_overtime')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('late')->default(0);
            $table->integer('early_overtime')->default(0);
            $table->integer('overtime')->default(0);
            $table->double('work_hrs')->default(0);
            $table->double('credited_work_hrs')->default(0);
            $table->string('overtime_status')->default('n/a');
            $table->boolean('early_clock_in_approved')->default(false);
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

        Schema::create('dtr_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('submitted_by_employee_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->string('submission_type')->default('dtr');
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_hash')->nullable();
            $table->boolean('is_new')->default(true);
            $table->string('status')->default('pending');
            $table->text('comments')->nullable();
            $table->text('description')->nullable();
            $table->text('review_remarks')->nullable();
            $table->text('reviewer_remarks')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('generated_dtr_id')->nullable();
            $table->unsignedBigInteger('generated_visible_dtr_id')->nullable();
            $table->string('employee_name_snapshot')->nullable();
            $table->string('employee_company_id_snapshot')->nullable();
            $table->string('branch_name_snapshot')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sic_rc_dtr_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('imported_by_employee_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('import_name')->nullable();
            $table->string('source_filename')->nullable();
            $table->integer('imported_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->string('status')->default('completed');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_submission_uses_the_bound_employee_and_branch_as_the_authoritative_identity(): void
    {
        [$branch, $employee, $period] = $this->context();

        $submission = app(OnFieldDtrService::class)->submit($employee, [
            ...$this->submissionData($period),
            'employee_id' => 999999,
            'branch_id' => 999999,
        ]);

        $this->assertSame($employee->id, $submission->employee_id);
        $this->assertSame($branch->id, $submission->branch_id);
        $this->assertSame($employee->company_id, $submission->employee_company_id_snapshot);
        $this->assertSame('Test Branch', $submission->branch_name_snapshot);
        $this->assertSame(DtrSubmission::STATUS_PENDING, $submission->status);
        $this->assertDatabaseCount('dtrs', 0);
        $this->assertDatabaseCount('employee_visible_dtrs', 0);
    }

    public function test_identical_pending_or_approved_request_cannot_be_submitted_twice(): void
    {
        [, $employee, $period] = $this->context();
        $data = $this->submissionData($period);

        app(OnFieldDtrService::class)->submit($employee, $data);

        $this->expectException(ValidationException::class);

        app(OnFieldDtrService::class)->submit($employee, $data);
    }

    public function test_hr_approval_applies_punches_to_official_and_employee_visible_dtrs(): void
    {
        [$branch, $employee, $period] = $this->context();
        $reviewer = User::factory()->create(['role' => 'hr']);

        $submission = app(OnFieldDtrService::class)->submit($employee, $this->submissionData($period));

        $approved = app(OnFieldDtrService::class)->approve($submission, $reviewer, 'Signed itinerary verified.');

        $this->assertSame(DtrSubmission::STATUS_APPROVED, $approved->status);
        $this->assertSame($reviewer->id, $approved->reviewed_by_user_id);
        $this->assertSame('Signed itinerary verified.', $approved->reviewer_remarks);
        $this->assertNotNull($approved->reviewed_at);

        $this->assertDatabaseHas('dtrs', [
            'on_field_dtr_submission_id' => $submission->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-08-20',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-20',
            'time_out' => '17:00:00',
        ]);

        $this->assertDatabaseHas('employee_visible_dtrs', [
            'id' => $approved->generated_visible_dtr_id,
            'on_field_dtr_submission_id' => $submission->id,
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-08-20',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-20',
            'time_out' => '17:00:00',
        ]);
    }

    public function test_hr_rejection_marks_record_without_writing_attendance_entries(): void
    {
        [, $employee, $period] = $this->context();
        $reviewer = User::factory()->create(['role' => 'hr']);
        $submission = app(OnFieldDtrService::class)->submit($employee, $this->submissionData($period));

        $rejected = app(OnFieldDtrService::class)->reject($submission, $reviewer, 'Trip ticket lacks supervisor sign-off.');

        $this->assertSame(DtrSubmission::STATUS_REJECTED, $rejected->status);
        $this->assertSame($reviewer->id, $rejected->reviewed_by_user_id);
        $this->assertSame('Trip ticket lacks supervisor sign-off.', $rejected->reviewer_remarks);
        $this->assertDatabaseCount('dtrs', 0);
        $this->assertDatabaseCount('employee_visible_dtrs', 0);
    }

    public function test_reimporting_same_period_and_branch_preserves_on_field_dtr_punches(): void
    {
        [$branch, $employee, $period] = $this->context();
        $reviewer = User::factory()->create(['role' => 'hr']);
        $submission = app(OnFieldDtrService::class)->submit($employee, $this->submissionData($period));
        $approved = app(OnFieldDtrService::class)->approve($submission, $reviewer, 'Approved.');

        $visibleDtrService = app(EmployeeVisibleDtrImportService::class);
        $firstImportRows = [[
            'Batch ID' => 'batch-1',
            'Period ID' => $period->id,
            'Branch ID' => $branch->id,
            'Fingerprint ID' => $employee->fingerprint_id,
            'Date In' => '2026-08-19',
            'Time In' => '08:00:00',
            'Date Out' => '2026-08-19',
            'Time Out' => '17:00:00',
            'Schedule Type' => 'Regular',
            'Schedule Start' => '08:00:00',
            'Schedule End' => '17:00:00',
        ]];
        $visibleDtrService->importRows($firstImportRows, 'batch-1');

        $secondImportRows = [[
            'Batch ID' => 'batch-2',
            'Period ID' => $period->id,
            'Branch ID' => $branch->id,
            'Fingerprint ID' => $employee->fingerprint_id,
            'Date In' => '2026-08-21',
            'Time In' => '08:00:00',
            'Date Out' => '2026-08-21',
            'Time Out' => '17:00:00',
            'Schedule Type' => 'Regular',
            'Schedule Start' => '08:00:00',
            'Schedule End' => '17:00:00',
        ]];
        $visibleDtrService->importRows($secondImportRows, 'batch-2');

        $this->assertDatabaseHas('employee_visible_dtrs', [
            'id' => $approved->generated_visible_dtr_id,
            'on_field_dtr_submission_id' => $submission->id,
            'employee_id' => $employee->id,
            'date_in' => '2026-08-20',
        ]);
    }

    private function context(): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Test Branch',
            'branch_address' => 'Test Address',
            'mobile_no' => '09123456789',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);

        $employee = Employee::query()->create([
            'uid' => '0001',
            'firstname' => 'Juan',
            'middlename' => 'Dela',
            'lastname' => 'Cruz',
            'branch_id' => $branch->id,
            'fingerprint_id' => '0001',
            'employment_type' => 'Permanent',
            'rate_type' => 'daily',
            'daily_rate' => 500,
            'is_station_manager' => true,
            'managed_branches' => [['branch_id' => $branch->id]],
        ]);

        $period = PayrollPeriod::query()->create([
            'title' => 'Aug 11 - 25, 2026',
            'date_start' => '2026-08-11',
            'date_end' => '2026-08-25',
            'date_payout' => '2026-08-31',
            'description' => 'Test period',
            'is_locked' => false,
        ]);

        return [$branch, $employee, $period];
    }

    private function submissionData(PayrollPeriod $period): array
    {
        return [
            'payroll_period_id' => $period->id,
            'date_in' => '2026-08-20',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-20',
            'time_out' => '17:00:00',
            'proof_file' => 'dtr-proof-submissions/on-field.pdf',
            'description' => 'Field maintenance work at site.',
        ];
    }

    private function biometricRow(Employee $employee, string $timestamp): array
    {
        return [
            'uid' => (int) $employee->uid,
            'timestamp' => $timestamp,
            'state' => 1,
            'type' => 1,
        ];
    }
}
