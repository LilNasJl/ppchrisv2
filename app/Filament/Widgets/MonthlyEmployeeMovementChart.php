<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\EmployeeEmploymentTypeChange;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MonthlyEmployeeMovementChart extends ChartWidget
{
    protected ?string $heading = 'New Hires and Exits by Month';

    protected ?string $description = 'Monthly totals use hire dates and recorded employment end dates.';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    public function mount(): void
    {
        $this->filter = (string) now()->year;
        parent::mount();
    }

    protected function getFilters(): ?array
    {
        $employmentType = DB::raw('UPPER(TRIM(employment_type))');
        $hireDates = Employee::query()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'))
            ->whereNotNull('hired_date')
            ->pluck('hired_date');

        $exitDates = EmployeeEmploymentTypeChange::query()
            ->whereIn($employmentType, $this->exitEmploymentTypes())
            ->whereHas('employee.user', fn (Builder $query): Builder => $query->where('role', 'employee'))
            ->pluck('effective_date');

        return $hireDates
            ->merge($exitDates)
            ->map(fn (mixed $date): int => Carbon::parse($date)->year)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])
            ->all();
    }

    protected function getData(): array
    {
        $filters = $this->getFilters() ?? [];
        $year = isset($filters[$this->filter ?? '']) ? (int) $this->filter : (int) now()->year;
        $start = Carbon::create($year, 1, 1)->toDateString();
        $end = Carbon::create($year, 12, 31)->toDateString();

        $hireCounts = array_fill(1, 12, 0);
        Employee::query()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'))
            ->whereBetween('hired_date', [$start, $end])
            ->pluck('hired_date')
            ->each(function (mixed $date) use (&$hireCounts): void {
                $hireCounts[Carbon::parse($date)->month]++;
            });

        $exitCounts = array_fill(1, 12, 0);
        EmployeeEmploymentTypeChange::query()
            ->whereIn(DB::raw('UPPER(TRIM(employment_type))'), $this->exitEmploymentTypes())
            ->whereBetween('effective_date', [$start, $end])
            ->whereHas('employee.user', fn (Builder $query): Builder => $query->where('role', 'employee'))
            ->get(['employee_id', 'effective_date'])
            ->unique(fn (EmployeeEmploymentTypeChange $change): string => $change->employee_id.'-'.$change->effective_date->month)
            ->each(function (EmployeeEmploymentTypeChange $change) use (&$exitCounts): void {
                $exitCounts[$change->effective_date->month]++;
            });

        return [
            'datasets' => [
                [
                    'label' => 'New Hires',
                    'data' => array_values($hireCounts),
                    'backgroundColor' => '#2563eb',
                    'borderColor' => '#1d4ed8',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Resigned / Exits',
                    'data' => array_values($exitCounts),
                    'backgroundColor' => '#f97316',
                    'borderColor' => '#c2410c',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function exitEmploymentTypes(): array
    {
        return array_map('strtoupper', [
            ...Employee::ENDED_EMPLOYMENT_TYPES,
            'Contract Ended',
        ]);
    }
}
