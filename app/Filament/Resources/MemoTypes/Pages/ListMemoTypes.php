<?php

namespace App\Filament\Resources\MemoTypes\Pages;

use App\Filament\Resources\MemoTypes\MemoTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListMemoTypes extends ListRecords
{
    protected static string $resource = MemoTypeResource::class;

    protected string $view = 'filament.resources.memo-types.pages.list-memo-types';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Notice Type')
                ->icon(Heroicon::Plus)
                ->schema(fn (): array => MemoTypeResource::form(Schema::make())->getComponents())
                ->modalHeading('Add Notice Type')
                ->modalSubmitActionLabel('Create Notice Type')
                ->modalWidth(Width::Large)
                ->createAnother(false),
        ];
    }
}
