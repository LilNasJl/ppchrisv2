<?php

namespace App\Filament\Imports;

use App\Models\Leave;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class LeaveImporter extends Importer
{
    protected static ?string $model = Leave::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('employee_id')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('leave_type')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('leave_from')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('leave_to')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('reason')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): Leave
    {
        return new Leave();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your leave import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
