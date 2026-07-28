<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Filament\Widgets\PayrollEmployeeTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use UnitEnum;

class Payroll extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation and Benefits Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Payroll Processing';

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('managePayrollPeriods')
                ->label('Manage Payroll Periods')
                ->icon(Heroicon::CalendarDays)
                ->url(PayrollPeriodResource::getUrl()),

            Action::make('payrollVisibility')
                ->label('Payroll Visibility')
                ->icon(Heroicon::Eye)
                ->url(PayrollVisibility::getUrl()),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PayrollEmployeeTable::class,
        ];
    }
}
