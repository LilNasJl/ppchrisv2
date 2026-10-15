<?php

namespace App\Filament\Resources\SystemAccounts\Pages;

use App\Filament\Resources\SystemAccounts\SystemAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSystemAccounts extends ListRecords
{
    protected static string $resource = SystemAccountResource::class;

    protected string $view = 'filament.resources.system-accounts.pages.list-system-accounts';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New System Account')
                ->icon(Heroicon::Plus),
        ];
    }
}
