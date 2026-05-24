<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DtrImport;
use App\Filament\Pages\DtrPeriodBranches;
use App\Filament\Pages\DtrViewer;
use App\Models\PayrollPeriod;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DtrPageTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(false)
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
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('dtrviewer')
                    ->label('D.T.R Viewer')
                    ->icon(Heroicon::QueueList)
                    ->url(DtrViewer::getUrl()),

                Action::make('importdtr')
                    ->label('D.T.R Importer')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(DtrImport::getUrl()),

            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('manageDtr')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->url(fn (PayrollPeriod $record): string => DtrPeriodBranches::getUrl([
                            'periodId' => $record->id,
                        ])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
