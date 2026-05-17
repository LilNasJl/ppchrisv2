<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Deductions\DeductionResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Query\Builder;
use Override;
use UnitEnum;

class EmployeeDetails extends Page 
{


    protected string $view = 'filament.pages.employee-details';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Identification;
    protected static string|UnitEnum|null $navigationGroup = "Employee Management";
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
