<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use App\Services\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class EmployeeVisibleDtrImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_import_links_the_row_to_the_employee_in_the_selected_branch(): void
    {
        [$branch, $period] = $this->context();
        $employee = $this->employee($branch, '000123', '0001');

        $result = app(EmployeeVisibleDtrImportService::class)->importRows([
            $this->row($branch, $period, '123'),
        ], 'Branch preview');

        $this->assertSame(1, $result['successful']);
        $this->assertDatabaseHas('employee_visible_dtrs', [
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'fingerprint_id' => '000123',
        ]);
    }

    public function test_preview_import_accepts_a_fingerprint_owned_by_another_branch(): void
    {
        [$selectedBranch, $period] = $this->context();
        $otherBranch = $this->branch('Other Branch');
        $employee = $this->employee($otherBranch, '551122', '0002');
        $user = User::withoutEvents(fn (): User => User::query()->create([
            'name' => 'Cross Station Employee', 'username' => 'cross-station-employee',
            'email' => 'cross-station@example.test', 'password' => 'password',
            'role' => 'employee',
        ]));
        $employee->update(['user_id' => $user->id]);

        $result = app(EmployeeVisibleDtrImportService::class)->importRows([
            $this->row($selectedBranch, $period, '551122'),
        ], 'Cross-station preview');

        $this->assertSame(1, $result['successful']);
        $this->assertDatabaseHas('employee_visible_dtrs', [
            'employee_id' => $employee->id,
            'branch_id' => $selectedBranch->id,
        ]);
        $this->assertDatabaseHas('dtrs', [
            'employee_id' => $employee->id,
            'branch_id' => $otherBranch->id,
            'source_branch_id' => $selectedBranch->id,
        ]);
        $payrollRows = app(PayrollCalculator::class)->rows($period, $otherBranch->id);
        $this->assertCount(1, $payrollRows);
        $this->assertGreaterThan(0, (float) $payrollRows->sole()['days_worked']);
        $this->assertCount(0, app(PayrollCalculator::class)->rows($period, $selectedBranch->id));
    }

    public function test_preview_import_rejects_an_unknown_fingerprint(): void
    {
        [$branch, $period] = $this->context();

        $result = app(EmployeeVisibleDtrImportService::class)->importRows([
            $this->row($branch, $period, '999999'),
        ], 'Unknown employee preview');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('does not match an active employee', $result['errors'][0]['message']);
        $this->assertDatabaseCount('employee_visible_dtrs', 0);
    }

    public function test_preview_import_rejects_ambiguous_fingerprint_ids_across_branches(): void
    {
        [$station, $period] = $this->context();
        $this->employee($station, '777', '101');
        $this->employee($this->branch('Another Branch'), '000777', '102');

        $result = app(EmployeeVisibleDtrImportService::class)->importRows([
            $this->row($station, $period, '777'),
        ], 'Ambiguous fingerprint');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('multiple active employees', $result['errors'][0]['message']);
        $this->assertDatabaseCount('employee_visible_dtrs', 0);
    }

    public function test_legacy_unlinked_cross_station_record_is_still_credited_to_unique_employee(): void
    {
        [$station, $period] = $this->context();
        $home = $this->branch('Main Office');
        $employee = $this->employee($home, '00888', '103');
        $user = User::withoutEvents(fn (): User => User::query()->create([
            'name' => 'Legacy Cross Station', 'username' => 'legacy-cross-station',
            'email' => 'legacy-cross@example.test', 'password' => 'password',
            'role' => 'employee',
        ]));
        $employee->update(['user_id' => $user->id]);
        Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $station->id,
            'fingerprint_id' => '888',
            'date_in' => '2026-08-12', 'time_in' => '08:00:00',
            'date_out' => '2026-08-12', 'time_out' => '18:00:00',
            'schedule_type' => 'Regular',
            'schedule_start' => '08:00:00', 'schedule_end' => '18:00:00',
            'day_part' => 'whole_day', 'entry_source' => 'imported',
            'is_imported' => true, 'work_hrs' => 540, 'credited_work_hrs' => 540,
            'daily_rate' => 500,
        ]);

        $this->assertCount(1, Dtr::query()->whereNull('employee_id')
            ->whereIn('fingerprint_id', ['00888', '888'])->get());
        $this->assertSame(1, Employee::query()->activeEmployment()->get()
            ->filter(fn (Employee $candidate): bool => (ltrim((string) $candidate->fingerprint_id, '0') ?: '0') === '888')->count());
        $this->assertSame(1, Employee::query()->activeEmployment()->get(['fingerprint_id', 'uid'])
            ->map(fn (Employee $candidate): string => ltrim((string) $candidate->fingerprint_id, '0') ?: '0')
            ->countBy()->get('888'));
        $this->assertCount(1, Dtr::query()->whereNull('employee_id')
            ->whereIn('fingerprint_id', ['00888', '888'])->get()
            ->groupBy(fn (Dtr $dtr): string => ltrim((string) $dtr->fingerprint_id, '0') ?: '0')
            ->get('888'));
        app(PayrollCalculator::class)->preloadEmployeesForPeriod(collect([$employee]), $period);
        $this->assertCount(1, $employee->getRelation('preloadedDtrs'));
        $rows = app(PayrollCalculator::class)->rows($period, $home->id);
        $this->assertCount(1, $rows);
        $this->assertGreaterThan(0, (float) $rows->sole()['days_worked']);
    }

    private function context(): array
    {
        return [
            $branch = $this->branch('Test Branch'),
            PayrollPeriod::query()->create([
                'title' => 'Aug 11 - 25, 2026',
                'date_start' => '2026-08-11',
                'date_end' => '2026-08-25',
                'date_payout' => '2026-08-31',
                'description' => 'Open test period',
                'is_locked' => false,
            ]),
        ];
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

    private function employee(Branch $branch, string $fingerprintId, string $uid): Employee
    {
        return Employee::query()->create([
            'uid' => $uid,
            'firstname' => 'Import',
            'middlename' => 'Test',
            'lastname' => 'Employee',
            'branch_id' => $branch->id,
            'fingerprint_id' => $fingerprintId,
            'employment_type' => 'Permanent',
            'rate_type' => 'daily',
            'daily_rate' => 500,
        ]);
    }

    private function row(Branch $branch, PayrollPeriod $period, string $fingerprintId): array
    {
        return [
            'Batch ID' => 'PREVIEW-BATCH',
            'Period ID' => $period->id,
            'Branch ID' => $branch->id,
            'Fingerprint ID' => $fingerprintId,
            'Date In' => '2026-08-12',
            'Time In' => '08:00:00',
            'Date Out' => '2026-08-12',
            'Time Out' => '18:00:00',
            'Schedule Type' => 'Regular',
            'Schedule Start' => '08:00:00',
            'Schedule End' => '18:00:00',
        ];
    }
}
