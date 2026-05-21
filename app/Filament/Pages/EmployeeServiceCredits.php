<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use UnitEnum;

class EmployeeServiceCredits extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.employee-service-credits';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Employee Service Credits';





    
}
