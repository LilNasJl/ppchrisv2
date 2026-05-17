<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Filament\Pages\Payroll;
use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Payroll::getUrl()),

            CreateAction::make(),
        ];
    }
}
