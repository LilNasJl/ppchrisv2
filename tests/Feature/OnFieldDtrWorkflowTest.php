<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DtrSubmission;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use App\Models\User;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use App\Services\OnFieldDtrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class OnFieldDtrWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put('dtr-proof-submissions/on-field.pdf', "%PDF-1.4\n%%EOF");
    }

    public function test_submission_uses_the_bound_employee_and_branch_as_the_authoritative_identity(): void
    {
        [$branch, $employee, $period, $account] = $this->context();

        $submission = app(OnFieldDtrService::class)->submit($account, [
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
        [, , $period, $account] = $this->context();
        $data = $this->submissionData($period);

        app(OnFieldDtrService::class)->submit($account, $data);

        $this->expectException(ValidationException::class);

        app(OnFieldDtrService::class)->submit($account, $data);
    }

    public function test_approval_creates_linked_official_and_employee_visible_dtr_records_once(): void
    {
        [$branch, $employee, $period, $account] = $this->context();
        $reviewer = $this->reviewer();
        $submission = app(OnFieldDtrService::class)->submit($account, $this->submissionData($period));

        $approved = app(OnFieldDtrService::class)->approve($submission, $reviewer, 'Proof verified.');

        $this->assertSame(DtrSubmission::STATUS_APPROVED, $approved->status);
        $this->assertNotNull($approved->reviewed_at);
        $this->assertNotNull($approved->generated_dtr_id);
        $this->assertNotNull($approved->generated_visible_dtr_id);
        $this->assertDatabaseHas('dtrs', [
            'id' => $approved->generated_dtr_id,
            'on_field_dtr_submission_id' => $approved->id,
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => $employee->fingerprint_id,
            'schedule_type' => 'Regular',
            'entry_source' => 'on_field_dtr',
            'is_absent' => false,
        ]);
        $this->assertDatabaseHas('employee_visible_dtrs', [
            'id' => $approved->generated_visible_dtr_id,
            'on_field_dtr_submission_id' => $approved->id,
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
        ]);

        try {
            app(OnFieldDtrService::class)->approve($approved, $reviewer);
            $this->fail('A reviewed request was approved twice.');
        } catch (\DomainException) {
            $this->assertDatabaseCount('dtrs', 1);
            $this->assertDatabaseCount('employee_visible_dtrs', 1);
        }
    }

    public function test_rejection_keeps_the_request_for_audit_without_creating_dtr(): void
    {
        [, , $period, $account] = $this->context();
        $submission = app(OnFieldDtrService::class)->submit($account, $this->submissionData($period));

        $rejected = app(OnFieldDtrService::class)->reject($submission, $this->reviewer(), 'The proof is not sufficient.');

        $this->assertSame(DtrSubmission::STATUS_REJECTED, $rejected->status);
        $this->assertSame('The proof is not sufficient.', $rejected->reviewer_remarks);
        $this->assertDatabaseCount('dtrs', 0);
        $this->assertDatabaseCount('employee_visible_dtrs', 0);
    }

    public function test_controlled_delete_removes_both_generated_records_and_preserves_submission_audit(): void
    {
        [, , $period, $account] = $this->context();
        $reviewer = $this->reviewer();
        $submission = app(OnFieldDtrService::class)->submit($account, $this->submissionData($period));
        $approved = app(OnFieldDtrService::class)->approve($submission, $reviewer);
        $official = $approved->generatedDtr;

        app(OnFieldDtrService::class)->deleteGeneratedDtr($official, $reviewer);

        $this->assertDatabaseMissing('dtrs', ['on_field_dtr_submission_id' => $approved->id]);
        $this->assertDatabaseMissing('employee_visible_dtrs', ['on_field_dtr_submission_id' => $approved->id]);
        $this->assertDatabaseHas('dtr_submissions', [
            'id' => $approved->id,
            'status' => DtrSubmission::STATUS_APPROVED,
            'generated_dtr_deleted_by_user_id' => $reviewer->id,
        ]);
        $this->assertNotNull($approved->fresh()->generated_dtr_deleted_at);
    }

    public function test_biometric_reimport_cannot_overwrite_an_approved_on_field_record(): void
    {
        [$branch, $employee, $period, $account] = $this->context();
        $submission = app(OnFieldDtrService::class)->submit($account, $this->submissionData($period));
        $approved = app(OnFieldDtrService::class)->approve($submission, $this->reviewer());

        $result = app(EmployeeVisibleDtrImportService::class)->importRows([[
            'Batch ID' => 'REIMPORT-BATCH',
            'Period ID' => $period->id,
            'Branch ID' => $branch->id,
            'Fingerprint ID' => $employee->fingerprint_id,
            'Date In' => '2026-08-12',
            'Time In' => '08:00:00',
            'Date Out' => '2026-08-12',
            'Time Out' => '18:00:00',
            'Schedule Type' => 'Regular',
            'Schedule Start' => '08:00:00',
            'Schedule End' => '18:00:00',
        ]], 'On Field overlap');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertDatabaseCount('employee_visible_dtrs', 1);
        $this->assertDatabaseHas('employee_visible_dtrs', [
            'id' => $approved->generated_visible_dtr_id,
            'on_field_dtr_submission_id' => $approved->id,
            'batch_id' => 'ONFIELD-'.$approved->publicKey(),
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
            'reg_sched_end' => '18:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);
        $employee = Employee::query()->create([
            'uid' => '0001',
            'firstname' => 'Juan',
            'middlename' => 'Dela',
            'lastname' => 'Cruz',
            'branch_id' => $branch->id,
            'fingerprint_id' => '123456',
            'employment_type' => 'Permanent',
            'rate_type' => 'daily',
            'daily_rate' => 500,
        ]);
        $period = PayrollPeriod::query()->create([
            'title' => 'Aug 11 - 25, 2026',
            'date_start' => '2026-08-11',
            'date_end' => '2026-08-25',
            'date_payout' => '2026-08-31',
            'description' => 'Open test period',
            'is_locked' => false,
        ]);
        $account = SicRcAccount::query()->create([
            'employee_id' => $employee->id,
            'username' => 'field-reviewer',
            'password' => 'password',
            'biometric_devices' => [[
                'branch_id' => $branch->id,
                'branch_name' => $branch->branch_name,
            ]],
            'is_active' => true,
        ]);

        return [$branch, $employee, $period, $account];
    }

    private function reviewer(): User
    {
        return User::factory()->create([
            'username' => 'hr-reviewer-'.fake()->unique()->numberBetween(1000, 9999),
            'role' => 'hr',
        ]);
    }

    private function submissionData(PayrollPeriod $period): array
    {
        return [
            'payroll_period_id' => $period->id,
            'date_in' => '2026-08-12',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-12',
            'time_out' => '18:00:00',
            'proof_file' => 'dtr-proof-submissions/on-field.pdf',
            'description' => 'Employee worked on site while the device was offline.',
        ];
    }
}
