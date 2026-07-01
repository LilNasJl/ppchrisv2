<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class EmployeeTenureTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected static ?string $heading = 'Employee Tenure';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Employee::query()
                ->with('user')
                ->whereNotNull('hired_date')
                ->activeEmployment()
                ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
                ->orderBy('hired_date'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('fullname')
                    ->label('Full Name')
                    ->getStateUsing(fn (Employee $record): string => trim($record->lastname.', '.(filled($record->middlename) ? $record->middlename.'. ' : '').$record->firstname)),

                TextColumn::make('tenure')
                    ->label('Tenure')
                    ->icon(Heroicon::Clock)
                    ->getStateUsing(fn (Employee $record): string => $record->tenure),
            ])
            ->paginated([10, 25, 50]);
    }
}
