<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class EmploymentTypeHeadCount extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Gender Distribution';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

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
                        '#36A2EB',
                        '#FF6384',
                        '#94A3B8',
                    ],
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
