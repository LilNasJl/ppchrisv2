<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DtrImportBatchEntries;
use App\Filament\Pages\DtrImportUpload;
use App\Models\Dtr as ModelsImport;
use App\Support\HrDatabaseNotification;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DtrImportTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(false)
            ->query(fn (): Builder => ModelsImport::query()
                ->where('is_locked', 0)
                ->where('is_imported', 1)
                ->whereDoesntHave(
                    'payrollPeriod',
                    fn (Builder $query) => $query->where('is_locked', true)
                )
                ->selectRaw('MIN(id) as id, batch_id, MAX(import_name) as import_name, COUNT(*) as total, MAX(created_at) as imported_at, MAX(id) as latest_id')
                ->groupBy('batch_id')
                ->orderByDesc('imported_at')
                ->orderByDesc('latest_id')
            )
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('batch_id')
                    ->label('Batch ID')
                    ->badge(),

                TextColumn::make('import_name')
                    ->label('Import Name')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('total')
                    ->label('Total Records'),

                TextColumn::make('imported_at')
                    ->label('Date Imported')
                    ->date(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('importDtr')
                    ->label('Import D.T.R')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(DtrImportUpload::getUrl()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewBatch')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->url(fn (ModelsImport $record): string => DtrImportBatchEntries::getUrl([
                            'batchId' => $record->batch_id,
                        ]))
                        ->visible(fn (ModelsImport $record): bool => filled($record->batch_id)),

                    DeleteAction::make('deleteBatch')
                        ->label('Delete Batch')
                        ->modalHeading(fn (ModelsImport $record): string => "Delete batch {$record->batch_id}")
                        ->modalDescription('This will delete all D.T.R records with the same batch ID if none of them belongs to a locked payroll period.')
                        ->successNotificationTitle('D.T.R batch deleted')
                        ->using(function (ModelsImport $record): bool {
                            if ($this->batchHasLockedRecords($record->batch_id)) {
                                Notification::make()
                                    ->title('Batch cannot be deleted')
                                    ->body('Unlock the payroll period first before deleting this D.T.R batch.')
                                    ->danger()
                                    ->send();

                                return false;
                            }

                            $deleted = ModelsImport::query()
                                ->where('batch_id', $record->batch_id)
                                ->forceDelete();

                            if ($deleted > 0) {
                                HrDatabaseNotification::send(
                                    title: 'D.T.R batch permanently deleted',
                                    body: "Batch {$record->batch_id} ({$deleted} entries)",
                                    status: 'danger',
                                    icon: Heroicon::Trash,
                                );
                            }

                            return $deleted > 0;
                        }),
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

    protected function batchHasLockedRecords(?string $batchId): bool
    {
        if (blank($batchId)) {
            return true;
        }

        return ModelsImport::query()
            ->where('batch_id', $batchId)
            ->where(function (Builder $query): void {
                $query
                    ->where('is_locked', true)
                    ->orWhereHas(
                        'payrollPeriod',
                        fn (Builder $query) => $query->where('is_locked', true)
                    );
            })
            ->exists();
    }
}
