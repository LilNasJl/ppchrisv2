<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\OverallDailyAttendanceSummaryChart;
use App\Filament\Widgets\OvertimeLogHoursChart;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\PayrollPeriod;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrDashboardEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::query()->where('username', 'masteradmin')->first()
            ?? User::withoutEvents(fn (): User => User::query()->create([
                'name' => 'Master Admin',
                'username' => 'masteradmin',
                'email' => 'masteradmin@example.test',
                'password' => 'password',
                'role' => 'admin',
            ]));

        Filament::setCurrentPanel(Filament::getPanel('hr'));
        Filament::auth()->setUser($this->user);
        auth()->setUser($this->user);
    }

    public function test_dashboard_url_with_nested_chart_filters_redirects_to_clean_url(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/hr/dashboard?attendanceFilters[payroll_period_id]=10&attendanceFilters[date]=2026-10-12+08%3A05%3A43&overtimeFilters[payroll_period_id]=10');

        $response->assertRedirect('/hr/dashboard');
    }

    public function test_dashboard_page_renders_successfully(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/hr/dashboard');

        $response->assertSuccessful();
        $response->assertSee('Dashboard: Employee Snapshot');
        $response->assertSee('Shout Out Hall');
        $response->assertSee('ppc-dashboard-shoutout');
        $response->assertSee('Attendance & Leave Overview');
    }

    public function test_dashboard_component_mounts_cleanly_via_livewire(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSee('Dashboard: Employee Snapshot')
            ->assertSee('Shout Out Hall')
            ->assertSee('ppc-dashboard-shoutout')
            ->assertSee('Attendance & Leave Overview');
    }

    public function test_charts_calculate_properly_with_dates_and_periods(): void
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Sample Period',
            'date_start' => '2026-09-26',
            'date_end' => '2026-10-10',
            'date_payout' => '2026-10-15',
            'is_locked' => false,
        ]);

        $branch = Branch::query()->create([
            'branch_name' => 'Main Branch',
            'branch_address' => '123 Main St',
        ]);

        Dtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'date_in' => '2026-10-10',
            'date_out' => '2026-10-10',
            'time_in' => '08:00:00',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
            'late' => 15,
            'is_absent' => false,
            'overtime' => 60,
            'credited_overtime' => 60,
            'credited_early_clock_in' => 0,
            'overtime_approved' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(OverallDailyAttendanceSummaryChart::class)
            ->set('filters.payroll_period_id', $period->id)
            ->set('filters.date', '2026-10-10')
            ->assertSet('filters.payroll_period_id', $period->id);

        Livewire::test(OvertimeLogHoursChart::class)
            ->set('filters.payroll_period_id', $period->id)
            ->assertSet('filters.payroll_period_id', $period->id);
    }
}
