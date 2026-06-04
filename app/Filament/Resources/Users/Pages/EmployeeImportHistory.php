<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use App\Models\User;
use App\Support\HrDatabaseNotification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class EmployeeImportHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = UserResource::class;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Employee Import History';

    public function table(Table $table): Table
    {
        return $table
            ->heading(false)
            ->query(fn (): Builder => Employee::query()
                ->whereNotNull('employee_import_batch_id')
                ->selectRaw('MIN(id) as id, employee_import_batch_id, MAX(employee_import_name) as employee_import_name, COUNT(*) as total, MAX(employee_imported_at) as imported_at, MAX(id) as latest_id')
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
                        ]))
                        ->visible(fn (Employee $record): bool => filled($record->employee_import_batch_id)),

                    DeleteAction::make('deleteBatch')
                        ->label('Delete Batch')
                        ->modalHeading(fn (Employee $record): string => "Delete batch {$record->employee_import_batch_id}")
                        ->modalDescription('This will delete the employee accounts and employee details created from this import batch.')
                        ->successNotificationTitle('Employee import batch deleted')
                        ->using(function (Employee $record): bool {
                            $batchId = $record->employee_import_batch_id;

                            if (blank($batchId)) {
                                return false;
                            }

                            $employeeQuery = Employee::query()
                                ->where('employee_import_batch_id', $batchId);

                            $employeeCount = (clone $employeeQuery)->count();
                            $userIds = (clone $employeeQuery)
                                ->whereNotNull('user_id')
                                ->pluck('user_id')
                                ->all();

                            User::query()
                                ->whereIn('id', $userIds)
                                ->get()
                                ->each
                                ->delete();

                            (clone $employeeQuery)
                                ->whereNull('user_id')
                                ->delete();

                            if ($employeeCount > 0) {
                                HrDatabaseNotification::send(
                                    title: 'Employee import batch deleted',
                                    body: "Batch {$batchId} ({$employeeCount} employees)",
                                    status: 'danger',
                                    icon: Heroicon::Trash,
                                );
                            }

                            return $employeeCount > 0;
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions')
                    ->color('primary'),
            ]);
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
                ->url(UserResource::getUrl()),
        ];
    }
}
