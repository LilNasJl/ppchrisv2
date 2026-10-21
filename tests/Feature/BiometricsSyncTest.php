<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Station\StationBiometricsSync;
use App\Filament\Employee\Pages\Station\StationBiometricsSyncDetails;
use App\Filament\Pages\BiometricsSync;
use App\Filament\Pages\BiometricsSyncDetails;
use App\Filament\Pages\DtrManage;
use App\Models\BiometricsSyncRequest;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\LeaveApprovalWorkflow;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Biometrics\BiometricDtrBinCodec;
use App\Services\Biometrics\BiometricsSyncService;
use App\Services\Biometrics\SyncSignature;
use App\Services\DtrAttendanceUnitService;
use App\Services\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BiometricsSyncTest extends TestCase
{
    use RefreshDatabase;

    private PayrollPeriod $period;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['biometrics.url' => 'https://biometrics.test', 'biometrics.secret' => str_repeat('s', 40)]);
        Queue::fake();
        $branch = Branch::create(['branch_name' => 'Main Office', 'branch_address' => 'Office', 'mobile_no' => '09123456789', 'employee_id' => 0, 'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00', 'reg_sched_end' => '18:00:00', 'is_24hrs' => false, 'has_broken_time' => false]);
        Employee::create(['uid' => '1001', 'fingerprint_id' => '12345', 'firstname' => 'Test', 'lastname' => 'Employee', 'branch_id' => $branch->id, 'employment_type' => 'Permanent', 'rate_type' => 'daily', 'daily_rate' => 500]);
        $this->period = PayrollPeriod::create(['title' => 'Review payroll', 'date_start' => '2026-07-20', 'date_end' => '2026-07-31', 'date_payout' => '2026-08-05', 'is_locked' => false]);
        $this->user = User::factory()->create(['role' => 'hr']);
    }

    private function row(array $overrides = []): array
    {
        return array_replace(['uid' => 12345, 'date_in' => '2026-07-21', 'time_in' => '08:00:00', 'date_out' => '2026-07-21', 'time_out' => '18:00:00',
            'sched' => 'Regular', 'sched_start' => '08:00:00', 'sched_end' => '18:00:00', 'day_part' => 'whole_day', 'session_id' => 'review-session', 'branch_id' => 'DEVICE_20260720'], $overrides);
    }

    private function fakeServer(array $rows, string $state = 'done', bool $badChecksum = false, bool $archiveUnavailable = false): void
    {
        Http::swap(new Factory);
        $binary = (new BiometricDtrBinCodec)->encode($rows);
        Http::fake(function ($request) use ($binary, $rows, $state, $badChecksum, $archiveUnavailable) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($path === '/api/hris/devices') {
                return Http::response(['data' => [['id' => 1, 'name' => 'Maco', 'code' => 'MACO', 'online' => true, 'last_seen' => null, 'firmware_type' => 'dual_touch', 'firmware_version' => '1.2.2']]]);
            }
            if (str_ends_with($path, '/finish')) {
                return Http::response(['ok' => true]);
            }
            if (str_ends_with($path, '/file')) {
                if ($archiveUnavailable) {
                    return Http::response(['message' => 'Export is not available for import.'], 409);
                }

                return Http::response($binary, 200, ['Content-Type' => 'application/octet-stream']);
            }
            $requestId = $request->method() === 'POST' ? (json_decode($request->body(), true)['request_id'] ?? null) : basename($path);
            $sync = BiometricsSyncRequest::findOrFail($requestId);

            return Http::response(['request_id' => $sync->id, 'device_id' => 1, 'command_id' => 10, 'command' => 'DOWNLOAD_DTR_PAYROLL', 'status' => $state, 'sync_state' => 'active', 'message' => null,
                'date_from' => '2026-07-20', 'date_to' => '2026-07-31', 'received_at' => now()->toIso8601String(), 'executed_at' => $state === 'done' ? now()->toIso8601String() : null,
                'export' => ['file_name' => 'payroll.bin', 'size' => strlen($binary), 'record_count' => count($rows), 'sha256' => $badChecksum ? str_repeat('0', 64) : hash('sha256', $binary)]]);
        });
    }

    private function createSync(): BiometricsSyncRequest
    {
        return app(BiometricsSyncService::class)->create('1', $this->period->id, $this->user->id);
    }

    private function stationApprover(string $name = 'SIC Alpha'): User
    {
        $approver = User::factory()->create(['role' => 'employee', 'name' => $name]);
        $employee = Employee::query()->firstOrFail();
        $employee->forceFill(['user_id' => $approver->id])->save();
        $workflow = LeaveApprovalWorkflow::create([
            'name' => $name.' workflow', 'is_active' => true, 'version' => 1,
            'branch_id' => $employee->branch_id,
            'active_scope_key' => (string) \Illuminate\Support\Str::uuid(),
            'device_ids' => ['1'],
        ]);
        $workflow->levels()->create([
            'sequence' => 1, 'label' => 'Station approver', 'approver_employee_id' => $employee->id,
        ]);

        return $approver;
    }

    private function syncHistoryRecord(User $requester, string $status = 'success'): BiometricsSyncRequest
    {
        return BiometricsSyncRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'device_id' => '1',
            'device_name' => 'Maco',
            'active_device_key' => null,
            'requested_by' => $requester->id,
            'sync_type' => 'payroll',
            'payroll_period_id' => $this->period->id,
            'date_from' => $this->period->date_start,
            'date_to' => $this->period->date_end,
            'status' => $status,
            'message' => 'Test synchronization record.',
            'result' => [],
            'deadline_at' => now()->addMinutes(15),
        ]);
    }

    private function fakeEmployeeServer(array $rows, bool $badChecksum = false, bool $finishFails = false): void
    {
        Http::swap(new Factory);
        $binary = (new BiometricDtrBinCodec)->encode($rows);
        Http::fake(function ($request) use ($binary, $rows, $badChecksum, $finishFails) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($path === '/api/hris/devices') {
                return Http::response(['data' => [['id' => 1, 'name' => 'Maco', 'code' => 'MACO', 'online' => true]]]);
            }
            if (str_ends_with($path, '/finish')) {
                return $finishFails ? Http::response(['message' => 'Temporary'], 503) : Http::response(['ok' => true]);
            }
            if (str_ends_with($path, '/file')) {
                return Http::response($binary, 200, ['Content-Type' => 'application/octet-stream']);
            }
            $requestId = $request->method() === 'POST' ? (json_decode($request->body(), true)['request_id'] ?? null) : basename($path);
            $sync = BiometricsSyncRequest::findOrFail($requestId);

            return Http::response([
                'request_id' => $sync->id, 'device_id' => 1, 'command_id' => 20,
                'command' => 'DOWNLOAD_EMPLOYEE_DETAILS', 'status' => 'done', 'sync_state' => 'active',
                'message' => $rows ? 'Employee records uploaded.' : 'No employees registered.',
                'date_from' => null, 'date_to' => null, 'received_at' => now()->toIso8601String(), 'executed_at' => now()->toIso8601String(),
                'export' => $rows ? ['file_name' => 'employee.bin', 'size' => strlen($binary), 'record_count' => count($rows),
                    'sha256' => $badChecksum ? str_repeat('0', 64) : hash('sha256', $binary)] : null,
            ]);
        });
    }

    public function test_background_sync_imports_then_repeated_range_adds_only_new_sessions(): void
    {
        $this->fakeServer([$this->row()]);
        $sync = $this->createSync();
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('success', $sync->fresh()->status);
        $this->assertSame(1, $sync->fresh()->result['successful']);
        $this->assertNotNull($sync->fresh()->remote_acknowledged_at);
        $this->assertDatabaseCount('dtrs', 1);
        $this->assertDatabaseCount('employee_visible_dtrs', 1);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertDatabaseCount('dtrs', 1);
        $this->fakeServer([$this->row(), $this->row(['date_in' => '2026-07-22', 'date_out' => '2026-07-22', 'session_id' => 'next-session'])]);
        $second = $this->createSync();
        app(BiometricsSyncService::class)->advance($second->id);
        $this->assertSame('success', $second->fresh()->status);
        $this->assertSame(1, $second->fresh()->result['successful']);
        $this->assertSame(1, $second->fresh()->result['skipped']);
        $this->assertSame([1], $second->fresh()->result['import_skipped_export_rows']);
        $this->assertDatabaseCount('dtrs', 2);
        $this->assertDatabaseHas('dtrs', ['fingerprint_id' => '12345', 'branch_id' => Employee::first()->branch_id]);

        $master = User::where('username', 'masteradmin')->firstOrFail();
        Livewire::withQueryParams(['device' => '1', 'syncId' => $second->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->call('selectDetailTab', 'entries')
            ->call('loadExportDtrEntries')
            ->assertSet('exportDtrRows.0.import_skipped', true)
            ->assertSet('exportDtrRows.0.import_row', 1)
            ->assertSet('exportDtrRows.1.import_row', 2)
            ->assertSee('1 skipped')
            ->assertSee('flagged below')
            ->assertSee('Skipped')
            ->assertSee('This DTR session was already imported; this row was skipped.');
    }

    public function test_payroll_sync_imports_saturday_absence_as_three_hours_not_a_full_day(): void
    {
        $employee = Employee::query()->firstOrFail();
        $employee->update([
            'rate_type' => 'monthly',
            'monthly_rate' => 20800,
            'schedule_type' => 'regular',
            'hired_date' => '2026-07-01',
        ]);
        $this->fakeServer([$this->row([
            'date_in' => '2026-07-25',
            'date_out' => '2026-07-25',
            'time_in' => '',
            'time_out' => '',
            'sched' => 'Absent',
            'sched_start' => '08:00:00',
            'sched_end' => '11:00:00',
            'entry_source' => 'auto_absence',
            'absence_candidate' => true,
            'regular_schedule' => 'Regular Sched 1',
            'session_id' => 'ABS-12345-2026-07-25-whole_day',
        ])]);

        $sync = $this->createSync();
        app(BiometricsSyncService::class)->advance($sync->id);

        $this->assertSame('success', $sync->fresh()->status);
        $this->assertSame(1, $sync->fresh()->result['successful']);
        $this->assertSame(180, Dtr::query()->sole()->absence_minutes);
        $this->assertSame(1, \App\Models\EmployeeVisibleDtr::query()->where('is_absent', true)->count());
        $this->assertEquals(300, app(PayrollCalculator::class)->row($employee->fresh(), $this->period)['absent']);
    }

    public function test_payroll_sync_overtime_only_row_adds_no_workday(): void
    {
        $this->fakeServer([$this->row([
            'time_in' => '18:00:00',
            'time_out' => '19:00:00',
            'sched' => 'Overtime',
            'session_id' => 'overtime-only',
        ])]);

        $sync = $this->createSync();
        app(BiometricsSyncService::class)->advance($sync->id);

        $this->assertSame('success', $sync->fresh()->status);
        $record = Dtr::query()->sole();
        $this->assertSame('Overtime', $record->schedule_type);
        $this->assertSame(0.0, app(DtrAttendanceUnitService::class)->attendanceDays(collect([$record])));
        $this->assertEquals(0, app(PayrollCalculator::class)->row(Employee::query()->firstOrFail(), $this->period)['days_worked']);
        $page = new DtrManage;
        $page->employeeId = Employee::query()->firstOrFail()->id;
        $page->branchId = $record->branch_id;
        $page->periodId = $this->period->id;
        $this->assertSame(0.0, $page->getSummaryMetrics()['total_days_work']);
    }

    public function test_pending_session_is_deferred_and_later_completed_session_is_imported(): void
    {
        $this->fakeServer([$this->row(['time_out' => '', 'date_out' => '', 'day_part' => 'provisional'])]);
        $first = $this->createSync();
        app(BiometricsSyncService::class)->advance($first->id);
        $this->assertSame('success', $first->fresh()->status);
        $this->assertSame(1, $first->fresh()->result['pending']);
        $this->assertDatabaseCount('dtrs', 0);
        $this->fakeServer([$this->row()]);
        $second = $this->createSync();
        app(BiometricsSyncService::class)->advance($second->id);
        $this->assertDatabaseHas('dtrs', ['time_out' => '18:00:00']);
    }

    public function test_changed_completed_session_is_preserved_and_reported_for_review(): void
    {
        $this->fakeServer([$this->row()]);
        $first = $this->createSync();
        app(BiometricsSyncService::class)->advance($first->id);
        $this->fakeServer([$this->row(['time_out' => '17:00:00'])]);
        $second = $this->createSync();
        app(BiometricsSyncService::class)->advance($second->id);
        $this->assertSame('success', $second->fresh()->status);
        $this->assertSame(1, $second->fresh()->result['conflicts']);
        $this->assertDatabaseCount('dtrs', 1);
        $this->assertDatabaseHas('dtrs', ['time_out' => '18:00:00']);
    }

    public function test_one_active_request_and_expiry_are_enforced_on_the_backend(): void
    {
        $this->fakeServer([$this->row()], 'processing');
        $sync = $this->createSync();
        try {
            $this->createSync();
            $this->fail('Duplicate request accepted');
        } catch (ValidationException $exception) {
            $this->assertSame(BiometricsSyncService::DEVICE_UNAVAILABLE_MESSAGE, $exception->errors()['device'][0]);
        }
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('processing', $sync->fresh()->status);
        $sync->update(['deadline_at' => now()->subSecond()]);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('failed', $sync->fresh()->status);
        $this->assertNull($sync->fresh()->active_device_key);
    }

    public function test_an_active_command_on_one_device_does_not_block_a_different_device(): void
    {
        Http::swap(new Factory);
        Http::fake(['https://biometrics.test/api/hris/devices' => Http::response(['data' => [
            ['id' => 1, 'name' => 'Maco', 'code' => 'MACO', 'online' => true],
            ['id' => 2, 'name' => 'Tagum', 'code' => 'TAGUM', 'online' => true],
        ]])]);

        $first = app(BiometricsSyncService::class)->create('1', $this->period->id, $this->user->id);
        $second = app(BiometricsSyncService::class)->create('2', $this->period->id, $this->user->id);

        $this->assertSame('1', $first->fresh()->active_device_key);
        $this->assertSame('2', $second->fresh()->active_device_key);
        $this->assertDatabaseCount('biometrics_sync_requests', 2);
    }

    public function test_invalid_row_cancels_all_imports_and_preserves_original_data(): void
    {
        $this->fakeServer([
            $this->row(),
            $this->row(['uid' => 12345, 'time_in' => 'not-a-time']),
        ]);
        $sync = $this->createSync();
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('failed', $sync->fresh()->status);
        $this->assertDatabaseCount('dtrs', 0);
        $this->assertSame(12345, $sync->fresh()->result['row_details'][2]['uid']);
        $this->assertSame('18:00:00', $sync->fresh()->result['row_details'][2]['time_out']);
    }

    public function test_unmatched_fingerprint_rows_are_skipped_and_explained_in_the_export_list(): void
    {
        $this->fakeServer([
            $this->row(['uid' => 99998, 'name' => 'Unknown One']),
            $this->row(['uid' => 99999, 'name' => 'Unknown Two']),
            $this->row(['uid' => '', 'name' => 'Missing Fingerprint']),
        ]);
        $sync = $this->createSync();
        app(BiometricsSyncService::class)->advance($sync->id);

        $this->assertSame('success', $sync->fresh()->status);
        $this->assertSame(0, $sync->fresh()->result['successful']);
        $this->assertSame(0, $sync->fresh()->result['failed']);
        $this->assertSame(3, $sync->fresh()->result['skipped']);
        $this->assertSame(2, $sync->fresh()->result['unmatched_skipped']);
        $this->assertSame(1, $sync->fresh()->result['missing_fingerprint_skipped']);
        $this->assertStringContainsString('no active HRIS employee matches', $sync->fresh()->message);
        $this->assertDatabaseCount('dtrs', 0);
        $master = User::where('username', 'masteradmin')->firstOrFail();
        Livewire::withQueryParams(['device' => '1', 'syncId' => $sync->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->assertSee('No eligible D.T.R. records were imported.')
            ->call('selectDetailTab', 'entries')
            ->call('loadExportDtrEntries')
            ->assertSet('exportDtrLoaded', true)
            ->assertSee('Unknown One')
            ->assertSee('Skipped (Unmatched Fingerprint)')
            ->assertSee('No active HRIS employee matches fingerprint ID 99998; this exported row was skipped.')
            ->assertSee('No fingerprint ID was supplied; this exported row was skipped.');
    }

    public function test_unmatched_fingerprint_is_skipped_while_matched_dtr_still_imports(): void
    {
        $this->fakeServer([
            $this->row(['uid' => 99998, 'name' => 'Unknown Employee']),
            $this->row(['uid' => 12345, 'name' => 'Known Employee']),
        ]);
        $sync = $this->createSync();
        app(BiometricsSyncService::class)->advance($sync->id);

        $this->assertSame('success', $sync->fresh()->status);
        $this->assertSame(1, $sync->fresh()->result['successful']);
        $this->assertSame(0, $sync->fresh()->result['failed']);
        $this->assertSame(1, $sync->fresh()->result['skipped']);
        $this->assertSame(1, $sync->fresh()->result['unmatched_skipped']);
        $this->assertDatabaseCount('dtrs', 1);
        $this->assertDatabaseHas('dtrs', ['fingerprint_id' => '12345']);

        $master = User::where('username', 'masteradmin')->firstOrFail();
        Livewire::withQueryParams(['device' => '1', 'syncId' => $sync->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->call('selectDetailTab', 'entries')
            ->call('loadExportDtrEntries')
            ->assertSet('exportDtrRows.0.import_row', null)
            ->assertSet('exportDtrRows.1.import_row', 1)
            ->assertSee('Unknown Employee')
            ->assertSee('Known Employee')
            ->assertSee('No active HRIS employee matches fingerprint ID 99998; this exported row was skipped.');
    }

    public function test_older_sync_history_loads_full_dtr_fields_from_the_signed_archived_export(): void
    {
        $rows = [
            $this->row(['uid' => 11111, 'time_out' => '', 'day_part' => 'provisional']),
            $this->row(['uid' => 99999]),
        ];
        $this->fakeServer($rows);
        $sync = $this->createSync();
        $binary = (new BiometricDtrBinCodec)->encode($rows);
        $sync->update([
            'status' => 'failed',
            'active_device_key' => null,
            'message' => 'Import cancelled.',
            'result' => [
                'total' => 1,
                'failed' => 1,
                'errors' => [['row' => 1, 'message' => 'Fingerprint ID has no active employee.']],
                'export' => ['file_name' => 'payroll.bin', 'size' => strlen($binary), 'record_count' => 2, 'sha256' => hash('sha256', $binary)],
            ],
        ]);
        $master = User::where('username', 'masteradmin')->firstOrFail();

        Livewire::withQueryParams(['device' => '1', 'syncId' => $sync->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->assertSee('Loading original DTR fields')
            ->call('loadArchivedRows')
            ->assertSet('archiveLookupFailed', false)
            ->assertSee('Complete device DTR record')
            ->assertSee('99999')
            ->assertDontSee('11111')
            ->assertSee('18:00:00')
            ->assertSee('Fingerprint ID has no active employee.');
    }

    public function test_sync_detail_entries_tab_lists_all_export_rows_with_findings_and_pages(): void
    {
        $rows = array_map(fn ($index) => $this->row([
            'uid' => 90000 + $index,
            'name' => 'Employee '.$index,
            'session_id' => 'session-'.$index,
        ]), range(0, 26));
        $rows[0]['time_out'] = '';
        $rows[0]['day_part'] = 'provisional';
        $rows[0]['late'] = 4;
        $rows[0]['undertime'] = 30;
        $rows[0]['overtime'] = 60;
        $rows[0]['early'] = 15;
        $rows[0]['ot_status'] = 'Approved';
        $rows[1]['late'] = 0;
        $rows[1]['ot_status'] = 'Rejected';
        $this->fakeServer($rows);
        $sync = $this->createSync();
        $binary = (new BiometricDtrBinCodec)->encode($rows);
        $sync->update([
            'status' => 'failed',
            'active_device_key' => null,
            'result' => [
                'failed' => 1,
                'errors' => [['row' => 1, 'message' => 'Fingerprint ID has no active employee.']],
                'export' => ['file_name' => 'payroll.bin', 'size' => strlen($binary), 'record_count' => count($rows), 'sha256' => hash('sha256', $binary)],
            ],
        ]);
        $master = User::where('username', 'masteradmin')->firstOrFail();

        Livewire::withQueryParams(['device' => '1', 'syncId' => $sync->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->assertSee('DTR Entries (27)')
            ->call('selectDetailTab', 'entries')
            ->assertSee('Loading and verifying the archived device export')
            ->call('loadExportDtrEntries')
            ->assertSet('exportDtrLoaded', true)
            ->assertSet('exportDtrTotal', 27)
            ->assertSet('exportDtrRows.0.import_row', null)
            ->assertSet('exportDtrRows.1.import_row', 1)
            ->assertSee('bio-dtr-table-scroll')
            ->assertSeeHtml('<td class="bio-dtr-value bio-dtr-value--schedule" style="padding:.7rem;white-space:nowrap">Regular</td>')
            ->assertSeeHtml('<td class="bio-dtr-value bio-dtr-value--late" style="padding:.7rem;white-space:nowrap">4</td>')
            ->assertSeeHtml('<td class="bio-dtr-value bio-dtr-value--undertime" style="padding:.7rem;white-space:nowrap">30</td>')
            ->assertSeeHtml('<td class="bio-dtr-value bio-dtr-value--overtime" style="padding:.7rem;white-space:nowrap">60</td>')
            ->assertSeeHtml('<td class="bio-dtr-value bio-dtr-value--approved" style="padding:.7rem;white-space:nowrap">Approved</td>')
            ->assertSeeHtml('<td class="bio-dtr-value bio-dtr-value--muted" style="padding:.7rem;white-space:nowrap">0</td>')
            ->assertSeeHtml('<td class="bio-dtr-value bio-dtr-value--rejected" style="padding:.7rem;white-space:nowrap">Rejected</td>')
            ->assertSee('Employee 0')
            ->assertSee('Deferred')
            ->assertSee('Employee 1')
            ->assertSee('Fingerprint ID has no active employee.')
            ->assertDontSee('Employee 26')
            ->call('changeExportDtrPage', 2)
            ->assertSet('exportDtrPage', 2)
            ->assertSet('exportDtrRows.0.source_row', 26)
            ->assertSee('Employee 26')
            ->assertDontSee('Employee 0')
            ->call('showAllExportDtrEntries')
            ->assertSet('exportDtrShowAll', true)
            ->assertSet('exportDtrPage', 1)
            ->assertSet('exportDtrRows.26.source_row', 27)
            ->assertSee('Employee 26')
            ->assertSee('Showing 1–27 of 27')
            ->assertSee('Show 25 per page')
            ->call('showPagedExportDtrEntries')
            ->assertSet('exportDtrShowAll', false)
            ->assertSet('exportDtrRows.24.source_row', 25)
            ->assertDontSee('Employee 26')
            ->call('selectDetailTab', 'overview')
            ->assertDontSee('Employee 0');
    }

    public function test_failed_sync_uses_complete_saved_rejected_rows_when_live_archive_returns_409(): void
    {
        $rows = [$this->row(['uid' => 99998, 'name' => 'Rejected One']), $this->row(['uid' => 99999, 'name' => 'Rejected Two'])];
        $this->fakeServer($rows, archiveUnavailable: true);
        $sync = $this->createSync();
        $binary = (new BiometricDtrBinCodec)->encode($rows);
        $sync->update([
            'status' => 'failed',
            'active_device_key' => null,
            'result' => [
                'failed' => 2,
                'pending' => 0,
                'errors' => [['row' => 1, 'message' => 'Fingerprint unmatched.'], ['row' => 2, 'message' => 'Fingerprint unmatched.']],
                'row_details' => [1 => $rows[0], 2 => $rows[1]],
                'export' => ['file_name' => 'payroll.bin', 'size' => strlen($binary), 'record_count' => 2, 'sha256' => hash('sha256', $binary)],
            ],
        ]);
        $master = User::where('username', 'masteradmin')->firstOrFail();

        Livewire::withQueryParams(['device' => '1', 'syncId' => $sync->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->call('selectDetailTab', 'entries')
            ->call('loadExportDtrEntries')
            ->assertSet('exportDtrLoaded', true)
            ->assertSet('exportDtrSource', 'saved_rejected')
            ->assertSee('complete rejected-row snapshot')
            ->assertSee('Rejected One')
            ->assertSee('Rejected Two')
            ->assertSee('Fingerprint unmatched.');
    }

    public function test_incomplete_saved_snapshot_is_not_misrepresented_as_full_export(): void
    {
        $rows = [$this->row(['uid' => 99998]), $this->row(['uid' => 99999])];
        $this->fakeServer($rows, archiveUnavailable: true);
        $sync = $this->createSync();
        $binary = (new BiometricDtrBinCodec)->encode($rows);
        $sync->update([
            'status' => 'failed',
            'active_device_key' => null,
            'result' => [
                'failed' => 1,
                'row_details' => [1 => $rows[0]],
                'export' => ['file_name' => 'payroll.bin', 'size' => strlen($binary), 'record_count' => 2, 'sha256' => hash('sha256', $binary)],
            ],
        ]);
        $master = User::where('username', 'masteradmin')->firstOrFail();

        Livewire::withQueryParams(['device' => '1', 'syncId' => $sync->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->call('selectDetailTab', 'entries')
            ->call('loadExportDtrEntries')
            ->assertSet('exportDtrLoaded', false)
            ->assertSet('exportDtrFailed', true)
            ->assertSee('HTTP 409')
            ->assertDontSee('Showing 1–2 of 2');
    }

    public function test_history_details_open_on_a_dedicated_page_and_return_to_the_selected_device(): void
    {
        $this->fakeServer([$this->row()]);
        $sync = $this->createSync();
        $sync->update([
            'status' => 'failed',
            'active_device_key' => null,
            'message' => 'Import cancelled.',
            'result' => [
                'total' => 101,
                'failed' => 101,
                'errors' => array_map(fn ($row) => ['row' => $row, 'message' => "Issue {$row}"], range(1, 101)),
            ],
        ]);
        $master = User::where('username', 'masteradmin')->firstOrFail();

        $history = Livewire::actingAs($master)
            ->test(BiometricsSync::class)
            ->set('device', '1')
            ->assertSee('View details')
            ->assertDontSee('Issue 101');

        $history->assertSee(BiometricsSyncDetails::getUrl(['device' => '1', 'syncId' => $sync->id]));

        $this->actingAs($master)
            ->get(BiometricsSyncDetails::getUrl(['device' => '1', 'syncId' => $sync->id]))
            ->assertOk()
            ->assertSee('Return to device');

        Livewire::withQueryParams(['device' => '1', 'syncId' => $sync->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->assertSee('Synchronization details')
            ->assertSee('Issue 101')
            ->assertSee('Return to device')
            ->assertSeeHtml(BiometricsSync::getUrl(['device' => '1']))
            ->assertDontSee('aria-modal="true"');
    }

    public function test_locked_period_and_bad_checksum_prevent_import(): void
    {
        $this->fakeServer([$this->row()], badChecksum: true);
        $sync = $this->createSync();
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('failed', $sync->fresh()->status);
        $this->assertDatabaseCount('dtrs', 0);
        $this->fakeServer([$this->row()]);
        $next = $this->createSync();
        $this->period->update(['is_locked' => true]);
        app(BiometricsSyncService::class)->advance($next->id);
        $this->assertSame('failed', $next->fresh()->status);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_callbacks_are_authenticated_and_page_is_hr_only(): void
    {
        $this->fakeServer([$this->row()]);
        $sync = $this->createSync();
        $path = '/api/biometrics-sync/callback';
        $data = ['request_id' => $sync->id];
        $body = json_encode($data);
        $this->postJson($path, $data)->assertUnauthorized();
        $this->withHeaders(SyncSignature::headers('POST', $path, $body))->postJson($path, $data)->assertAccepted();
        $this->actingAs(User::factory()->create(['role' => 'employee']))->get('/hr/biometrics-sync')->assertForbidden();
        $master = User::where('username', 'masteradmin')->first();
        $this->assertNotNull($master);
        $this->actingAs($master)->get('/hr/biometrics-sync')->assertOk()->assertSee('Biometrics Devices');
        $this->get('/hr/biometrics-sync?device=1')->assertOk()->assertSee('Sync Device');
    }

    public function test_station_approver_only_sees_and_syncs_workflow_assigned_devices(): void
    {
        Http::swap(new Factory);
        Http::fake(['https://biometrics.test/api/hris/devices' => Http::response(['data' => [
            ['id' => 1, 'name' => 'Maco', 'code' => 'MACO', 'online' => true],
            ['id' => 2, 'name' => 'Tagum', 'code' => 'TAGUM', 'online' => true],
        ]])]);

        $approver = User::factory()->create(['role' => 'employee']);
        $employee = Employee::query()->firstOrFail();
        $employee->forceFill(['user_id' => $approver->id])->save();
        $workflow = LeaveApprovalWorkflow::create([
            'name' => 'Maco approval', 'is_active' => true, 'version' => 1,
            'branch_id' => $employee->branch_id,
            'active_scope_key' => '0:'.$employee->branch_id.':0:0',
            'device_ids' => ['1'],
        ]);
        $workflow->levels()->create([
            'sequence' => 1, 'label' => 'Station approver', 'approver_employee_id' => $employee->id,
        ]);

        $this->actingAs($approver);
        $this->get('/employee/station/biometrics-sync')->assertOk()
            ->assertSee('Maco')->assertDontSee('Tagum');
        $this->get('/employee/station/biometrics-sync?device=2')->assertForbidden();
        $this->get('/employee/station/biometrics-sync?device=1')->assertOk()
            ->assertSee('Sync Device')->assertDontSee('Sync Employee');

        Livewire::actingAs($approver)->test(StationBiometricsSync::class)
            ->set('device', '1')->call('openSync')
            ->assertSet('showSyncModal', true)
            ->set('periodId', (string) $this->period->id)
            ->call('submitSync');
        $this->assertDatabaseHas('biometrics_sync_requests', [
            'device_id' => '1', 'requested_by' => $approver->id,
            'sync_type' => 'payroll', 'status' => 'pending',
        ]);

        Livewire::actingAs($approver)->test(StationBiometricsSync::class)
            ->set('device', '1')->call('openEmployeeSync')->assertForbidden();

        $workflow->update(['is_active' => false, 'active_scope_key' => null]);
        $this->get('/employee/station/biometrics-sync')->assertForbidden();
    }

    public function test_hr_history_is_shared_and_shows_the_initiating_account(): void
    {
        Http::swap(new Factory);
        Http::fake(['https://biometrics.test/api/hris/devices' => Http::response(['data' => [
            ['id' => 1, 'name' => 'Maco', 'code' => 'MACO', 'online' => true],
        ]])]);
        $master = User::where('username', 'masteradmin')->firstOrFail();
        $otherHr = User::factory()->create(['role' => 'hr', 'name' => 'HR Reviewer']);
        $masterSync = $this->syncHistoryRecord($master);
        $otherHrSync = $this->syncHistoryRecord($otherHr);

        Livewire::actingAs($master)->test(BiometricsSync::class)
            ->set('device', '1')
            ->assertSee('Requested By')
            ->assertSee($master->name)
            ->assertSee('HR Reviewer')
            ->assertSee($masterSync->id)
            ->assertSee($otherHrSync->id);
    }

    public function test_station_history_and_details_are_private_to_the_requesting_account(): void
    {
        Http::swap(new Factory);
        Http::fake(['https://biometrics.test/api/hris/devices' => Http::response(['data' => [
            ['id' => 1, 'name' => 'Maco', 'code' => 'MACO', 'online' => true],
        ]])]);
        $approver = $this->stationApprover('SIC Alpha');
        $otherAccount = User::factory()->create(['role' => 'employee', 'name' => 'SIC Beta']);
        $ownSync = $this->syncHistoryRecord($approver);
        $otherSync = $this->syncHistoryRecord($otherAccount);

        $this->actingAs($approver);
        $this->get('/employee/station/biometrics-sync?device=1')->assertOk();
        $page = Livewire::actingAs($approver)->test(StationBiometricsSync::class)
            ->set('device', '1')
            ->assertSee('SIC Alpha')
            ->assertSee($ownSync->id)
            ->assertDontSee('SIC Beta')
            ->assertDontSee($otherSync->id);

        Livewire::withQueryParams(['device' => '1', 'syncId' => $ownSync->id])
            ->actingAs($approver)
            ->test(StationBiometricsSyncDetails::class)
            ->assertSee('Return to device')
            ->assertSeeHtml(StationBiometricsSync::getUrl(['device' => '1']));

        Livewire::withQueryParams(['device' => '1', 'syncId' => $otherSync->id])
            ->actingAs($approver)
            ->test(StationBiometricsSyncDetails::class)
            ->assertNotFound();

        $this->actingAs($approver)
            ->get(StationBiometricsSyncDetails::getUrl(['device' => '1', 'syncId' => $otherSync->id]))
            ->assertNotFound();
    }

    public function test_hr_device_command_blocks_employee_panel_until_remote_completion_is_acknowledged(): void
    {
        $approver = $this->stationApprover();
        $this->fakeServer([$this->row()], 'processing');
        $sync = $this->createSync();
        $busyMessage = BiometricsSyncService::DEVICE_UNAVAILABLE_MESSAGE;

        $this->actingAs($approver);
        $this->get('/employee/station/biometrics-sync?device=1')->assertOk();
        Livewire::actingAs($approver)->test(StationBiometricsSync::class)
            ->set('device', '1')
            ->assertSee($busyMessage)
            ->assertSeeHtml('disabled');

        try {
            app(BiometricsSyncService::class)->createEmployee('1', $approver->id);
            $this->fail('An employee sync was accepted while HR already held the device lock.');
        } catch (ValidationException $exception) {
            $this->assertSame($busyMessage, $exception->errors()['device'][0]);
        }

        $this->fakeServer([$this->row()]);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('success', $sync->fresh()->status);
        $this->assertNull($sync->fresh()->active_device_key);

        Livewire::actingAs($approver)->test(StationBiometricsSync::class)
            ->set('device', '1')
            ->assertDontSee($busyMessage);
    }

    public function test_transient_network_failure_retries_same_request_without_reimporting(): void
    {
        $this->fakeServer([$this->row()]);
        $sync = $this->createSync();
        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['message' => 'Temporary'], 503)]);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('pending', $sync->fresh()->status);
        $this->assertNotNull($sync->fresh()->next_attempt_at);
        $this->fakeServer([$this->row()]);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('success', $sync->fresh()->status);
        $this->assertDatabaseCount('dtrs', 1);
    }

    public function test_employee_sync_creates_read_only_snapshot_and_blocks_payroll_until_acknowledged(): void
    {
        $this->fakeEmployeeServer([['id' => 1, 'uid' => 123456, 'name' => 'Test Employee', 'type' => 'USER',
            'sched' => 'REGULAR1', 'fps' => [4, 0, 0], 'fp_count' => 1, 'created' => '2026-10-01']]);
        $sync = app(BiometricsSyncService::class)->createEmployee('1', $this->user->id);
        $this->assertSame('employee', $sync->sync_type);
        $this->assertNull($sync->payroll_period_id);
        $this->expectException(ValidationException::class);
        app(BiometricsSyncService::class)->create('1', $this->period->id, $this->user->id);
    }

    public function test_employee_snapshot_completes_without_importing_dtr_or_changing_hris_employees(): void
    {
        $this->fakeEmployeeServer([['id' => 1, 'uid' => 123456, 'name' => 'Test Employee', 'type' => 'USER',
            'sched' => 'REGULAR1', 'fps' => [4, 0, 0], 'fp_count' => 1, 'created' => '2026-10-01']]);
        $sync = app(BiometricsSyncService::class)->createEmployee('1', $this->user->id);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('success', $sync->fresh()->status);
        $this->assertSame(1, $sync->fresh()->result['employee_count']);
        $this->assertSame([4], $sync->fresh()->result['employees'][0]['fingerprint_ids']);
        $this->assertNotNull($sync->fresh()->remote_acknowledged_at);
        $this->assertNull($sync->fresh()->active_device_key);
        $this->assertDatabaseCount('dtrs', 0);
        $this->assertDatabaseCount('employees', 1);

        $master = User::where('username', 'masteradmin')->firstOrFail();
        Livewire::actingAs($master)->test(BiometricsSync::class)->set('device', '1')
            ->assertSee('Sync Employee')->assertSee('View details');

        Livewire::withQueryParams(['device' => '1', 'syncId' => $sync->id])
            ->actingAs($master)
            ->test(BiometricsSyncDetails::class)
            ->assertSee('Test Employee')->assertSee('123456')
            ->set('employeeSearch', 'not-found')->assertSee('No device employees match this search.');
    }

    public function test_empty_employee_device_is_a_successful_empty_snapshot(): void
    {
        $this->fakeEmployeeServer([]);
        $sync = app(BiometricsSyncService::class)->createEmployee('1', $this->user->id);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('success', $sync->fresh()->status);
        $this->assertSame(0, $sync->fresh()->result['employee_count']);
        $this->assertNull($sync->fresh()->active_device_key);
    }

    public function test_bad_employee_export_checksum_fails_without_a_snapshot(): void
    {
        $this->fakeEmployeeServer([['id' => 1, 'uid' => 123456, 'name' => 'Test Employee', 'type' => 'USER',
            'sched' => 'REGULAR1', 'fps' => [4, 0, 0], 'created' => '2026-10-01']], badChecksum: true);
        $sync = app(BiometricsSyncService::class)->createEmployee('1', $this->user->id);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('failed', $sync->fresh()->status);
        $this->assertNull($sync->fresh()->result);
        $this->assertNull($sync->fresh()->active_device_key);
    }

    public function test_buttons_remain_reserved_until_remote_completion_is_acknowledged(): void
    {
        $this->fakeEmployeeServer([['id' => 1, 'uid' => 123456, 'name' => 'Test Employee', 'type' => 'USER',
            'sched' => 'REGULAR1', 'fps' => [4, 0, 0], 'fp_count' => 1, 'created' => '2026-10-01']], finishFails: true);
        $sync = app(BiometricsSyncService::class)->createEmployee('1', $this->user->id);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertSame('success', $sync->fresh()->status);
        $this->assertSame('1', $sync->fresh()->active_device_key);
        $this->assertNull($sync->fresh()->remote_acknowledged_at);

        $this->fakeEmployeeServer([]);
        app(BiometricsSyncService::class)->advance($sync->id);
        $this->assertNull($sync->fresh()->active_device_key);
        $this->assertNotNull($sync->fresh()->remote_acknowledged_at);
    }
}
