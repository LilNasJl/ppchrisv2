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

    protected ?string $maxHeight = '300px';

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
                        '#1d4ed8',
                        '#2563eb',
                        '#3b82f6',
                        '#60a5fa',
                        '#7dd3fc',
                        '#0ea5e9',
                        '#64748b',
                    ],
                    'borderWidth' => 1,
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
