<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Filament\Pages\Payroll;
use App\Filament\Pages\PayrollByBranch;
use App\Filament\Pages\PayrollSummary;
use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPayrollPeriod extends ViewRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return to List')
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(PayrollPeriodResource::getUrl()),

            Action::make('open_payroll')
                ->label('Payroll Processing')
                ->icon('heroicon-o-table-cells')
                ->color('primary')
                ->url(fn (): string => Payroll::getUrl(['periodId' => $this->record->publicKey()])),

            Action::make('open_branch')
                ->label('Payroll By Branch')
                ->icon('heroicon-o-building-storefront')
                ->color('info')
                ->url(fn (): string => PayrollByBranch::getUrl(['periodId' => $this->record->publicKey()])),

            Action::make('open_summary')
                ->label('Payroll Summary')
                ->icon('heroicon-o-chart-bar')
                ->color('success')
                ->url(fn (): string => PayrollSummary::getUrl(['periodId' => $this->record->publicKey()])),

            EditAction::make()
                ->visible(fn (): bool => ! (bool) $this->record->is_locked),
        ];
    }
}
