<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Station\ManageStationDtr;
use App\Filament\Employee\Pages\Station\StationDtrImport;
use App\Filament\Employee\Pages\Station\StationDtrImportHistory;
use App\Filament\Employee\Pages\Station\StationEmployees;
use App\Filament\Employee\Pages\Station\StationSubmissions;
use App\Filament\Pages\Dtr as HrDtrPage;
use App\Filament\Pages\DtrManage;
use App\Filament\Pages\Payroll as HrPayrollPage;
use App\Models\ActionHistory;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\LeaveApprovalLevel;
use App\Models\LeaveApprovalWorkflow;
use App\Models\PayrollPeriod;
use App\Models\SicRcDtrImport;
use App\Models\User;
use App\Services\DtrChangeRequestService;
use App\Services\Imports\DtrImportService;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use App\Services\OvertimeApprovalService;
use App\Services\SicRcDtrImportDeletionService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DtrSynchronizationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{branch:Branch, branchB:Branch, period:PayrollPeriod, sicUser:User, sicEmployee:Employee, empUser:User, employee:Employee, hrUser:User}
     */
    private function seedStationScenario(): array
    {
        $department = Department::query()->create([
            'name' => 'Operations',
            'description' => 'Station Operations',
        ]);

        $designation = Designation::query()->create([
            'title' => 'Pump Attendant',
            'description' => 'Station Pump Attendant',
            'rate_per_hour' => 65,
        ]);

        $branch = Branch::query()->create([
            'branch_name' => 'Tagum Station',
            'branch_address' => 'Tagum City',
            'mobile_no' => '09171234567',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);

        $branchB = Branch::query()->create([
            'branch_name' => 'Maco Station',
            'branch_address' => 'Maco',
            'mobile_no' => '09179876543',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);

        $period = PayrollPeriod::query()->create([
            'title' => 'Oct 1 - 15, 2026',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-15',
            'date_payout' => '2026-10-20',
            'description' => 'First Half October 2026',
            'is_locked' => false,
        ]);

        $sicUser = User::factory()->create([
            'role' => 'employee',
            'email' => 'tagum.sic@example.com',
        ]);

        $sicEmployee = Employee::query()->create([
            'user_id' => $sicUser->id,
            'uid' => 'SIC-1001',
            'fingerprint_id' => '9001',
            'lastname' => 'Santos',
            'firstname' => 'Marco',
            'middlename' => 'D',
            'gender' => 'Male',
            'civil_status' => 'Single',
            'birthdate' => '1990-04-10',
            'home_address' => 'Tagum City',
            'contact_no' => '09171111111',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_status' => 'Regular',
            'employment_type' => 'Permanent',
            'date_hired' => '2022-01-10',
            'rate_type' => 'monthly',
            'monthly_rate' => 18000,
            'daily_rate' => 692.31,
            'schedule_type' => 'regular',
        ]);
        $sicUser->setRelation('employee', $sicEmployee);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Tagum Station Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Tagum SIC',
            'approver_employee_id' => $sicEmployee->id,
        ]);

        $empUser = User::factory()->create([
            'role' => 'employee',
            'email' => 'juan.delacruz@example.com',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $empUser->id,
            'uid' => 'EMP-2001',
            'fingerprint_id' => '2001',
            'lastname' => 'Dela Cruz',
            'firstname' => 'Juan',
            'middlename' => 'R',
            'gender' => 'Male',
            'civil_status' => 'Single',
            'birthdate' => '1995-06-15',
            'home_address' => 'Tagum City',
            'contact_no' => '09172222222',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_status' => 'Regular',
            'employment_type' => 'Permanent',
            'date_hired' => '2023-03-01',
            'rate_type' => 'monthly',
            'monthly_rate' => 15000,
            'daily_rate' => 576.92,
            'schedule_type' => 'regular',
        ]);
        $empUser->setRelation('employee', $employee);

        $hrUser = User::factory()->create([
            'role' => 'hr',
            'email' => 'hr.admin@example.com',
        ]);

        return compact('branch', 'branchB', 'period', 'sicUser', 'sicEmployee', 'empUser', 'employee', 'hrUser');
    }

    public function test_station_dtr_import_automatically_synchronizes_to_hr_dtr_table(): void
    {
        $ctx = $this->seedStationScenario();
        $this->actingAs($ctx['sicUser'], 'web');

        $rows = [
            [
                'Batch ID' => 'BATCH-SYNC-1',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-02',
                'Time In' => '08:00:00',
                'Date Out' => '2026-10-02',
                'Time Out' => '17:00:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
            [
                'Batch ID' => 'BATCH-SYNC-1',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-05',
                'Time In' => '07:15:00',
                'Date Out' => '2026-10-05',
                'Time Out' => '18:30:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
        ];

        $result = app(EmployeeVisibleDtrImportService::class)->importRows($rows, 'Tagum Oct Import', 'BATCH-SYNC-1');

        $this->assertSame(2, $result['successful']);
        $this->assertSame(0, $result['failed']);
        $this->assertArrayHasKey('summary', $result);
        $this->assertSame(2, $result['summary']['imported']);

        // Both EmployeeVisibleDtr (Station) and Dtr (HR Official) must exist automatically
        $this->assertSame(2, EmployeeVisibleDtr::query()->where('payroll_period_id', $ctx['period']->id)->count());
        $this->assertSame(2, Dtr::query()->where('payroll_period_id', $ctx['period']->id)->count());

        $officialOtRow = Dtr::query()
            ->where('payroll_period_id', $ctx['period']->id)
            ->whereDate('date_in', '2026-10-05')
            ->firstOrFail();

        $this->assertSame('2001', (string) $officialOtRow->fingerprint_id);
        $this->assertSame(45, (int) $officialOtRow->early_clock_in);
        $this->assertSame(90, (int) $officialOtRow->overtime);
        $this->assertSame('Pending', $officialOtRow->overtime_status);
    }

    public function test_station_import_uses_authorized_route_branch_without_a_row_branch_column(): void
    {
        $ctx = $this->seedStationScenario();
        $this->actingAs($ctx['sicUser'], 'web');
        $payload = [
            'import_name' => 'Station BIN upload',
            'period_id' => $ctx['period']->id,
            'batch_id' => 'STATION-NO-SELECTOR',
            'rows' => [[
                'Batch ID' => 'STATION-NO-SELECTOR',
                'Fingerprint ID' => '2001',
                'Date In' => '2026-10-02', 'Time In' => '08:00:00',
                'Date Out' => '2026-10-02', 'Time Out' => '17:00:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00', 'Schedule End' => '17:00:00',
            ]],
        ];

        $this->postJson(route('sicrc_tools.import.dtr_preview'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        $this->postJson(route('sicrc_tools.import.dtr_preview'), [
            ...$payload, 'branch_id' => $ctx['branchB']->id,
        ])->assertForbidden();
        $this->assertDatabaseCount('dtrs', 0);

        $this->postJson(route('sicrc_tools.import.dtr_preview'), [
            ...$payload, 'branch_id' => $ctx['branch']->id,
        ])->assertSuccessful()->assertJsonPath('successful', 1);
        $this->assertDatabaseHas('employee_visible_dtrs', [
            'employee_id' => $ctx['employee']->id, 'branch_id' => $ctx['branch']->id,
        ]);
        $this->assertDatabaseHas('dtrs', [
            'employee_id' => $ctx['employee']->id,
            'branch_id' => $ctx['branch']->id,
            'source_branch_id' => $ctx['branch']->id,
        ]);
    }

    public function test_overtime_and_early_ot_approval_on_station_dtr_automatically_syncs_to_hr_dtr(): void
    {
        $ctx = $this->seedStationScenario();
        $this->actingAs($ctx['sicUser'], 'web');

        app(EmployeeVisibleDtrImportService::class)->importRows([
            [
                'Batch ID' => 'BATCH-OT-1',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-05',
                'Time In' => '07:15:00',
                'Date Out' => '2026-10-05',
                'Time Out' => '18:30:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
        ], 'Tagum OT Import', 'BATCH-OT-1');

        $visibleRecord = EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $ctx['period']->id)
            ->whereDate('date_in', '2026-10-05')
            ->firstOrFail();

        // SIC approves both early clock-in (45m) and after-schedule overtime (90m) on the Station DTR record
        app(OvertimeApprovalService::class)->approve($visibleRecord, 90, 45);

        $visibleRecord->refresh();
        $officialRecord = Dtr::query()
            ->where('payroll_period_id', $ctx['period']->id)
            ->whereDate('date_in', '2026-10-05')
            ->firstOrFail();

        $this->assertSame('Approved', $visibleRecord->overtime_status);
        $this->assertSame('Approved', $officialRecord->overtime_status);
        $this->assertSame(45, (int) $officialRecord->credited_early_clock_in);
        $this->assertSame(135, (int) $officialRecord->credited_overtime);
        $this->assertTrue((bool) $officialRecord->early_clock_in_approved);
        $this->assertTrue((bool) $officialRecord->overtime_approved);

        // Audit trail entry recorded
        $this->assertTrue(ActionHistory::query()->whereIn('action', ['overtime_synced_to_official', 'overtime_approved'])->exists());
    }

    public function test_mixed_import_skips_existing_and_edited_records_while_importing_new_rows(): void
    {
        $ctx = $this->seedStationScenario();
        $this->actingAs($ctx['sicUser'], 'web');

        // Initial import of Oct 2 and Oct 5
        app(EmployeeVisibleDtrImportService::class)->importRows([
            [
                'Batch ID' => 'BATCH-MIX-1',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-02',
                'Time In' => '08:00:00',
                'Date Out' => '2026-10-02',
                'Time Out' => '17:00:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
            [
                'Batch ID' => 'BATCH-MIX-1',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-05',
                'Time In' => '07:15:00',
                'Date Out' => '2026-10-05',
                'Time Out' => '18:30:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
        ], 'Initial Import', 'BATCH-MIX-1');

        // Approve OT on Oct 5 so it differs from raw biometric file
        $oct5Visible = EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $ctx['period']->id)
            ->whereDate('date_in', '2026-10-05')
            ->firstOrFail();
        app(OvertimeApprovalService::class)->approve($oct5Visible, 90, 45);

        // Re-import file containing Oct 2 (unchanged), Oct 5 (has approved OT), and Oct 6 (new)
        $mixedResult = app(EmployeeVisibleDtrImportService::class)->importRows([
            [
                'Batch ID' => 'BATCH-MIX-2',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-02',
                'Time In' => '08:00:00',
                'Date Out' => '2026-10-02',
                'Time Out' => '17:00:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
            [
                'Batch ID' => 'BATCH-MIX-2',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-05',
                'Time In' => '07:15:00',
                'Date Out' => '2026-10-05',
                'Time Out' => '18:30:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
            [
                'Batch ID' => 'BATCH-MIX-2',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-06',
                'Time In' => '08:00:00',
                'Date Out' => '2026-10-06',
                'Time Out' => '17:00:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
        ], 'Mixed Import', 'BATCH-MIX-2');

        $this->assertSame(3, $mixedResult['total']);
        $this->assertSame(1, $mixedResult['successful']);
        $this->assertSame(2, $mixedResult['skipped']);
        $this->assertSame(2, $mixedResult['already_existing']);
        $this->assertSame(0, $mixedResult['failed']);

        // Oct 5 approved OT must NOT be overwritten in either table
        $this->assertSame(
            'Approved',
            EmployeeVisibleDtr::query()->whereDate('date_in', '2026-10-05')->value('overtime_status'),
        );
        $this->assertSame(
            'Approved',
            Dtr::query()->whereDate('date_in', '2026-10-05')->value('overtime_status'),
        );

        // Oct 6 must be present in both tables
        $this->assertSame(3, EmployeeVisibleDtr::query()->where('payroll_period_id', $ctx['period']->id)->count());
        $this->assertSame(3, Dtr::query()->where('payroll_period_id', $ctx['period']->id)->count());
    }

    public function test_change_request_approval_updates_both_station_and_hr_dtr_when_targeting_visible_dtr(): void
    {
        $ctx = $this->seedStationScenario();
        $this->actingAs($ctx['sicUser'], 'web');

        app(EmployeeVisibleDtrImportService::class)->importRows([
            [
                'Batch ID' => 'BATCH-CR-1',
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-07',
                'Time In' => '08:30:00',
                'Date Out' => '2026-10-07',
                'Time Out' => '17:00:00',
                'Schedule Type' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
        ], 'Oct 7 Import', 'BATCH-CR-1');

        $visibleRecord = EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $ctx['period']->id)
            ->whereDate('date_in', '2026-10-07')
            ->firstOrFail();

        $changeRequest = app(DtrChangeRequestService::class)->submit($ctx['employee'], [
            'payroll_period_id' => $ctx['period']->id,
            'date_from' => '2026-10-07',
            'date_to' => '2026-10-07',
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'description' => 'Biometric scanner delayed my morning punch; arrived at 08:00 AM.',
        ]);

        // While pending, official DTR still has 08:30:00 (30m late)
        $officialBefore = Dtr::query()
            ->where('payroll_period_id', $ctx['period']->id)
            ->whereDate('date_in', '2026-10-07')
            ->firstOrFail();
        $this->assertSame('08:30:00', $officialBefore->time_in);

        // Level 1 SIC saves corrected time_in = 08:00:00 and approves
        app(DtrChangeRequestService::class)->saveCorrections($changeRequest, $ctx['sicEmployee'], [
            'date_in' => '2026-10-07',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-07',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
            'comment' => 'Verified via CCTV at 08:00 AM',
        ]);

        app(DtrChangeRequestService::class)->approve($changeRequest->refresh(), $ctx['sicEmployee'], 'Approved after CCTV check.');

        $this->assertSame('Pending', $changeRequest->fresh()->status);
        $this->assertSame('08:30:00', $officialBefore->fresh()->time_in);
        app(DtrChangeRequestService::class)->approve($changeRequest->refresh(), $ctx['hrUser'], 'Final HR approval.');

        $visibleAfter = $visibleRecord->refresh();
        $officialAfter = $officialBefore->refresh();

        $this->assertSame('08:00:00', $visibleAfter->time_in);
        $this->assertSame(0, (int) $visibleAfter->late);
        $this->assertSame('08:00:00', $officialAfter->time_in);
        $this->assertSame(0, (int) $officialAfter->late);
    }

    public function test_locked_payroll_period_blocks_station_dtr_import_and_overtime_mutations(): void
    {
        $ctx = $this->seedStationScenario();
        $ctx['period']->update(['is_locked' => true]);

        $this->actingAs($ctx['sicUser'], 'web');

        $response = $this->postJson(route('sicrc_tools.import.dtr_preview'), [
            'import_name' => 'Locked Period Attempt',
            'period_id' => $ctx['period']->id,
            'branch_id' => $ctx['branch']->id,
            'rows' => [
                [
                    'Fingerprint ID' => '2001',
                    'Date In' => '2026-10-08',
                    'Time In' => '08:00:00',
                    'Date Out' => '2026-10-08',
                    'Time Out' => '17:00:00',
                    'Schedule' => 'Regular',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, EmployeeVisibleDtr::query()->where('payroll_period_id', $ctx['period']->id)->count());
        $this->assertSame(0, Dtr::query()->where('payroll_period_id', $ctx['period']->id)->count());
    }

    public function test_manual_transfer_controls_and_import_history_deletion_are_disabled(): void
    {
        $ctx = $this->seedStationScenario();
        $this->actingAs($ctx['sicUser'], 'web');
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        // The branch landing page has no proof-submission or manual-transfer action.
        Livewire::test(ManageStationDtr::class)
            ->assertActionDoesNotExist('submitDtr')
            ->assertActionDoesNotExist('submitDtrProof');

        // 2. StationEmployees does not have downloadDtr
        Livewire::withQueryParams([
            'branchId' => $ctx['branch']->id,
            'periodId' => $ctx['period']->id,
        ])
            ->test(StationEmployees::class)
            ->assertActionDoesNotExist('downloadDtr')
            ->assertActionExists('importDtr');

        // 3. StationDtrImport does not have importHistory
        Livewire::withQueryParams([
            'branchId' => $ctx['branch']->id,
            'periodId' => $ctx['period']->id,
        ])
            ->test(StationDtrImport::class)
            ->assertActionDoesNotExist('importHistory');

        // 4. StationSubmissions and StationDtrImportHistory deny access
        $this->assertFalse(StationSubmissions::canAccess());
        $this->assertFalse(StationDtrImportHistory::canAccess());

        // 5. SicRcDtrImportDeletionService protects completed batches & records
        $visible = EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $ctx['period']->id,
            'branch_id' => $ctx['branch']->id,
            'employee_id' => $ctx['employee']->id,
            'fingerprint_id' => '2001',
            'batch_id' => 'PROTECTED-BATCH',
            'date_in' => '2026-10-09',
            'time_in' => '08:00:00',
            'date_out' => '2026-10-09',
            'time_out' => '17:00:00',
        ]);

        $importRecord = SicRcDtrImport::query()->create([
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['period']->id,
            'batch_id' => 'PROTECTED-BATCH',
            'import_name' => 'Protected Import',
            'total_rows' => 1,
            'imported_rows' => 1,
            'status' => SicRcDtrImport::STATUS_COMPLETED,
            'imported_at' => now(),
        ]);

        $delResult = app(SicRcDtrImportDeletionService::class)->delete($importRecord);
        $this->assertSame(['entries' => 0, 'histories' => 0], $delResult);
        $this->assertNotNull(EmployeeVisibleDtr::query()->find($visible->id));
    }

    public function test_hr_dtr_and_payroll_pages_support_direct_period_branch_and_employee_filtering(): void
    {
        $ctx = $this->seedStationScenario();
        Gate::before(fn () => true);
        $this->actingAs($ctx['hrUser'], 'web');
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        // Import synchronized DTR for Juan Dela Cruz in Tagum Station
        app(DtrImportService::class)->importRows([
            [
                'Fingerprint ID' => '2001',
                'Period ID' => $ctx['period']->id,
                'Branch ID' => $ctx['branch']->id,
                'Date In' => '2026-10-03',
                'Time In' => '08:00:00',
                'Date Out' => '2026-10-03',
                'Time Out' => '17:00:00',
                'Schedule' => 'Regular',
                'Schedule Start' => '08:00:00',
                'Schedule End' => '17:00:00',
            ],
        ], 'HR Direct Import', 'BATCH-HR-1');

        // Verify HR DTR page filters
        $dtrPage = Livewire::test(HrDtrPage::class)
            ->assertSet('periodId', $ctx['period']->id)
            ->assertSee('Dela Cruz')
            ->set('branchId', $ctx['branchB']->id)
            ->assertDontSee('Dela Cruz')
            ->call('resetFilters')
            ->assertSet('branchId', null)
            ->assertSee('Dela Cruz');

        $dtrPage->set('search', 'Juan')
            ->assertSee('Dela Cruz');

        // Verify HR Payroll page filters and layout
        $payrollComponent = Livewire::test(HrPayrollPage::class)
            ->assertSet('periodId', $ctx['period']->id)
            ->assertSee('Payroll Branch/Department')
            ->assertDontSee('Browse Branches')
            ->assertDontSee('Selected Period')
            ->assertDontSee('Payout Date')
            ->assertDontSee('Branch Scope')
            ->assertDontSee('Matching Employees')
            ->assertSee('Dela Cruz')
            ->set('branchId', $ctx['branchB']->id)
            ->assertDontSee('Dela Cruz')
            ->call('resetFilters')
            ->assertSet('branchId', null)
            ->assertSee('Dela Cruz');

        $this->assertSame('', $payrollComponent->instance()->getHeading());
    }

    public function test_hr_dtr_manage_and_return_direct_navigation_preserves_filters(): void
    {
        $ctx = $this->seedStationScenario();
        Gate::before(fn () => true);
        $this->actingAs($ctx['hrUser'], 'web');
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        // 1. Check HR DTR page rows generate Manage DTR url with search & filters preserved
        $dtrComponent = Livewire::withQueryParams([
            'periodId' => $ctx['period']->publicKey(),
            'branchId' => $ctx['branch']->publicKey(),
            'search' => 'Juan',
        ])->test(HrDtrPage::class)
            ->assertSet('periodId', $ctx['period']->id)
            ->assertSet('branchId', $ctx['branch']->id)
            ->assertSet('search', 'Juan');

        $rows = $dtrComponent->instance()->filteredEmployeeRows();
        $this->assertCount(1, $rows);
        $manageUrl = $rows[0]['manage_url'];
        $this->assertStringContainsString('periodId='.$ctx['period']->publicKey(), $manageUrl);
        $this->assertStringContainsString('branchId='.$ctx['branch']->publicKey(), $manageUrl);
        $this->assertStringContainsString('employeeId='.$ctx['employee']->publicKey(), $manageUrl);
        $this->assertStringContainsString('search=Juan', $manageUrl);

        // 2. Open DtrManage page with these parameters and assert Return action points directly to DTR page
        $manageComponent = Livewire::withQueryParams([
            'periodId' => $ctx['period']->publicKey(),
            'branchId' => $ctx['branch']->publicKey(),
            'employeeId' => $ctx['employee']->publicKey(),
            'search' => 'Juan',
        ])->test(DtrManage::class)
            ->assertActionExists('return');

        $method = new \ReflectionMethod(DtrManage::class, 'getHeaderActions');
        $actions = $method->invoke($manageComponent->instance());
        $returnAction = collect($actions)->first(fn ($a) => $a->getName() === 'return');
        $this->assertNotNull($returnAction);
        $returnUrl = $returnAction->getUrl();

        $this->assertStringContainsString('/hr/dtr', $returnUrl);
        $this->assertStringNotContainsString('dtr-branch-employees', $returnUrl);
        $this->assertStringNotContainsString('dtr-period-branches', $returnUrl);
        $this->assertStringContainsString('periodId='.$ctx['period']->publicKey(), $returnUrl);
        $this->assertStringContainsString('branchId='.$ctx['branch']->publicKey(), $returnUrl);
        $this->assertStringContainsString('search=Juan', $returnUrl);
    }
}
