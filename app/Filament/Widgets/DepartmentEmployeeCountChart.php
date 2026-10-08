<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use App\Models\Employee;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DepartmentEmployeeCountChart extends ChartWidget
{
    protected ?string $heading = 'Total Employees by Department';

    protected ?string $description = 'Employee account headcount by department, including ended employment.';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $departments = Department::query()
            ->withCount([
                'employees as employee_count' => fn (Builder $query): Builder => $query
                    ->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->where('role', 'employee'))
                    ->where(fn (Builder $employeeQuery): Builder => $employeeQuery
                        ->whereNull('employment_type')
                        ->orWhereNotIn(
                            DB::raw('UPPER(TRIM(employment_type))'),
                            array_map('strtoupper', [...Employee::ENDED_EMPLOYMENT_TYPES, 'Contract Ended']),
                        )),
            ])
            ->orderBy('name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Employees',
                    'data' => $departments->pluck('employee_count')->all(),
                    'backgroundColor' => '#2563eb',
                    'borderColor' => '#1d4ed8',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $departments->map(fn (Department $department): string => $department->name ?: 'Unnamed Department')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
                'y' => ['grid' => ['display' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
