<?php

namespace Tests\Feature;

use App\Filament\Resources\SystemUpdates\Pages\CreateSystemUpdate;
use App\Filament\Resources\SystemUpdates\Pages\EditSystemUpdate;
use App\Filament\Resources\SystemUpdates\Pages\ListSystemUpdates;
use App\Filament\Resources\SystemUpdates\SystemUpdateResource;
use App\Livewire\SystemUpdatesModal;
use App\Livewire\SystemUpdatesTrigger;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveApprovalLevel;
use App\Models\LeaveApprovalWorkflow;
use App\Models\SystemUpdate;
use App\Models\SystemUpdateAcknowledgment;
use App\Models\SystemUpdateSetting;
use App\Models\User;
use App\Services\SystemUpdateService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use STS\FilamentImpersonate\ImpersonateManager;
use Tests\TestCase;

class SystemUpdatesModalTest extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;

    protected Role $hrAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        config(['permission.cache.store' => 'array']);
        app(PermissionRegistrar::class)->initializeCache();

        $this->superAdminRole = Role::findOrCreate('super_admin', 'web');
        $this->hrAdminRole = Role::findOrCreate('hr_admin', 'web');

        $viewDashboard = Permission::findOrCreate('View:Dashboard', 'web');
        $this->superAdminRole->givePermissionTo($viewDashboard);
        $this->hrAdminRole->givePermissionTo($viewDashboard);
    }

    public function test_migration_seeds_default_settings_initial_updates_and_shield_permissions(): void
    {
        $setting = SystemUpdateSetting::current();

        $this->assertSame(SystemUpdateSetting::FREQUENCY_UNTIL_ACKNOWLEDGED, $setting->popup_frequency);
        $this->assertTrue($setting->show_topbar_button);
        $this->assertSame('System Updates & Notes', $setting->modal_heading);
        $this->assertSame(2, SystemUpdate::query()->where('is_published', true)->count());
        $this->assertSame(
            ['Attendance, Overtime & DTR Filing Policy Updates', 'Station Approver Overtime Adjustment Control'],
            SystemUpdate::query()->published()->ordered()->pluck('title')->all()
        );

        $hrAdmin = $this->createHrUser('hr_admin_user', 'hr_admin');

        Filament::setCurrentPanel(Filament::getPanel('hr'));
        $this->actingAs($hrAdmin);

        $this->assertTrue(SystemUpdateResource::canAccess());
        $this->assertSame('Settings', SystemUpdateResource::getNavigationGroup());
    }

    public function test_hr_and_employee_logins_trigger_role_filtered_updates_popup(): void
    {
        $hrUser = $this->createHrUser('hr-reviewer', 'hr_admin');
        $employeeUser = $this->createEmployeeUser('PF0801', '0801');

        $hrOnlyUpdate = SystemUpdate::query()->create([
            'title' => 'HR Payroll Cut-Off Notice',
            'summary' => 'Review pending DTR requests prior to payroll lock.',
            'category' => SystemUpdate::CATEGORY_NOTE,
            'audience' => SystemUpdate::AUDIENCE_HR,
            'is_published' => true,
            'published_at' => now()->subMinute(),
        ]);

        $employeeOnlyUpdate = SystemUpdate::query()->create([
            'title' => 'Employee Self-Service Portal Notice',
            'summary' => 'Check your payslip schedule inside My Workspace.',
            'category' => SystemUpdate::CATEGORY_NOTE,
            'audience' => SystemUpdate::AUDIENCE_EMPLOYEE,
            'is_published' => true,
            'published_at' => now()->subMinute(),
        ]);

        // HR logs in via unified portal
        $this->post(route('landing.login'), [
            'username' => 'hr-reviewer',
            'password' => 'secret-pass',
        ])->assertRedirect(url('/hr'));

        $this->assertTrue((bool) session(SystemUpdateService::SESSION_LOGIN_PENDING));

        $hrResponse = $this->get('/hr/dashboard');
        $hrResponse->assertSuccessful();
        $hrResponse->assertSee('System Updates & Notes');
        $hrResponse->assertSee('Attendance, Overtime & DTR Filing Policy Updates');
        $hrResponse->assertSee($hrOnlyUpdate->title);
        $hrResponse->assertDontSee($employeeOnlyUpdate->title);
        $hrResponse->assertDontSee('Station Approver Overtime Adjustment Control');
        $hrResponse->assertDontSee('Dark Theme & Mobile Experience');

        // Fresh login for standard employee (not a station manager)
        $this->post('/hr/logout');
        $this->post(route('landing.login'), [
            'username' => 'PF-0801',
            'password' => 'secret-pass',
        ])->assertRedirect(url('/employee'));

        $empResponse = $this->get('/employee/dashboard');
        $empResponse->assertSuccessful();
        $empResponse->assertSee('Attendance, Overtime & DTR Filing Policy Updates');
        $empResponse->assertSee($employeeOnlyUpdate->title);
        $empResponse->assertDontSee($hrOnlyUpdate->title);
        $empResponse->assertDontSee('Station Approver Overtime Adjustment Control');
    }

    public function test_station_approvers_receive_station_manager_updates(): void
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Tagum Station',
            'branch_address' => 'Tagum City',
        ]);

        $managerUser = $this->createEmployeeUser('PF0802', '0802', $branch->id);
        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Tagum Flow',
            'branch_id' => $branch->id,
            'is_active' => true,
            'version' => 1,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC Tagum',
            'approver_employee_id' => $managerUser->employee->id,
        ]);

        $hrOnlyUpdate = SystemUpdate::query()->create([
            'title' => 'HR Payroll Cut-Off Notice',
            'summary' => 'HR-only reminder.',
            'category' => SystemUpdate::CATEGORY_NOTE,
            'audience' => SystemUpdate::AUDIENCE_HR,
            'is_published' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->post(route('landing.login'), [
            'username' => 'PF0802',
            'password' => 'secret-pass',
        ])->assertRedirect(url('/employee'));

        $response = $this->get('/employee/dashboard');
        $response->assertSuccessful();
        $response->assertSee('Station Approver Overtime Adjustment Control', false);
        $response->assertDontSee($hrOnlyUpdate->title);
    }

    public function test_until_acknowledged_frequency_reopens_on_relogin_until_marked_read(): void
    {
        $employee = $this->createEmployeeUser('PF0803', '0803');
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        $this->post(route('landing.login'), [
            'username' => 'PF0803',
            'password' => 'secret-pass',
        ])->assertRedirect(url('/employee'));

        // First login: auto-opens
        $modal = Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', true);

        // User clicks Remind Me Later (dismiss without acknowledging)
        $modal->call('dismissModal')
            ->assertDispatched('close-modal', id: SystemUpdatesModal::MODAL_ID);

        // Navigating another page in the same session stays closed
        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', false);

        // Re-login: since not acknowledged, auto-opens again
        $this->post('/employee/logout');
        $this->post(route('landing.login'), [
            'username' => 'PF0803',
            'password' => 'secret-pass',
        ])->assertRedirect(url('/employee'));

        $secondLoginModal = Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', true);

        // User clicks "Got it, Mark as Read"
        $secondLoginModal->call('acknowledgeAll')
            ->assertDispatched('system-updates-acknowledged')
            ->assertDispatched('close-modal', id: SystemUpdatesModal::MODAL_ID);

        $this->assertGreaterThan(
            0,
            SystemUpdateAcknowledgment::query()->where('user_id', $employee->id)->count()
        );

        // Third login: stays closed because already acknowledged
        $this->post('/employee/logout');
        $this->post(route('landing.login'), [
            'username' => 'PF0803',
            'password' => 'secret-pass',
        ])->assertRedirect(url('/employee'));

        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', false);

        // Publishing a NEW update makes next login auto-open again
        SystemUpdate::query()->create([
            'version' => 'v2.10',
            'title' => 'New Cutoff Reminder',
            'summary' => 'Please verify all Field DTR logs by Friday.',
            'category' => SystemUpdate::CATEGORY_NOTE,
            'audience' => SystemUpdate::AUDIENCE_ALL,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->post('/employee/logout');
        $this->post(route('landing.login'), [
            'username' => 'PF0803',
            'password' => 'secret-pass',
        ])->assertRedirect(url('/employee'));

        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', true)
            ->assertSee('New Cutoff Reminder');
    }

    public function test_once_per_day_and_every_login_and_disabled_frequencies(): void
    {
        $employee = $this->createEmployeeUser('PF0804', '0804');
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        $setting = SystemUpdateSetting::current();
        $setting->update(['popup_frequency' => SystemUpdateSetting::FREQUENCY_ONCE_PER_DAY]);

        $this->travelTo(now('Asia/Manila')->addDay()->setTime(9, 0));

        // Login 1 today -> opens
        $this->post(route('landing.login'), ['username' => 'PF0804', 'password' => 'secret-pass']);
        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', true)
            ->call('dismissModal');

        // Login 2 same day (still unread) -> does NOT auto-open again today
        $this->post('/employee/logout');
        $this->post(route('landing.login'), ['username' => 'PF0804', 'password' => 'secret-pass']);
        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', false);

        // Next calendar day -> opens again
        $this->travel(1)->day();
        $this->post('/employee/logout');
        $this->post(route('landing.login'), ['username' => 'PF0804', 'password' => 'secret-pass']);
        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', true)
            ->call('acknowledgeAll');

        // Switch to EVERY_LOGIN -> pops even though user already acknowledged!
        $setting->update(['popup_frequency' => SystemUpdateSetting::FREQUENCY_EVERY_LOGIN]);
        $this->post('/employee/logout');
        $this->post(route('landing.login'), ['username' => 'PF0804', 'password' => 'secret-pass']);
        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', true);

        // Switch to DISABLED -> never auto-opens, topbar trigger still lets user open manually
        $setting->update(['popup_frequency' => SystemUpdateSetting::FREQUENCY_DISABLED]);
        $this->post('/employee/logout');
        $this->post(route('landing.login'), ['username' => 'PF0804', 'password' => 'secret-pass']);
        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', false);

        Livewire::test(SystemUpdatesTrigger::class, ['panelId' => 'employee'])
            ->assertSet('shouldRender', true)
            ->call('openUpdatesModal')
            ->assertDispatched('open-system-updates-modal');
    }

    public function test_impersonation_suppresses_auto_popup_and_does_not_mark_employee_read(): void
    {
        $hr = $this->createHrUser('hr-impersonator', 'hr_admin');
        $employee = $this->createEmployeeUser('PF0805', '0805');

        Filament::setCurrentPanel(Filament::getPanel('employee'));

        $this->actingAs($employee, 'web');
        session([
            ImpersonateManager::SESSION_KEY => $hr->id,
            SystemUpdateService::SESSION_LOGIN_PENDING => true,
        ]);

        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'employee'])
            ->assertSet('autoOpen', false)
            ->assertSee('Viewing while impersonating')
            ->call('acknowledgeAll');

        $this->assertSame(
            0,
            SystemUpdateAcknowledgment::query()->where('user_id', $employee->id)->count()
        );
    }

    public function test_hr_admin_manages_popup_frequency_and_updates_under_settings(): void
    {
        $hrUser = $this->createHrUser('hr-settings-manager', 'hr_admin');
        Filament::setCurrentPanel(Filament::getPanel('hr'));
        $this->actingAs($hrUser, 'web');

        $this->get('/hr/settings/updates-notes')
            ->assertSuccessful()
            ->assertSee('Popup Frequency & Modal Behavior');

        // Configure frequency, title, subtitle & topbar toggle
        Livewire::test(ListSystemUpdates::class)
            ->set('popupFrequency', SystemUpdateSetting::FREQUENCY_EVERY_LOGIN)
            ->set('showTopbarButton', false)
            ->set('modalHeading', 'HRIS Changelog & Reminders')
            ->set('modalSubtitle', 'Latest system release updates.')
            ->call('savePopupSettings')
            ->assertHasNoErrors();

        $setting = SystemUpdateSetting::current();
        $this->assertSame(SystemUpdateSetting::FREQUENCY_EVERY_LOGIN, $setting->popup_frequency);
        $this->assertFalse($setting->show_topbar_button);
        $this->assertSame('HRIS Changelog & Reminders', $setting->modal_heading);

        // Topbar trigger hides when showTopbarButton is false
        Livewire::test(SystemUpdatesTrigger::class, ['panelId' => 'hr'])
            ->assertSet('shouldRender', false);

        // Create a new SystemUpdate entry (no published toggle, published_at picker, or version field; auto-publishes immediately)
        Livewire::test(CreateSystemUpdate::class)
            ->assertFormFieldDoesNotExist('published_at')
            ->assertFormFieldDoesNotExist('is_published')
            ->assertFormFieldDoesNotExist('version')
            ->fillForm([
                'title' => 'Year-End 13th Month Reminder',
                'audience' => SystemUpdate::AUDIENCE_ALL,
                'category' => SystemUpdate::CATEGORY_NOTE,
                'sort_order' => 0,
                'is_pinned' => true,
                'summary' => 'Submission cutoffs for 13th month processing.',
                'notes' => 'Coordinate payroll adjustments before Dec 10.',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $created = SystemUpdate::query()->where('title', 'Year-End 13th Month Reminder')->firstOrFail();
        $this->assertSame($hrUser->id, $created->created_by);
        $this->assertTrue($created->is_published);
        $this->assertNotNull($created->published_at);

        // Edit the created record (also preserves published status & posted date)
        Livewire::test(EditSystemUpdate::class, ['record' => $created->uuid])
            ->assertFormFieldDoesNotExist('published_at')
            ->assertFormFieldDoesNotExist('is_published')
            ->assertFormFieldDoesNotExist('version')
            ->fillForm([
                'title' => 'Year-End 13th Month Processing Notice',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $reloaded = $created->fresh();
        $this->assertSame('Year-End 13th Month Processing Notice', $reloaded->title);
        $this->assertTrue($reloaded->is_published);
        $this->assertNotNull($reloaded->published_at);

        // Newly created entry is immediately reflected in the portal popup without published date, version, or filter/badge clutter
        Livewire::test(SystemUpdatesModal::class, ['panelId' => 'hr'])
            ->assertSee('Year-End 13th Month Processing Notice')
            ->assertDontSee($reloaded->published_at->timezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y'))
            ->assertDontSee('v2026.11')
            ->assertDontSee('All Updates (')
            ->assertDontSee('Unread (')
            ->assertDontSee('su-pill--pinned')
            ->assertDontSee('su-card-date');

        // Re-announce clears acknowledgments
        SystemUpdateAcknowledgment::query()->create([
            'system_update_id' => $created->id,
            'user_id' => $hrUser->id,
            'acknowledged_at' => now(),
        ]);

        Livewire::test(ListSystemUpdates::class)
            ->assertTableColumnDoesNotExist('is_published')
            ->assertTableColumnDoesNotExist('published_at')
            ->assertTableColumnDoesNotExist('version')
            ->sortTable('acknowledgments_count')
            ->assertHasNoErrors()
            ->callAction('previewHrPopup')
            ->assertHasNoErrors()
            ->callAction('previewEmployeePopup')
            ->assertHasNoErrors()
            ->callTableAction('reannounce', $created);

        $this->assertSame(
            0,
            SystemUpdateAcknowledgment::query()->where('system_update_id', $created->id)->count()
        );

        // Global reset clears all acknowledgments across all updates
        SystemUpdateAcknowledgment::query()->create([
            'system_update_id' => $created->id,
            'user_id' => $hrUser->id,
            'acknowledged_at' => now(),
        ]);

        Livewire::test(ListSystemUpdates::class)
            ->callAction('resetAllReads')
            ->assertHasNoErrors();

        $this->assertSame(0, SystemUpdateAcknowledgment::query()->count());
    }

    public function test_unauthorized_hr_and_employees_cannot_access_settings_updates_management(): void
    {
        $unprivilegedHr = User::factory()->create([
            'username' => 'hr-no-perms',
            'password' => Hash::make('secret-pass'),
            'role' => 'hr',
            'is_disabled' => false,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('hr'));
        $this->actingAs($unprivilegedHr, 'web');

        $this->assertFalse(SystemUpdateResource::canAccess());
        $this->get('/hr/settings/updates-notes')->assertForbidden();

        $employee = $this->createEmployeeUser('PF0806', '0806');
        $this->actingAs($employee, 'web');
        $this->get('/hr/settings/updates-notes')->assertForbidden();
    }

    private function createHrUser(string $username, string $roleName): User
    {
        $user = User::factory()->create([
            'name' => $username,
            'username' => $username,
            'password' => Hash::make('secret-pass'),
            'role' => 'hr',
            'is_disabled' => false,
        ]);

        $user->assignRole($roleName);

        return $user;
    }

    private function createEmployeeUser(string $username, string $uid, ?int $branchId = null): User
    {
        $user = User::factory()->create([
            'name' => 'Employee '.$uid,
            'username' => $username,
            'password' => Hash::make('secret-pass'),
            'role' => 'employee',
            'is_disabled' => false,
        ]);

        Employee::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'uid' => $uid,
            'firstname' => 'Test',
            'lastname' => 'Employee'.$uid,
            'hired_date' => '2026-01-01',
            'employment_type' => 'REGULAR',
            'rate_type' => 'monthly',
            'monthly_rate' => 26000,
        ]);

        return $user;
    }
}
