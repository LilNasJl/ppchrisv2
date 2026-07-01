<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Pages\ActivityCalendar;
use App\Filament\Resources\Activities\ActivityResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(ActivityCalendar::getUrl()),

            CreateAction::make()
                ->label('Add Activity'),
        ];
    }
}
