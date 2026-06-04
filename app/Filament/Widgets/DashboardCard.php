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

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 2,
            'md' => 5,
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
                ->color('primary'),

            Stat::make('Resigned Attrit. %', $this->coloredValue($this->attritionRate(['RESIGNED', 'FORCE RESIGNED']), '#d97706'))
                ->color('warning'),

            Stat::make('Termimated Attrit. %', $this->coloredValue($this->attritionRate(['TERMINATED']), '#dc2626'))
                ->color('danger'),

            Stat::make('Awol Attrit. %', $this->coloredValue($this->attritionRate(['AWOL', 'AWOL EMPLOYEE']), '#e11d48'))
                ->color('danger'),

            Stat::make('Avg. Employee Tenure', $this->coloredValue($this->averageTenureYears(), '#059669'))
                ->color('success'),
        ];
    }

    protected function employeeAccountQuery(): Builder
    {
        return Employee::query()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'));
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
