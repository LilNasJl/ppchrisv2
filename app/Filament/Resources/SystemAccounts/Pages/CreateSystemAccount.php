<?php

namespace App\Filament\Resources\SystemAccounts\Pages;

use App\Filament\Resources\SystemAccounts\SystemAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemAccount extends CreateRecord
{
    protected static string $resource = SystemAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = $data['username'];

        return $data;
    }
}
