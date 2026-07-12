<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\EmployeeDetails;
use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use App\Models\User;
use App\Support\HrDatabaseNotification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Override;

class EmployeeImportHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = UserResource::class;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Employee Import History';

    public ?string $returnUrl = null;

    public function mount(): void
    {
        $this->returnUrl = $this->normalizeReturnUrl(request()->query('returnUrl'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(false)
            ->query(fn (): Builder => Employee::query()
                ->withTrashed()
                ->whereNotNull('employee_import_batch_id')
                ->selectRaw('MIN(id) as id, employee_import_batch_id, MAX(employee_import_name) as employee_import_name, COUNT(*) as total, SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as active_total, SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted_total, MAX(employee_imported_at) as imported_at, MAX(deleted_at) as deleted_at, MAX(id) as latest_id')
                ->groupBy('employee_import_batch_id')
                ->orderByDesc('imported_at')
                ->orderByDesc('latest_id'))
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee_import_batch_id')
                    ->label('Batch ID')
                    ->badge()
                    ->searchable(),

                TextColumn::make('employee_import_name')
                    ->label('Import Name')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('total')
                    ->label('Total Employees'),

                TextColumn::make('active_total')
                    ->label('Active'),

                TextColumn::make('deleted_total')
                    ->label('Deleted'),

                TextColumn::make('batch_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Employee $record): string {
                        $active = (int) $record->active_total;
                        $deleted = (int) $record->deleted_total;

                        if ($deleted > 0 && $active === 0) {
                            return 'Deleted';
                        }

                        if ($deleted > 0) {
                            return 'Partially Deleted';
                        }

                        return 'Active';
                    })
                    ->color(function (Employee $record): string {
                        $active = (int) $record->active_total;
                        $deleted = (int) $record->deleted_total;

                        if ($deleted > 0 && $active === 0) {
                            return 'danger';
                        }

                        if ($deleted > 0) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                TextColumn::make('imported_at')
                    ->label('Date Imported')
                    ->date(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewBatch')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->url(fn (Employee $record): string => UserResource::getUrl('import-batch', [
                            'batchId' => $record->employee_import_batch_id,
                            'returnUrl' => $this->getReturnUrl(),
                        ]))
                        ->visible(fn (Employee $record): bool => filled($record->employee_import_batch_id)),

                    Action::make('restoreBatch')
                        ->label('Restore Batch')
                        ->icon(Heroicon::ArrowPath)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Employee $record): string => "Restore batch {$record->employee_import_batch_id}")
                        ->modalDescription('This will restore the employee accounts and employee details in this import batch.')
                        ->successNotificationTitle('Employee import batch restored')
                        ->visible(fn (Employee $record): bool => (int) $record->deleted_total > 0)
                        ->action(function (Employee $record): void {
                            $count = $this->restoreImportBatch($record->employee_import_batch_id);

                            if ($count > 0) {
                                HrDatabaseNotification::send(
                                    title: 'Employee import batch restored',
                                    body: "Batch {$record->employee_import_batch_id} ({$count} employees)",
                                    status: 'success',
                                    icon: Heroicon::ArrowPath,
                                );
                            }
                        }),

                    Action::make('deleteBatch')
                        ->label('Delete Batch')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Employee $record): string => "Delete batch {$record->employee_import_batch_id}")
                        ->modalDescription('This will delete the employee accounts and employee details created from this import batch.')
                        ->successNotificationTitle('Employee import batch deleted')
                        ->visible(fn (Employee $record): bool => (int) $record->active_total > 0)
                        ->action(function (Employee $record): void {
                            $count = $this->deleteImportBatch($record->employee_import_batch_id);

                            if ($count > 0) {
                                HrDatabaseNotification::send(
                                    title: 'Employee import batch deleted',
                                    body: "Batch {$record->employee_import_batch_id} ({$count} employees)",
                                    status: 'danger',
                                    icon: Heroicon::Trash,
                                );
                            }
                        }),

                    Action::make('forceDeleteBatch')
                        ->label('Force Delete Batch')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Employee $record): string => "Force delete batch {$record->employee_import_batch_id}")
                        ->modalDescription('This will permanently delete the employee accounts and employee details in this import batch. This cannot be undone.')
                        ->modalSubmitActionLabel('Force Delete')
                        ->successNotificationTitle('Employee import batch permanently deleted')
                        ->action(function (Employee $record): void {
                            $count = $this->deleteImportBatch($record->employee_import_batch_id, force: true);

                            if ($count > 0) {
                                HrDatabaseNotification::send(
                                    title: 'Employee import batch permanently deleted',
                                    body: "Batch {$record->employee_import_batch_id} ({$count} employees)",
                                    status: 'danger',
                                    icon: Heroicon::Trash,
                                );
                            }
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions')
                    ->color('primary'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('restoreBatches')
                        ->label('Restore')
                        ->icon(Heroicon::ArrowPath)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Restore selected import batches')
                        ->modalDescription('This will restore employee accounts and employee details in the selected import batches.')
                        ->action(fn (Collection $records): int => $this->restoreSelectedImportBatches($records)),

                    BulkAction::make('deleteBatches')
                        ->label('Delete')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected import batches')
                        ->modalDescription('This will delete employee accounts and employee details in the selected import batches.')
                        ->action(fn (Collection $records): int => $this->deleteSelectedImportBatches($records)),

                    BulkAction::make('forceDeleteBatches')
                        ->label('Force Delete')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Force delete selected import batches')
                        ->modalDescription('This will permanently delete employee accounts and employee details in the selected import batches. This cannot be undone.')
                        ->modalSubmitActionLabel('Force Delete')
                        ->action(fn (Collection $records): int => $this->deleteSelectedImportBatches($records, force: true)),
                ]),
            ]);
    }

    protected function getReturnUrl(): string
    {
        return $this->returnUrl ?: EmployeeDetails::getUrl();
    }

    protected function normalizeReturnUrl(mixed $url): ?string
    {
        if (! is_string($url) || blank($url)) {
            return null;
        }

        $appUrl = url('/');

        return str_starts_with($url, $appUrl) || str_starts_with($url, '/')
            ? $url
            : null;
    }

    protected function restoreSelectedImportBatches(Collection $records): int
    {
        $count = 0;

        foreach ($this->batchIdsFromRecords($records) as $batchId) {
            $count += $this->restoreImportBatch($batchId);
        }

        if ($count > 0) {
            HrDatabaseNotification::send(
                title: 'Employee import batches restored',
                body: "{$count} employees restored from selected batches",
                status: 'success',
                icon: Heroicon::ArrowPath,
            );
        }

        return $count;
    }

    protected function deleteSelectedImportBatches(Collection $records, bool $force = false): int
    {
        $count = 0;

        foreach ($this->batchIdsFromRecords($records) as $batchId) {
            $count += $this->deleteImportBatch($batchId, $force);
        }

        if ($count > 0) {
            HrDatabaseNotification::send(
                title: $force ? 'Employee import batches permanently deleted' : 'Employee import batches deleted',
                body: "{$count} employees affected from selected batches",
                status: 'danger',
                icon: Heroicon::Trash,
            );
        }

        return $count;
    }

    /**
     * @return array<int, string>
     */
    protected function batchIdsFromRecords(Collection $records): array
    {
        return $records
            ->pluck('employee_import_batch_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function restoreImportBatch(?string $batchId): int
    {
        if (blank($batchId)) {
            return 0;
        }

        $employeeQuery = Employee::withTrashed()
            ->where('employee_import_batch_id', $batchId);

        $employeeCount = (clone $employeeQuery)
            ->whereNotNull('deleted_at')
            ->count();

        $userIds = (clone $employeeQuery)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();

        User::withTrashed()
            ->whereIn('id', $userIds)
            ->get()
            ->each
            ->restore();

        (clone $employeeQuery)
            ->whereNull('user_id')
            ->restore();

        return $employeeCount;
    }

    protected function deleteImportBatch(?string $batchId, bool $force = false): int
    {
        if (blank($batchId)) {
            return 0;
        }

        $employeeQuery = $force
            ? Employee::withTrashed()->where('employee_import_batch_id', $batchId)
            : Employee::query()->where('employee_import_batch_id', $batchId);

        $employeeCount = (clone $employeeQuery)->count();
        $userIds = (clone $employeeQuery)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();

        if ($force) {
            User::withTrashed()
                ->whereIn('id', $userIds)
                ->get()
                ->each
                ->forceDelete();

            (clone $employeeQuery)
                ->whereNull('user_id')
                ->forceDelete();

            return $employeeCount;
        }

        User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->each
            ->delete();

        (clone $employeeQuery)
            ->whereNull('user_id')
            ->delete();

        return $employeeCount;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => $this->getReturnUrl()),
        ];
    }
}
