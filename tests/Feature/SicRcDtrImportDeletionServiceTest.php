<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Models\SicRcDtrImport;
use App\Services\SicRcDtrImportDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class SicRcDtrImportDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_batch_is_permanently_deleted_with_all_matching_history_records(): void
    {
        [$branch, $period] = $this->context();
        $completed = $this->history($branch, $period, 'BATCH-A', SicRcDtrImport::STATUS_COMPLETED, 2);
        $this->history($branch, $period, 'BATCH-A', SicRcDtrImport::STATUS_NO_CHANGES, 0);
        $this->history($branch, $period, 'BATCH-B', SicRcDtrImport::STATUS_COMPLETED, 1);

        $first = $this->entry($branch, $period, 'BATCH-A', '1001');
        $second = $this->entry($branch, $period, 'BATCH-A', '1002');
        $unrelated = $this->entry($branch, $period, 'BATCH-B', '1003');
        $second->delete();

        $deleted = app(SicRcDtrImportDeletionService::class)->delete($completed);

        $this->assertSame(['entries' => 2, 'histories' => 2], $deleted);
        $this->assertNull(EmployeeVisibleDtr::withTrashed()->find($first->id));
        $this->assertNull(EmployeeVisibleDtr::withTrashed()->find($second->id));
        $this->assertNotNull(EmployeeVisibleDtr::withTrashed()->find($unrelated->id));
        $this->assertDatabaseMissing('sic_rc_dtr_imports', ['batch_id' => 'BATCH-A']);
        $this->assertDatabaseHas('sic_rc_dtr_imports', ['batch_id' => 'BATCH-B']);
    }

    public function test_failed_or_no_change_attempt_deletes_only_its_history_record(): void
    {
        [$branch, $period] = $this->context();
        $attempt = $this->history($branch, $period, 'BATCH-A', SicRcDtrImport::STATUS_NO_CHANGES, 0);
        $entry = $this->entry($branch, $period, 'BATCH-A', '1001');

        $deleted = app(SicRcDtrImportDeletionService::class)->delete($attempt);

        $this->assertSame(['entries' => 0, 'histories' => 1], $deleted);
        $this->assertNotNull(EmployeeVisibleDtr::withTrashed()->find($entry->id));
        $this->assertDatabaseMissing('sic_rc_dtr_imports', ['id' => $attempt->id]);
    }

    /** @return array{0: Branch, 1: PayrollPeriod} */
    private function context(): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Deletion Test Branch',
            'branch_address' => 'Test Address',
            'mobile_no' => '09123456789',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '18:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);

        $period = PayrollPeriod::query()->create([
            'title' => 'Aug 11 - 25, 2026',
            'date_start' => '2026-08-11',
            'date_end' => '2026-08-25',
            'date_payout' => '2026-08-31',
            'description' => 'Deletion test period',
            'is_locked' => false,
        ]);

        return [$branch, $period];
    }

    private function history(
        Branch $branch,
        PayrollPeriod $period,
        string $batchId,
        string $status,
        int $importedRows,
    ): SicRcDtrImport {
        return SicRcDtrImport::query()->create([
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'batch_id' => $batchId,
            'import_name' => $batchId,
            'total_rows' => max(1, $importedRows),
            'imported_rows' => $importedRows,
            'status' => $status,
            'imported_at' => now(),
        ]);
    }

    private function entry(
        Branch $branch,
        PayrollPeriod $period,
        string $batchId,
        string $fingerprintId,
    ): EmployeeVisibleDtr {
        return EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => $fingerprintId,
            'batch_id' => $batchId,
            'date_in' => '2026-08-12',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-12',
            'time_out' => '18:00:00',
        ]);
    }
}
