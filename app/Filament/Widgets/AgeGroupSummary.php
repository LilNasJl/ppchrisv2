<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AgeGroupSummary extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Age Group Summary';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected function getData(): array
    {
        $groups = [
            '18-24' => 0,
            '25-30' => 0,
            '31-40' => 0,
            '41-50' => 0,
            '51-60' => 0,
            '61 Above' => 0,
        ];

        Employee::query()
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'))
            ->whereNotNull('birthdate')
            ->pluck('birthdate')
            ->each(function (mixed $birthdate) use (&$groups): void {
                $age = Carbon::parse($birthdate)->age;

                match (true) {
                    $age >= 18 && $age <= 24 => $groups['18-24']++,
                    $age >= 25 && $age <= 30 => $groups['25-30']++,
                    $age >= 31 && $age <= 40 => $groups['31-40']++,
                    $age >= 41 && $age <= 50 => $groups['41-50']++,
                    $age >= 51 && $age <= 60 => $groups['51-60']++,
                    $age >= 61 => $groups['61 Above']++,
                    default => null,
                };
            });

        return [
            'datasets' => [
                [
                    'label' => 'Employees',
                    'data' => array_values($groups),
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#1D4ED8',
                ],
            ],
            'labels' => array_keys($groups),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
