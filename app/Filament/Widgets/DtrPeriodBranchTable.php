<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DtrBranchEmployees;
use App\Models\Branch;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DtrPeriodBranchTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Branches';

    public ?int $periodId = null;

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
                    ->sortable()
                    ->wrap(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewEmployees')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->url(fn (Branch $record): string => DtrBranchEmployees::getUrl([
                            'periodId' => $this->periodId,
                            'branchId' => $record->id,
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }
}
