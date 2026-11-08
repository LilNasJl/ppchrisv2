<?php

namespace App\Filament\Resources\Departments\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Department Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('acronym')
                    ->label('Acronym')
                    ->badge()
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('employees_count')
                    ->label('Active Staff')
                    ->counts('employees')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "{$state} staff" : 'Vacant')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('None provided')
                    ->color('gray')
                    ->wrap()
                    ->limit(100),

            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    // DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical') // The 3 dots icon
                    ->tooltip('Actions')
                    ->color('primary'),
            ]);
    }
}
