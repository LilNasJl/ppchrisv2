<?php

namespace App\Filament\Widgets;

use App\Models\Holiday;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingHolidaysTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected static ?string $heading = 'Upcoming Holidays';

    public function table(Table $table): Table
    {
        $today = now()->startOfDay();

        return $table
            ->query(fn (): Builder => Holiday::query()
                ->with('type')
                ->select('holidays.*')
                ->selectRaw(
                    "CASE WHEN is_recurring = 1 THEN STR_TO_DATE(CONCAT(CASE WHEN month_day >= ? THEN ? ELSE ? END, '-', month_day), '%Y-%m-%d') ELSE `date` END as occurrence_date",
                    [$today->format('m-d'), $today->year, $today->copy()->addYear()->year],
                )
                ->whereNull('branch_id')
                ->where(function (Builder $query) use ($today): void {
                    $query
                        ->whereDate('date', '>=', $today->toDateString())
                        ->orWhere('is_recurring', true);
                })
                ->orderBy('occurrence_date'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('occurrence_date')
                    ->label('Date')
                    ->date()
                    ->getStateUsing(fn (Holiday $record): string => $this->getOccurrenceDate($record)->toDateString()),

                TextColumn::make('title')
                    ->label('Holiday')
                    ->wrap(),

                TextColumn::make('type.type')
                    ->label('Type')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('type.rate')
                    ->label('Rate (%)'),
            ])
            ->paginated([5, 10, 25]);
    }

    protected function getOccurrenceDate(Holiday $holiday): Carbon
    {
        if (! $holiday->is_recurring || blank($holiday->month_day)) {
            return Carbon::parse($holiday->date);
        }

        $date = Carbon::createFromFormat('Y-m-d', now()->year.'-'.$holiday->month_day)->startOfDay();

        return $date->lt(now()->startOfDay()) ? $date->addYear() : $date;
    }
}
