<?php

namespace App\Filament\Employee\Widgets;

use Filament\Widgets\ChartWidget;

class EmployeePersonalKpiRatingsChart extends ChartWidget
{
    protected ?string $heading = 'Personal KPI Ratings';

    protected ?string $description = 'No published personal KPI ratings are available yet.';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        return [
            'datasets' => [
                ['label' => 'Rating', 'data' => [], 'borderColor' => '#34d399', 'backgroundColor' => '#34d399'],
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
