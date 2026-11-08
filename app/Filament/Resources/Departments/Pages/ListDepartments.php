<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
use App\Models\Employee;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected string $view = 'filament.resources.departments.pages.list-departments';

    public function getSubheading(): string
    {
        return 'Manage company departments, acronyms, and organizational descriptions.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New department'),
        ];
    }

    /**
     * @return array{total_departments:int, total_employees:int, unassigned:int, top_department_name:string, top_department_count:int}
     */
    public function getDepartmentMetrics(): array
    {
        $departments = Department::query()->withCount('employees')->get();
        $totalDepartments = $departments->count();
        $totalEmployees = Employee::query()->whereNotNull('department_id')->count();
        $unassigned = $departments->where('employees_count', 0)->count();
        $topDepartment = $departments->sortByDesc('employees_count')->first();

        return [
            'total_departments' => $totalDepartments,
            'total_employees' => $totalEmployees,
            'unassigned' => $unassigned,
            'top_department_name' => $topDepartment?->name ?? 'None',
            'top_department_count' => $topDepartment?->employees_count ?? 0,
        ];
    }
}
