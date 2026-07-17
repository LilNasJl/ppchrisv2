<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardHeadingIcon;
use App\Models\Department;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class HeadCountPerDept extends ChartWidget
{
    use HasDashboardHeadingIcon, HasWidgetShield;

    protected ?string $heading = 'Head Count Per Department';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected ?string $maxHeight = '300px';

    protected function getDashboardHeadingIcon(): Heroicon
    {
        return Heroicon::BuildingOffice2;
    }

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
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#1d4ed8',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
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
