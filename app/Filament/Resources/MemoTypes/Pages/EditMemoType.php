<?php

namespace App\Filament\Resources\MemoTypes\Pages;

use App\Filament\Resources\MemoTypes\MemoTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMemoType extends EditRecord
{
    protected static string $resource = MemoTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
