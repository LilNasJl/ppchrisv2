<?php

namespace App\Filament\Widgets;

use App\Models\Holiday;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingHolidaysTable extends TableWidget
{
    use HasWidgetShield;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming Holidays';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Holiday::query()
                ->with('type')
                ->whereDate('date', '>=', now()->toDateString())
                ->orderBy('date'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date(),

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
}
