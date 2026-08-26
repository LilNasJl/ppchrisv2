<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\Imports\EmployeeVisibleDtrImportService;
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

    public function test_preview_import_rejects_a_fingerprint_owned_by_another_branch(): void
    {
        [$selectedBranch, $period] = $this->context();
        $otherBranch = $this->branch('Other Branch');
        $this->employee($otherBranch, '551122', '0002');

        $result = app(EmployeeVisibleDtrImportService::class)->importRows([
            $this->row($selectedBranch, $period, '551122'),
        ], 'Wrong branch preview');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('not Test Branch', $result['errors'][0]['message']);
        $this->assertDatabaseCount('employee_visible_dtrs', 0);
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
