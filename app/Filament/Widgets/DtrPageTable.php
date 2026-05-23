<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DtrImport;
use App\Filament\Pages\DtrManage;
use App\Filament\Pages\DtrViewer;
use App\Models\Employee as ModelsEmployee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
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
            ->query(fn (): Builder => ModelsEmployee::query()
                ->with(['branch', 'department', 'designation', 'user'])
                ->activeEmployment()
                ->whereHas('user', fn ($query) => $query->where('role', 'employee'))
            )
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy('lastname')
                ->orderBy('middlename')
                ->orderBy('firstname'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('user.profile_photo_path')
                    ->label('Profile')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('fullname')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => trim($record->lastname.', '.(filled($record->middlename) ? $record->middlename.'. ' : '').$record->firstname)
                    )
                    // Pass the actual DB column names here
                    ->searchable(['lastname', 'middlename', 'firstname'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('lastname', $direction)
                        ->orderBy('middlename', $direction)
                        ->orderBy('firstname', $direction)),

                TextColumn::make('designation.title')
                    ->label('Designation')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(100),

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
                        ->label('Manage DTR')
                        ->icon(Heroicon::Cog6Tooth)
                        ->url(fn ($record) => DtrManage::getUrl([
                            'employeeId' => $record->id,
                            'branchId' => $record->branch_id,
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
