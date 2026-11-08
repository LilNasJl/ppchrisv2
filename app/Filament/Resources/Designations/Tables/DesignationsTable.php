<?php

namespace App\Filament\Resources\Designations\Tables;

use App\Models\Designation;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DesignationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->label('Designation Title')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employees_count')
                    ->counts('employees')
                    ->label('Active Staff')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state >= 20 => 'success',
                        $state >= 5 => 'info',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? '0 staff' : "{$state} staff"),

                TextColumn::make('specification')
                    ->label('Specification')
                    ->wrap()
                    ->limit(90)
                    ->placeholder('None specified')
                    ->tooltip(fn (Designation $record): ?string => $record->specification),

                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(90)
                    ->placeholder('None provided')
                    ->tooltip(fn (Designation $record): ?string => $record->description),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('staffing')
                    ->label('Staff Allocation')
                    ->options([
                        'assigned' => 'Assigned (Has Staff)',
                        'vacant' => 'Vacant (0 Staff)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'assigned' => $query->has('employees'),
                            'vacant' => $query->doesntHave('employees'),
                            default => $query,
                        };
                    }),

                TrashedFilter::make(),
            ])
            ->emptyStateHeading('No designations found')
            ->emptyStateDescription('Create a new designation to begin organizing company roles.')
            ->emptyStateIcon('heroicon-o-briefcase')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')
                    ->color('primary'),
            ]);
    }
}
