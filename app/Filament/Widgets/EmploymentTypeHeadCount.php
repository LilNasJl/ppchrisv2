<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardHeadingIcon;
use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class EmploymentTypeHeadCount extends ChartWidget
{
    use HasDashboardHeadingIcon, HasWidgetShield;

    protected ?string $heading = 'Gender Distribution';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected ?string $maxHeight = '300px';

    protected function getDashboardHeadingIcon(): Heroicon
    {
        return Heroicon::Users;
    }

    protected function getData(): array
    {
        $baseQuery = Employee::query()
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'));

        $male = (clone $baseQuery)->where('gender', 'male')->count();
        $female = (clone $baseQuery)->where('gender', 'female')->count();
        $unspecified = (clone $baseQuery)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('gender')
                ->orWhereNotIn('gender', ['male', 'female']))
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Employees',
                    'data' => [$male, $female, $unspecified],
                    'backgroundColor' => [
                        '#2563eb',
                        '#38bdf8',
                        '#94a3b8',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Male', 'Female', 'Unspecified'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
