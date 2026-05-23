<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EmployeeDetails extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.employee-details';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Identification;

    protected static string|UnitEnum|null $navigationGroup = 'Employee Management';

    protected static ?int $navigationSort = 1;

    // protected ?string $subheading = 'Manage and view employee profiles and employement data';

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('add')
    //             ->label('Add')
    //     ];
    // }

}
