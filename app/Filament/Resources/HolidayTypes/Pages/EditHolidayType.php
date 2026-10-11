<?php

namespace App\Filament\Resources\HolidayTypes\Pages;

use App\Filament\Resources\HolidayTypes\HolidayTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditHolidayType extends EditRecord
{
    protected static string $resource = HolidayTypeResource::class;

    protected string $view = 'filament.resources.holiday-types.pages.holiday-type-form';

    public function getSubheading(): string
    {
        return 'Update the holiday category and its payroll rate.';
    }
}
