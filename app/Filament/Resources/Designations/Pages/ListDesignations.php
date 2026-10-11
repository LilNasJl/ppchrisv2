<?php

namespace App\Filament\Resources\Designations\Pages;

use App\Filament\Resources\Designations\DesignationResource;
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
            CreateAction::make(),
        ];
    }
}
