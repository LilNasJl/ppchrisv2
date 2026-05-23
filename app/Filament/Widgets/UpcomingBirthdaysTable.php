<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingBirthdaysTable extends TableWidget
{
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected static ?string $heading = 'Upcoming Birthdays';

    public function table(Table $table): Table
    {
        $today = now()->format('m-d');

        return $table
            ->query(fn (): Builder => Employee::query()
                ->with('user')
                ->whereNotNull('birthdate')
                ->activeEmployment()
                ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
                ->orderByRaw("CASE WHEN DATE_FORMAT(birthdate, '%m-%d') < ? THEN 1 ELSE 0 END", [$today])
                ->orderByRaw("DATE_FORMAT(birthdate, '%m-%d')")
                ->orderBy('lastname'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['lastname', 'middlename', 'firstname'])
                    ->wrap(),

                TextColumn::make('birthdate')
                    ->label('Birthday')
                    ->icon('heroicon-s-cake')
                    ->formatStateUsing(fn ($state): string => Carbon::parse($state)->format('M d')),
            ])
            ->paginated([5, 10, 25]);
    }
}
