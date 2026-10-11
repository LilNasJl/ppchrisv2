<?php

namespace App\Filament\Resources\HolidayTypes\Pages;

use App\Filament\Pages\HolidayCalendar;
use App\Filament\Resources\HolidayTypes\HolidayTypeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListHolidayTypes extends ListRecords
{
    protected static string $resource = HolidayTypeResource::class;

    protected string $view = 'filament.resources.holiday-types.pages.list-holiday-types';

    public function getSubheading(): string
    {
        return 'Configure holiday categories and their payroll rates.';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(HolidayCalendar::getUrl()),

            CreateAction::make()
                ->label('Add Holiday Type'),
        ];
    }
}
