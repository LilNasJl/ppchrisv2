<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\BranchHolidayCalendar;
use App\Models\Branch;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BranchHolidayBranchTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Branches';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Branch::query()->orderBy('branch_name'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('branch_address')
                    ->label('Address')
                    ->searchable()
                    ->wrap(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('manageBranchHoliday')
                        ->label('Manage Holidays')
                        ->icon(Heroicon::CalendarDays)
                        ->url(fn (Branch $record): string => BranchHolidayCalendar::getUrl([
                            'branchId' => $record->publicKey(),
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }
}
