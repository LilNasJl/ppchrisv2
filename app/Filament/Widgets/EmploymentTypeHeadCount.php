<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EmploymentTypeHeadCount extends ChartWidget
{
    protected ?string $heading = 'Employment Type Head Count';
    protected int | string | array $columnSpan = 1; // Half width

    protected function getData(): array
    {
        $data = Employee::query()
            ->activeEmployment()
            // Join the users table
            ->join('users', 'employees.user_id', '=', 'users.id')
            // Filter by the role in the users table
            ->where('users.role', 'employee')
            ->select('employees.employment_type', DB::raw('count(*) as total'))
            ->groupBy('employees.employment_type')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Employees',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => [
                        '#36A2EB', 
                        '#FF6384', 
                        '#FFCE56', 
                        '#4BC0C0',
                    ],
                ],
            ],
            // Ensure labels match the grouped employment types
            'labels' => $data->pluck('employment_type')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
