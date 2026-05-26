<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\PayrollPeriodBranches;
use App\Models\PayrollPeriod;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PayrollEmployeeTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Payroll Periods';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PayrollPeriod::query()->latest('date_start'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->label('Payroll Period')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('is_locked')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Locked' : 'Open')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewPayroll')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->url(fn (PayrollPeriod $record): string => PayrollPeriodBranches::getUrl([
                            'periodId' => $record->publicKey(),
                        ])),
                ]),
            ]);
    }
}
