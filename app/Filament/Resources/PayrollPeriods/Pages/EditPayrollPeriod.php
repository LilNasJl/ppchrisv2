<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditPayrollPeriod extends EditRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        if (! (bool) $this->record->is_locked) {
            return;
        }

        Notification::make()
            ->title('Payroll period is locked')
            ->body('Locked payroll periods cannot be edited or unlocked.')
            ->danger()
            ->send();

        throw new Halt;
    }
}
