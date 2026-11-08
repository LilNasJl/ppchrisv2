<?php

namespace App\Filament\Resources\Designations\Pages;

use App\Filament\Resources\Designations\DesignationResource;
use App\Models\Designation;
use App\Models\Employee;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDesignations extends ListRecords
{
    protected static string $resource = DesignationResource::class;

    protected string $view = 'filament.resources.designations.pages.list-designations';

    public function getSubheading(): string
    {
        return 'Manage job titles, specifications, and role descriptions.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New designation'),
        ];
    }

    /**
     * @return array{total_designations:int, total_employees:int, unassigned:int, top_designation_name:string, top_designation_count:int}
     */
    public function getDesignationMetrics(): array
    {
        $designations = Designation::query()->withCount('employees')->get();
        $totalDesignations = $designations->count();
        $totalEmployees = Employee::query()->whereNotNull('designation_id')->count();
        $unassigned = $designations->where('employees_count', 0)->count();
        $topDesignation = $designations->sortByDesc('employees_count')->first();

        return [
            'total_designations' => $totalDesignations,
            'total_employees' => $totalEmployees,
            'unassigned' => $unassigned,
            'top_designation_name' => $topDesignation?->title ?? 'None',
            'top_designation_count' => $topDesignation?->employees_count ?? 0,
        ];
    }
}
