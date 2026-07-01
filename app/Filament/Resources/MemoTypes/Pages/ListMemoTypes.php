<?php

namespace App\Filament\Resources\MemoTypes\Pages;

use App\Filament\Pages\Memo;
use App\Filament\Resources\MemoTypes\MemoTypeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMemoTypes extends ListRecords
{
    protected static string $resource = MemoTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Memo::getUrl()),

            CreateAction::make()
                ->label('Add Memo Type')
                ->icon(Heroicon::Plus),
        ];
    }
}
