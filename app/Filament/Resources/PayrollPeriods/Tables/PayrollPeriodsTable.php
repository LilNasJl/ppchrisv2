<?php

namespace App\Filament\Resources\PayrollPeriods\Tables;

use App\Models\PayrollPeriod;
use App\Services\PayrollPeriodLockService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date_start', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_start')
                    ->label('Date Start')
                    ->date()
                    ->sortable(),

                TextColumn::make('date_end')
                    ->label('Date End')
                    ->date()
                    ->sortable(),

                TextColumn::make('date_payout')
                    ->label('Date Payout')
                    ->date()
                    ->sortable(),

                ToggleColumn::make('is_locked')
                    ->label('Locked')
                    ->onColor('danger')
                    ->offColor('success')
                    ->updateStateUsing(function (PayrollPeriod $record, mixed $state): bool {
                        app(PayrollPeriodLockService::class)->setLocked($record, (bool) $state);

                        return (bool) $state;
                    }),

                TextColumn::make('description')
                    ->limit(80)
                    ->wrap(),
            ])
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')
                    ->color('primary'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
