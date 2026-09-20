<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Models\SicRcDtrImport;
use App\Services\SicRcDtrImportDeletionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SicRcDtrImportDeletionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
    }

    protected function createTestTables(): void
    {
        Schema::dropAllTables();

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

        Schema::create('dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
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
            $table->integer('total_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->string('status')->default('completed');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

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
