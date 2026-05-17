<?php

namespace App\Filament\Resources\Designations\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Foundation\Console\ViewCacheCommand;

class DesignationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->sortable()
                    ->searchable()
                    ->rowIndex(),
                TextColumn::make('title')
                    ->label('Designation Title')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('specification')
                    ->wrap()
                    ->limit(100)
                    ->label('Specification'),

                TextColumn::make('description')
                    ->wrap()
                    ->limit(100)
                    ->label('Description'),

            ])
            ->filters([
                TrashedFilter::make()
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
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                //     ForceDeleteBulkAction::make(),
                //     RestoreBulkAction::make(),
                // ]),
            ]);
    }
}
