<?php

namespace App\Filament\Employee\Widgets;

use Filament\Widgets\ChartWidget;

class EmployeeDevelopmentStagePerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Development Stage Performance';

    protected ?string $description = 'No development stage evaluations recorded yet.';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        return [
            'datasets' => [
                ['label' => 'Performance', 'data' => [], 'borderColor' => '#60a5fa', 'backgroundColor' => '#60a5fa'],
            ],
            'labels' => [],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
