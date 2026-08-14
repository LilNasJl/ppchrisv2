<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Services\Imports\DtrImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class DtrImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_to_punch_accepts_its_four_naturally_empty_fields(): void
    {
        [$branch, $period] = $this->importContext();
        $row = $this->row($branch, $period, [
            'Schedule Type' => 'Forgot to Punch',
            'Date Out' => '',
            'Time Out' => '',
            'Schedule Start' => '',
            'Schedule End' => '',
        ]);

        $result = app(DtrImportService::class)->importRows([$row], 'Forgot punch import');

        $this->assertSame(1, $result['successful']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseHas('dtrs', [
            'fingerprint_id' => '999999',
            'schedule_type' => 'Forgot to Punch',
            'date_out' => null,
            'time_out' => null,
            'schedule_start' => null,
            'schedule_end' => null,
            'is_absent' => 1,
            'is_imported' => 1,
        ]);
    }

    public function test_normal_rows_still_require_timeout_and_schedule_fields(): void
    {
        [$branch, $period] = $this->importContext();
        $row = $this->row($branch, $period, [
            'Date Out' => '',
            'Time Out' => '',
            'Schedule Start' => '',
            'Schedule End' => '',
        ]);

        $result = app(DtrImportService::class)->importRows([$row], 'Invalid regular row');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertCount(4, $result['errors']);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_date_in_must_belong_to_the_selected_open_period(): void
    {
        [$branch, $period] = $this->importContext();
        $row = $this->row($branch, $period, [
            'Date In' => '2026-08-01',
            'Date Out' => '2026-08-01',
        ]);

        $result = app(DtrImportService::class)->importRows([$row], 'Wrong period');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('within the selected payroll period', $result['errors'][0]['message']);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_locked_period_rejects_imports(): void
    {
        [$branch, $period] = $this->importContext(locked: true);

        $result = app(DtrImportService::class)->importRows([
            $this->row($branch, $period),
        ], 'Locked period');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('locked', $result['errors'][0]['message']);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_non_overlapping_same_day_shifts_are_both_imported(): void
    {
        [$branch, $period] = $this->importContext();
        $morning = $this->row($branch, $period, [
            'Time In' => '08:00:00',
            'Time Out' => '12:00:00',
        ]);
        $afternoon = $this->row($branch, $period, [
            'Time In' => '13:00:00',
            'Time Out' => '17:00:00',
            'Schedule Type' => 'Shift2',
            'Schedule Start' => '13:00:00',
            'Schedule End' => '18:00:00',
        ]);

        $result = app(DtrImportService::class)->importRows([$morning, $afternoon], 'Split shifts');

        $this->assertSame(2, $result['successful']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseCount('dtrs', 2);
    }

    public function test_completed_session_followed_by_later_forgot_to_punch_is_imported(): void
    {
        [$branch, $period] = $this->importContext();
        $period->update(['date_end' => '2026-08-15']);

        $completed = $this->row($branch, $period, [
            'Fingerprint ID' => '970824',
            'Name' => 'RIZZEL',
            'Date In' => '2026-08-03',
            'Time In' => '08:01:57',
            'Date Out' => '2026-08-03',
            'Time Out' => '15:03:55',
        ]);
        $forgot = $this->row($branch, $period, [
            'Fingerprint ID' => '970824',
            'Name' => 'RIZZEL',
            'Date In' => '2026-08-03',
            'Time In' => '18:01:30',
            'Date Out' => '',
            'Time Out' => '',
            'Schedule Type' => 'Forgot to Punch',
            'Schedule Start' => '',
            'Schedule End' => '',
        ]);

        $result = app(DtrImportService::class)->importRows([$completed, $forgot], 'Multiple daily sessions');

        $this->assertSame(2, $result['successful']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseHas('dtrs', [
            'fingerprint_id' => '970824',
            'date_in' => '2026-08-03',
            'time_in' => '08:01:57',
            'time_out' => '15:03:55',
        ]);
        $this->assertDatabaseHas('dtrs', [
            'fingerprint_id' => '970824',
            'date_in' => '2026-08-03',
            'time_in' => '18:01:30',
            'time_out' => null,
            'schedule_type' => 'Forgot to Punch',
        ]);
    }

    public function test_forgot_to_punch_starting_inside_completed_session_is_rejected(): void
    {
        [$branch, $period] = $this->importContext();
        $completed = $this->row($branch, $period, [
            'Time In' => '08:00:00',
            'Time Out' => '15:00:00',
        ]);
        $overlappingForgot = $this->row($branch, $period, [
            'Time In' => '14:30:00',
            'Date Out' => '',
            'Time Out' => '',
            'Schedule Type' => 'Forgot to Punch',
            'Schedule Start' => '',
            'Schedule End' => '',
        ]);

        $result = app(DtrImportService::class)->importRows([$completed, $overlappingForgot], 'Overlapping open punch');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(2, $result['errors'][0]['row']);
        $this->assertStringContainsString('overlaps', $result['errors'][0]['message']);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_completed_session_after_an_unresolved_punch_is_rejected(): void
    {
        [$branch, $period] = $this->importContext();
        $open = $this->row($branch, $period, [
            'Time In' => '08:00:00',
            'Date Out' => '',
            'Time Out' => '',
            'Schedule Type' => 'Forgot to Punch',
            'Schedule Start' => '',
            'Schedule End' => '',
        ]);
        $laterCompleted = $this->row($branch, $period, [
            'Time In' => '13:00:00',
            'Time Out' => '18:00:00',
        ]);

        $result = app(DtrImportService::class)->importRows([$open, $laterCompleted], 'Unresolved earlier punch');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(2, $result['errors'][0]['row']);
        $this->assertStringContainsString('earlier punch remains open', $result['errors'][0]['message']);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_forgot_to_punch_does_not_block_a_completed_session_on_the_next_day(): void
    {
        [$branch, $period] = $this->importContext();
        $period->update([
            'date_start' => '2026-07-28',
            'date_end' => '2026-08-15',
        ]);

        $forgot = $this->row($branch, $period, [
            'Fingerprint ID' => '830729',
            'Name' => 'EFREN',
            'Date In' => '2026-07-28',
            'Time In' => '18:00:49',
            'Date Out' => '',
            'Time Out' => '',
            'Schedule Type' => 'Forgot to Punch',
            'Schedule Start' => '',
            'Schedule End' => '',
        ]);
        $nextDay = $this->row($branch, $period, [
            'Fingerprint ID' => '830729',
            'Name' => 'EFREN',
            'Date In' => '2026-07-29',
            'Time In' => '07:49:36',
            'Date Out' => '2026-07-29',
            'Time Out' => '18:00:04',
        ]);

        $result = app(DtrImportService::class)->importRows([$forgot, $nextDay], 'Calendar cutoff');

        $this->assertSame(2, $result['successful']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseCount('dtrs', 2);
    }

    public function test_forgot_to_punch_still_blocks_a_later_completed_session_on_the_same_day(): void
    {
        [$branch, $period] = $this->importContext();
        $forgot = $this->row($branch, $period, [
            'Time In' => '08:00:00',
            'Date Out' => '',
            'Time Out' => '',
            'Schedule Type' => 'Forgot to Punch',
            'Schedule Start' => '',
            'Schedule End' => '',
        ]);
        $laterCompleted = $this->row($branch, $period, [
            'Time In' => '13:00:00',
            'Time Out' => '18:00:00',
        ]);

        $result = app(DtrImportService::class)->importRows([$forgot, $laterCompleted], 'Same-day conflict');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(2, $result['errors'][0]['row']);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_broken_shifts_are_imported_as_distinct_half_day_segments(): void
    {
        [$branch, $period] = $this->importContext();
        $firstSegment = $this->row($branch, $period, [
            'Time In' => '08:00:00',
            'Time Out' => '12:00:00',
            'Schedule Type' => 'Broken Shift 1',
            'Schedule Start' => '08:00:00',
            'Schedule End' => '12:00:00',
        ]);
        $secondSegment = $this->row($branch, $period, [
            'Time In' => '13:00:00',
            'Time Out' => '17:00:00',
            'Schedule Type' => 'Broken Shift 2',
            'Schedule Start' => '13:00:00',
            'Schedule End' => '17:00:00',
        ]);

        $result = app(DtrImportService::class)->importRows([$firstSegment, $secondSegment], 'Broken shifts');

        $this->assertSame(2, $result['successful']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseHas('dtrs', [
            'schedule_type' => 'Brkn1',
            'day_part' => 'morning',
        ]);
        $this->assertDatabaseHas('dtrs', [
            'schedule_type' => 'Brkn2',
            'day_part' => 'afternoon',
        ]);
    }

    public function test_overlapping_same_day_punches_cancel_the_entire_batch(): void
    {
        [$branch, $period] = $this->importContext();
        $first = $this->row($branch, $period, [
            'Time In' => '08:00:00',
            'Time Out' => '12:00:00',
        ]);
        $overlap = $this->row($branch, $period, [
            'Time In' => '11:30:00',
            'Time Out' => '15:00:00',
            'Schedule Type' => 'Shift2',
            'Schedule Start' => '11:00:00',
            'Schedule End' => '18:00:00',
        ]);

        $result = app(DtrImportService::class)->importRows([$first, $overlap], 'Overlap');

        $this->assertSame(0, $result['successful']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(2, $result['errors'][0]['row']);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_regular_morning_half_day_is_imported_without_missing_afternoon_undertime(): void
    {
        [$branch, $period] = $this->importContext();

        $result = app(DtrImportService::class)->importRows([
            $this->row($branch, $period, [
                'Time In' => '08:15:00',
                'Time Out' => '12:00:00',
            ]),
        ], 'Morning half day');

        $this->assertSame(1, $result['successful']);
        $this->assertDatabaseHas('dtrs', [
            'day_part' => 'morning',
            'schedule_start' => '08:00:00',
            'schedule_end' => '12:00:00',
            'late' => 15,
            'undertime' => 0,
        ]);
    }

    public function test_regular_afternoon_half_day_is_imported_without_missing_morning_late(): void
    {
        [$branch, $period] = $this->importContext();

        $result = app(DtrImportService::class)->importRows([
            $this->row($branch, $period, [
                'Time In' => '13:00:00',
                'Time Out' => '17:30:00',
            ]),
        ], 'Afternoon half day');

        $this->assertSame(1, $result['successful']);
        $this->assertDatabaseHas('dtrs', [
            'day_part' => 'afternoon',
            'schedule_start' => '13:00:00',
            'schedule_end' => '18:00:00',
            'late' => 0,
            'undertime' => 30,
        ]);
    }

    public function test_regular_morning_and_afternoon_rows_import_in_the_same_batch(): void
    {
        [$branch, $period] = $this->importContext();
        $morning = $this->row($branch, $period, [
            'Time In' => '08:00:00',
            'Time Out' => '12:00:00',
        ]);
        $afternoon = $this->row($branch, $period, [
            'Time In' => '13:00:00',
            'Time Out' => '18:00:00',
        ]);

        $result = app(DtrImportService::class)->importRows([$morning, $afternoon], 'Complete split day');

        $this->assertSame(2, $result['successful']);
        $this->assertDatabaseHas('dtrs', ['day_part' => 'morning']);
        $this->assertDatabaseHas('dtrs', ['day_part' => 'afternoon']);
    }

    public function test_break_only_regular_record_is_marked_for_review_and_not_calculated(): void
    {
        [$branch, $period] = $this->importContext();

        $result = app(DtrImportService::class)->importRows([
            $this->row($branch, $period, [
                'Time In' => '12:01:00',
                'Time Out' => '12:59:00',
            ]),
        ], 'Break-only record');

        $this->assertSame(1, $result['successful']);
        $this->assertDatabaseHas('dtrs', [
            'day_part' => 'unclassified',
            'late' => 0,
            'undertime' => 0,
            'work_hrs' => 0,
        ]);
    }

    public function test_unmapped_fingerprint_id_is_kept_as_an_import_identifier(): void
    {
        [$branch, $period] = $this->importContext();

        $result = app(DtrImportService::class)->importRows([
            $this->row($branch, $period, ['Fingerprint ID' => '765432']),
        ], 'Unmapped fingerprint');

        $this->assertSame(1, $result['successful']);
        $this->assertDatabaseHas('dtrs', ['fingerprint_id' => '765432']);
    }

    public function test_exact_duplicate_rows_in_one_backup_are_imported_once(): void
    {
        [$branch, $period] = $this->importContext();
        $row = $this->row($branch, $period);

        $result = app(DtrImportService::class)->importRows([$row, $row], 'Duplicate backup rows');

        $this->assertSame(1, $result['successful']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseCount('dtrs', 1);
    }

    public function test_reimporting_an_exact_row_skips_it_without_creating_a_second_record(): void
    {
        [$branch, $period] = $this->importContext();
        $row = $this->row($branch, $period);

        $first = app(DtrImportService::class)->importRows([$row], 'First backup');
        $second = app(DtrImportService::class)->importRows([$row], 'Repeated backup');

        $this->assertSame(1, $first['successful']);
        $this->assertSame(0, $second['successful']);
        $this->assertSame(1, $second['skipped']);
        $this->assertSame(0, $second['failed']);
        $this->assertDatabaseCount('dtrs', 1);
    }

    public function test_direct_bin_import_stores_source_audit_metadata(): void
    {
        [$branch, $period] = $this->importContext();
        $fileHash = str_repeat('a', 64);

        $result = app(DtrImportService::class)->importRows([
            $this->row($branch, $period, [
                'Source Session ID' => 'session-20260721',
                'Source Filename' => 'payroll.bin',
                'Source File Hash' => $fileHash,
            ]),
        ], 'Direct BIN import');

        $this->assertSame(1, $result['successful']);
        $this->assertDatabaseHas('dtrs', [
            'source_session_id' => 'session-20260721',
            'source_filename' => 'payroll.bin',
            'source_file_hash' => $fileHash,
        ]);
    }

    /**
     * @return array{0: Branch, 1: PayrollPeriod}
     */
    private function importContext(bool $locked = false): array
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

        $period = PayrollPeriod::query()->create([
            'title' => 'Jul 20 - 31, 2026',
            'date_start' => '2026-07-20',
            'date_end' => '2026-07-31',
            'date_payout' => '2026-08-05',
            'description' => 'Import test period',
            'is_locked' => $locked,
        ]);

        return [$branch, $period];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(Branch $branch, PayrollPeriod $period, array $overrides = []): array
    {
        return array_replace([
            'Batch ID' => 'TEST-BATCH',
            'Period ID' => $period->id,
            'Branch ID' => $branch->id,
            'Fingerprint ID' => '999999',
            'Name' => 'Unmapped Employee',
            'Date In' => '2026-07-21',
            'Time In' => '08:00:00',
            'Date Out' => '2026-07-21',
            'Time Out' => '18:00:00',
            'Schedule Type' => 'Regular',
            'Schedule Start' => '08:00:00',
            'Schedule End' => '18:00:00',
        ], $overrides);
    }
}
