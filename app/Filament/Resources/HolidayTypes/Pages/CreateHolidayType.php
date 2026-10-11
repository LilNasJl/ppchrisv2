<?php

namespace App\Filament\Resources\HolidayTypes\Pages;

use App\Filament\Resources\HolidayTypes\HolidayTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHolidayType extends CreateRecord
{
    protected static string $resource = HolidayTypeResource::class;

    protected string $view = 'filament.resources.holiday-types.pages.holiday-type-form';

    public function getSubheading(): string
    {
        return 'Add a holiday category and its payroll rate.';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
