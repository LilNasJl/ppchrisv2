<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Imports\EmployeeImporter;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    // protected ?string $subheading = 'Manage and view employee acounts';

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make('importEmployee')
                ->label('Import Employee')
                ->icon(Heroicon::ArrowDownTray)
                ->importer(EmployeeImporter::class),

            CreateAction::make(),
        ];
    }
}
