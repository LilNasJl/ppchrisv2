<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class EducationLevelSummary extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Education Level Summary';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected function getData(): array
    {
        $data = Employee::query()
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'))
            ->pluck('school_level')
            ->map(fn (?string $level): string => filled($level) ? trim($level) : 'Unspecified')
            ->countBy()
            ->sortKeys();

        return [
            'datasets' => [
                [
                    'label' => 'Employees',
                    'data' => $data->values()->all(),
                    'backgroundColor' => [
                        '#2563EB',
                        '#16A34A',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6',
                        '#14B8A6',
                        '#64748B',
                    ],
                ],
            ],
            'labels' => $data->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'polarArea';
    }
}
