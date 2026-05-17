<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class HeadCountPerDept extends ChartWidget
{
    protected ?string $heading = 'Head Count Per Department';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected function getData(): array
    {
        $data = Employee::select('department_id', DB::raw('count(*) as total'))
            ->activeEmployment()
            // Filter by the role in the related 'user' table
            ->whereHas('user', function ($query) {
                $query->where('role', 'employee');
            })
            ->groupBy('department_id')
            ->with('department')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'No. of employees',
                    'data' => $data->pluck('total'),
                ],
            ],
            'labels' => $data->map(function ($item) {
                // Using department acronym as requested
                return $item->department->acronym ?? 'Unknown';
            }),
        ];

    }

    protected function getType(): string
    {
        return 'bar';
    }
}
