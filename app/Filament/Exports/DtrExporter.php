<?php

namespace App\Filament\Exports;

use App\Models\Dtr;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class DtrExporter extends Exporter
{
    protected static ?string $model = Dtr::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('date_in')
                ->label('Date In'),

            ExportColumn::make('time_in')
                ->label('Time In'),

            ExportColumn::make('date_out')
                ->label('Date Out'),

            ExportColumn::make('time_out')
                ->label('Time Out'),

            ExportColumn::make('schedule_type')
                ->label('Schedule Type'),

            ExportColumn::make('schedule_start')
                ->label('Schedule Start'),

            ExportColumn::make('schedule_end')
                ->label('Schedule End'),

            ExportColumn::make('late')
                ->label('Late'),

            ExportColumn::make('undertime')
                ->label('Undertime'),

            ExportColumn::make('overtime')
                ->label('Overtime'),

            ExportColumn::make('early_clock_in')
                ->label('Early Clock In'),

            ExportColumn::make('credited_overtime')
                ->label('Credited Overtime'),

            ExportColumn::make('work_hrs')
                ->label('Work Hours'),

            ExportColumn::make('credited_work_hrs')
                ->label('Credited Work Hours'),

            ExportColumn::make('overtime_status')
                ->label('Overtime Status'),

            ExportColumn::make('is_absent')
                ->label('Absent')
                ->formatStateUsing(fn ($state): string => $state ? 'Yes' : 'No'),

            ExportColumn::make('holiday_type')
                ->label('Holiday Type'),

            ExportColumn::make('holiday_rate')
                ->label('Holiday Rate'),

            ExportColumn::make('daily_rate')
                ->label('Daily Rate'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Exported ' . Number::format($export->successful_rows) . ' D.T.R rows.';

        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failed) . ' failed.';
        }

        return $body;
    }
}
