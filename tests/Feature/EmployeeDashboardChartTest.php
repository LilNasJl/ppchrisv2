<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Dashboard;
use App\Filament\Employee\Widgets\EmployeeDevelopmentStagePerformanceChart;
use App\Filament\Employee\Widgets\EmployeeEvaluationTrainingModulesChart;
use App\Filament\Employee\Widgets\EmployeeLatesAbsencesChart;
use App\Filament\Employee\Widgets\EmployeePersonalKpiRatingsChart;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeDashboardChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('employee'));
    }

    public function test_late_and_absent_days_use_visible_records_without_double_counting_finalized_records(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 11));

        $user = User::factory()->create(['role' => 'employee']);
        $branch = Branch::query()->create([
            'branch_name' => 'Test Station',
            'branch_address' => 'Test Address',
            'mobile_no' => '09123456789',
            'employee_id' => 0,
        ]);
        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'uid' => 99101,
            'fingerprint_id' => '99101',
            'branch_id' => $branch->id,
        ]);
        $january = PayrollPeriod::query()->create([
            'title' => 'January',
            'date_start' => '2026-01-01',
            'date_end' => '2026-01-15',
            'date_payout' => '2026-01-20',
            'is_locked' => false,
        ]);
        $february = PayrollPeriod::query()->create([
            'title' => 'February',
            'date_start' => '2026-02-01',
            'date_end' => '2026-02-15',
            'date_payout' => '2026-02-20',
            'is_locked' => false,
        ]);

        Dtr::query()->create([
            'payroll_period_id' => $january->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => '99101',
            'date_in' => '2026-01-10',
            'schedule_type' => 'absent',
            'is_absent' => true,
            'is_imported' => false,
        ]);
        Dtr::query()->create([
            'payroll_period_id' => $february->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => '99101',
            'date_in' => '2026-02-03',
            'time_in' => '08:15:00',
            'late' => 15,
            'is_imported' => false,
        ]);

        foreach ([15, 10] as $lateMinutes) {
            EmployeeVisibleDtr::query()->create([
                'payroll_period_id' => $february->id,
                'branch_id' => $branch->id,
                'employee_id' => $employee->id,
                'fingerprint_id' => '99101',
                'date_in' => '2026-02-04',
                'time_in' => '08:15:00',
                'late' => $lateMinutes,
                'is_imported' => false,
            ]);
        }

        $this->actingAs($user);
        $chart = Livewire::test(EmployeeLatesAbsencesChart::class);
        $data = (new \ReflectionMethod(EmployeeLatesAbsencesChart::class, 'getCachedData'))->invoke($chart->instance());

        $this->assertCount(12, $data['labels']);
        $this->assertSame(1, $data['datasets'][0]['data'][1]);
        $this->assertSame(0, $data['datasets'][0]['data'][0]);
        $this->assertSame(1, $data['datasets'][1]['data'][0]);
        $this->assertSame(0, $data['datasets'][1]['data'][1]);
    }

    public function test_development_chart_does_not_display_invented_scores(): void
    {
        $chart = Livewire::test(EmployeeDevelopmentStagePerformanceChart::class);
        $data = (new \ReflectionMethod(EmployeeDevelopmentStagePerformanceChart::class, 'getCachedData'))->invoke($chart->instance());

        $this->assertSame([], $data['datasets'][0]['data']);
    }

    public function test_new_kpi_and_training_charts_do_not_display_invented_scores(): void
    {
        foreach ([EmployeePersonalKpiRatingsChart::class, EmployeeEvaluationTrainingModulesChart::class] as $chartClass) {
            $chart = Livewire::test($chartClass);
            $data = (new \ReflectionMethod($chartClass, 'getCachedData'))->invoke($chart->instance());

            $this->assertSame([], $data['labels']);
            foreach ($data['datasets'] as $dataset) {
                $this->assertSame([], $dataset['data']);
            }
        }
    }

    public function test_dashboard_cards_open_their_detail_modals(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertDontSee('Welcome back')
            ->assertSee('My Dashboard')
            ->assertSee('Shout Out Hall')
            ->assertSee('Sample posts')
            ->assertSee('Leave Form')
            ->assertSee('OT Form')
            ->assertSee('Change Shift')
            ->call('showAnnouncements')
            ->assertDispatched('open-modal', id: 'employee-announcements')
            ->call('showUpcomingHolidays')
            ->assertDispatched('open-modal', id: 'employee-upcoming-holidays')
            ->call('showUpcomingActivities')
            ->assertDispatched('open-modal', id: 'employee-upcoming-activities');
    }

    public function test_shout_out_hall_displays_five_placeholder_messages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'employee']));

        $page = Livewire::test(Dashboard::class)
            ->assertSee('Lorem Ipsum')
            ->assertSee('Tempor Incididunt')
            ->assertDontSee('Alex Rivera');

        $this->assertSame(5, substr_count($page->html(), 'class="employee-dashboard-shoutout-message"'));
    }
}
