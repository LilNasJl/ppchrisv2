<?php

namespace App\Filament\Resources\MemoTypes\Pages;

use App\Filament\Resources\MemoTypes\MemoTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMemoType extends CreateRecord
{
    protected static string $resource = MemoTypeResource::class;
}
