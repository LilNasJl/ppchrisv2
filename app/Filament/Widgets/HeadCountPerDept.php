<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class HeadCountPerDept extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Head Count Per Department';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected function getData(): array
    {
        $departments = Department::query()
            ->withCount([
                'employees as active_employee_count' => fn (Builder $query): Builder => $query
                    ->activeEmployment()
                    ->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->where('role', 'employee')),
            ])
            ->orderBy('name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'No. of employees',
                    'data' => $departments->pluck('active_employee_count')->all(),
                ],
            ],
            'labels' => $departments
                ->map(fn (Department $department): string => $department->acronym ?: ($department->name ?: 'Unnamed Department'))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
