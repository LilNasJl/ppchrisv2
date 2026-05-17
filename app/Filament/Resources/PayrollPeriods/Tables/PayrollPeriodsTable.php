<?php

namespace App\Filament\Resources\PayrollPeriods\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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

                IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean(),

                TextColumn::make('description')
                    ->limit(80)
                    ->wrap(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalDescription('This will also soft delete all D.T.R entries using this payroll period.'),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->modalDescription('This will permanently delete this payroll period and all D.T.R entries using it.'),
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
