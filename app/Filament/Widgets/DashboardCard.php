<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class DashboardCard extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected string $view = 'filament.widgets.dashboard-card';

    protected int|string|array $columnSpan = 'full';

    public bool $showAttritionBreakdown = false;

    protected function getColumns(): int|array
    {
        return [
            'default' => 2,
            'md' => 4,
        ];
    }

    protected function getStats(): array
    {
        $totalEmployees = Employee::query()
            ->activeEmployment()
            ->whereHas('user', fn ($query) => $query->where('role', 'employee'))
            ->count();

        return [
            Stat::make('Total Employee', $this->coloredValue($totalEmployees, '#2563eb'))
                ->color('primary')
                ->extraAttributes(['data-dashboard-kpi' => 'total-employees']),

            Stat::make('Head Count Growth', $this->coloredValue($this->signedCount($this->headCountGrowthThisMonth()), '#0284c7'))
                ->color('info')
                ->extraAttributes(['data-dashboard-kpi' => 'head-count-growth']),

            Stat::make('Over All Attrition Rate', $this->coloredValue($this->attritionRate($this->combinedAttritionTypes()), '#dc2626'))
                ->color('danger')
                ->extraAttributes([
                    'data-dashboard-kpi' => 'attrition-rate',
                    'class' => 'cursor-pointer transition hover:ring-2 hover:ring-danger-500/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger-500/70',
                    'role' => 'button',
                    'tabindex' => '0',
                    'wire:click' => 'openAttritionBreakdown',
                    'wire:keydown.enter' => 'openAttritionBreakdown',
                    'wire:keydown.space.prevent' => 'openAttritionBreakdown',
                ]),

            Stat::make('Avg. Employee Tenure', $this->coloredValue($this->averageTenureYears(), '#0ea5e9'))
                ->color('info')
                ->extraAttributes(['data-dashboard-kpi' => 'average-tenure']),
        ];
    }

    protected function employeeAccountQuery(): Builder
    {
        return Employee::query()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'));
    }

    public function openAttritionBreakdown(): void
    {
        $this->showAttritionBreakdown = true;
    }

    public function closeAttritionBreakdown(): void
    {
        $this->showAttritionBreakdown = false;
    }

    protected function headCountGrowthThisMonth(): int
    {
        return (clone $this->employeeAccountQuery())
            ->activeEmployment()
            ->whereBetween('hired_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->count();
    }

    protected function signedCount(int $value): string
    {
        return '+'.number_format($value);
    }

    /**
     * @param  array<int, string>  $employmentTypes
     */
    protected function attritionRate(array $employmentTypes): string
    {
        $totalEmployees = (clone $this->employeeAccountQuery())->count();

        if ($totalEmployees < 1) {
            return '0.0%';
        }

        $separatedEmployees = (clone $this->employeeAccountQuery())
            ->whereIn(DB::raw('UPPER(TRIM(employment_type))'), $employmentTypes)
            ->count();

        return number_format(($separatedEmployees / $totalEmployees) * 100, 1).'%';
    }

    /**
     * @return array<int, array{type: string, label: string, percentage: string, count: int, color: string}>
     */
    public function getAttritionBreakdown(): array
    {
        return [
            [
                'type' => 'Resigned',
                'label' => 'Resigned Attrition Rate',
                'percentage' => $this->attritionRate($this->resignedAttritionTypes()),
                'count' => $this->attritionCount($this->resignedAttritionTypes()),
                'color' => '#d97706',
            ],
            [
                'type' => 'Terminated',
                'label' => 'Terminated Attrition Rate',
                'percentage' => $this->attritionRate($this->terminatedAttritionTypes()),
                'count' => $this->attritionCount($this->terminatedAttritionTypes()),
                'color' => '#dc2626',
            ],
            [
                'type' => 'AWOL',
                'label' => 'AWOL Attrition Rate',
                'percentage' => $this->attritionRate($this->awolAttritionTypes()),
                'count' => $this->attritionCount($this->awolAttritionTypes()),
                'color' => '#e11d48',
            ],
        ];
    }

    /**
     * @param  array<int, string>  $employmentTypes
     */
    protected function attritionCount(array $employmentTypes): int
    {
        return (clone $this->employeeAccountQuery())
            ->whereIn(DB::raw('UPPER(TRIM(employment_type))'), $employmentTypes)
            ->count();
    }

    /**
     * @return array<int, string>
     */
    protected function combinedAttritionTypes(): array
    {
        return [
            ...$this->resignedAttritionTypes(),
            ...$this->terminatedAttritionTypes(),
            ...$this->awolAttritionTypes(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function resignedAttritionTypes(): array
    {
        return ['RESIGNED', 'FORCE RESIGNED'];
    }

    /**
     * @return array<int, string>
     */
    protected function terminatedAttritionTypes(): array
    {
        return ['TERMINATED'];
    }

    /**
     * @return array<int, string>
     */
    protected function awolAttritionTypes(): array
    {
        return ['AWOL', 'AWOL EMPLOYEE'];
    }

    protected function averageTenureYears(): string
    {
        $today = now()->startOfDay();
        $averageDays = (clone $this->employeeAccountQuery())
            ->activeEmployment()
            ->whereNotNull('hired_date')
            ->pluck('hired_date')
            ->avg(fn (mixed $hiredDate): int => Carbon::parse($hiredDate)->startOfDay()->diffInDays($today));

        return number_format(((float) $averageDays) / 365.2425, 1).' yrs';
    }

    protected function coloredValue(int|string $value, string $color): HtmlString
    {
        return new HtmlString('<span style="color: '.$color.'; font-weight: 800;">'.e((string) $value).'</span>');
    }
}
