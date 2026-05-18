<?php

namespace App\Filament\Resources\SystemAccounts\Pages;

use App\Filament\Resources\SystemAccounts\SystemAccountResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSystemAccount extends ViewRecord
{
    protected static string $resource = SystemAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
