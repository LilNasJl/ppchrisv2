<?php

namespace Tests\Feature;

use App\Filament\Widgets\OverallDailyAttendanceSummaryChart;
use App\Filament\Widgets\OvertimeLogHoursChart;
use App\Models\Dtr;
use App\Models\PayrollPeriod;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardDtrChartFilterPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('hr'));
    }

    public function test_overtime_chart_restores_branch_and_period_from_its_url_state(): void
    {
        Livewire::withQueryParams([
            'overtimeFilters' => [
                'branch_id' => '12',
                'payroll_period_id' => '7',
            ],
        ])
            ->test(OvertimeLogHoursChart::class)
            ->assertSet('filters.branch_id', '12')
            ->assertSet('filters.payroll_period_id', '7');
    }

    public function test_attendance_chart_uses_a_separate_url_state_and_restores_its_date(): void
    {
        Livewire::withQueryParams([
            'attendanceFilters' => [
                'branch_id' => '12',
                'payroll_period_id' => '7',
                'date' => '2026-09-03',
            ],
        ])
            ->test(OverallDailyAttendanceSummaryChart::class)
            ->assertSet('filters.branch_id', '12')
            ->assertSet('filters.payroll_period_id', '7')
            ->assertSet('filters.date', '2026-09-03');
    }

    public function test_initial_chart_data_matches_its_filters_and_remains_stable_when_polled(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8));

        $currentPeriod = PayrollPeriod::query()->create([
            'title' => 'Current period',
            'date_start' => '2026-09-26',
            'date_end' => '2026-10-10',
            'date_payout' => '2026-10-15',
            'is_locked' => false,
        ]);

        $previousPeriod = PayrollPeriod::query()->create([
            'title' => 'Previous period',
            'date_start' => '2026-09-11',
            'date_end' => '2026-09-25',
            'date_payout' => '2026-09-30',
            'is_locked' => false,
        ]);

        Dtr::query()->create([
            'payroll_period_id' => $previousPeriod->id,
            'schedule_type' => 'Regular',
            'is_imported' => false,
            'overtime' => 60,
            'credited_overtime' => 60,
            'credited_early_clock_in' => 0,
            'overtime_approved' => true,
        ]);

        $cachedData = new \ReflectionMethod(OvertimeLogHoursChart::class, 'getCachedData');
        $component = Livewire::test(OvertimeLogHoursChart::class)
            ->assertSet('filters.payroll_period_id', $currentPeriod->id);

        $this->assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $cachedData->invoke($component->instance())['datasets'][0]['data']);
        $component->call('updateChartData')->assertNotDispatched('updateChartData');

        $component = Livewire::withQueryParams([
            'overtimeFilters' => ['payroll_period_id' => (string) $previousPeriod->id],
        ])->test(OvertimeLogHoursChart::class);

        $this->assertSame([1.0, 0.0, 0.0, 0.0, 0.0, 0.0], $cachedData->invoke($component->instance())['datasets'][0]['data']);
        $component->call('updateChartData')->assertNotDispatched('updateChartData');
        $this->assertSame([1.0, 0.0, 0.0, 0.0, 0.0, 0.0], $cachedData->invoke($component->instance())['datasets'][0]['data']);
    }

    public function test_pending_hours_count_only_eligible_unapproved_components_in_the_selected_branch(): void
    {
        foreach ([
            ['overtime' => 60, 'early_clock_in' => 30, 'overtime_status' => 'Pending'],
            ['overtime' => 60, 'early_clock_in' => 45, 'overtime_status' => 'Approved', 'overtime_approved' => true, 'credited_overtime' => 60],
            ['overtime' => 90, 'overtime_status' => null],
            ['overtime' => 120, 'early_clock_in' => 60, 'overtime_status' => 'Rejected'],
            ['overtime' => 29, 'early_clock_in' => 29, 'overtime_status' => 'Pending'],
            ['overtime' => 180, 'early_clock_in' => 180, 'schedule_type' => 'For Approval'],
            ['overtime' => 600, 'early_clock_in' => 600, 'branch_id' => 13],
        ] as $values) {
            Dtr::query()->create(array_replace([
                'branch_id' => 12,
                'schedule_type' => 'Regular',
                'is_imported' => false,
                'overtime_approved' => false,
                'early_clock_in_approved' => false,
            ], $values));
        }

        $component = Livewire::withQueryParams([
            'overtimeFilters' => ['branch_id' => '12', 'payroll_period_id' => null],
        ])->test(OvertimeLogHoursChart::class);

        $data = (new \ReflectionMethod(OvertimeLogHoursChart::class, 'getCachedData'))->invoke($component->instance());
        $this->assertSame([1.0, 0.0, 2.0, 1.0, 2.5, 1.25], $data['datasets'][0]['data']);
        $this->assertSame(['Pending Overtime', 'Pending Early Time-In'], array_slice($data['labels'], -2));
        $component->call('updateChartData')->assertNotDispatched('updateChartData');
    }
}
