<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\EmployeeImport;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    // protected ?string $subheading = 'Manage and view employee acounts';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importEmployee')
                ->label('Import Employee')
                ->icon(Heroicon::ArrowDownTray)
                ->url(EmployeeImport::getUrl()),

            Action::make('importHistory')
                ->label('Import History')
                ->icon(Heroicon::QueueList)
                ->url(UserResource::getUrl('import-history')),

            CreateAction::make(),
        ];
    }
}
