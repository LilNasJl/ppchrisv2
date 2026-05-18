<?php

namespace App\Filament\Resources\SystemAccounts\Pages;

use App\Filament\Resources\SystemAccounts\SystemAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSystemAccount extends EditRecord
{
    protected static string $resource = SystemAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
