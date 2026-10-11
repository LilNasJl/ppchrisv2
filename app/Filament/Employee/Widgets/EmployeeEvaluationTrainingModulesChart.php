<?php

namespace App\Filament\Employee\Widgets;

use Filament\Widgets\ChartWidget;

class EmployeeEvaluationTrainingModulesChart extends ChartWidget
{
    protected ?string $heading = 'Evaluation & Training Modules';

    protected ?string $description = 'No completed evaluations or training modules recorded yet.';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        return [
            'datasets' => [
                ['label' => 'Evaluations', 'data' => [], 'backgroundColor' => '#60a5fa'],
                ['label' => 'Training modules', 'data' => [], 'backgroundColor' => '#a78bfa'],
            ],
            'labels' => [],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
