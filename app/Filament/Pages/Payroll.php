<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Filament\Widgets\PayrollEmployeeTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use UnitEnum;

class Payroll extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll Management';

    protected static ?int $navigationSort = 2;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('payrollSummary')
                    ->label('Payroll Summary')
                    ->icon(Heroicon::ChartBar)
                    ->url(PayrollSummary::getUrl()),

                Action::make('payrollByBranch')
                    ->label('Payroll By Branch')
                    ->icon(Heroicon::BuildingStorefront)
                    ->url(PayrollByBranch::getUrl()),

                Action::make('managePayrollPeriods')
                    ->label('Manage Payroll Periods')
                    ->icon(Heroicon::CalendarDays)
                    ->url(PayrollPeriodResource::getUrl()),
            ])
                ->label('Manage Payroll')
                ->icon(Heroicon::ChevronDown)
                ->button(),
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
