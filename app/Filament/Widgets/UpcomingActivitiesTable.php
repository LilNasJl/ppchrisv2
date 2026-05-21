<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingActivitiesTable extends TableWidget
{
    use HasWidgetShield;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming Activities';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Activity::query()
                ->whereDate('date_to', '>=', now()->toDateString())
                ->orderBy('date_from'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('date_from')
                    ->label('From')
                    ->date()
                    ->icon(Heroicon::CalendarDays),

                TextColumn::make('date_to')
                    ->label('To')
                    ->date(),

                TextColumn::make('title')
                    ->label('Activity')
                    ->wrap(),
            ])
            ->paginated([5, 10, 25]);
    }
}
