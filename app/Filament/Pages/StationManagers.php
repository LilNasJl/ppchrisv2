<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class StationManagers extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Station Managers';

    protected static ?string $navigationLabel = 'Station Managers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Employee::query()
                ->with(['user', 'branch'])
                ->where('is_station_manager', true)
                ->orderBy('lastname')
                ->orderBy('firstname'))
            ->heading('Assigned Station Managers')
            ->description('Employees assigned to manage attendance, D.T.R preview records, and change requests for specific stations.')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('company_id')
                    ->label('Company ID')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Employee Name')
                    ->searchable(['lastname', 'firstname', 'middlename'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('lastname', $direction)
                        ->orderBy('firstname', $direction))
                    ->weight('semibold'),

                TextColumn::make('branch.branch_name')
                    ->label('Home Branch')
                    ->placeholder('None')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('managed_stations')
                    ->label('Managed Stations')
                    ->getStateUsing(fn (Employee $record): string => $this->branchDisplayInline($record))
                    ->badge()
                    ->color('info')
                    ->wrap(),

                IconColumn::make('is_station_manager')
                    ->label('Station Manager')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon(Heroicon::Eye)
                        ->modalHeading(fn (Employee $record): string => 'Station Manager - '.$record->full_name)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->schema([
                            TextInput::make('full_name')->label('Name')->disabled()->dehydrated(false),
                            TextInput::make('company_id')->label('Company ID')->disabled()->dehydrated(false),
                            TextInput::make('home_branch')->label('Home Branch')->disabled()->dehydrated(false),
                            Textarea::make('managed_branches_text')
                                ->label('Assigned Stations')
                                ->rows(4)
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->fillForm(fn (Employee $record): array => [
                            'full_name' => $record->full_name,
                            'company_id' => $record->company_id,
                            'home_branch' => $record->branch?->branch_name ?? 'None',
                            'managed_branches_text' => $this->branchDisplayList($record),
                        ]),

                    Action::make('editAssignment')
                        ->label('Configure Stations')
                        ->icon(Heroicon::PencilSquare)
                        ->modalHeading(fn (Employee $record): string => 'Configure Stations for '.$record->full_name)
                        ->modalDescription('Select the station branches this employee is authorized to manage in their Self-Service portal.')
                        ->modalSubmitActionLabel('Save Stations')
                        ->schema([
                            Toggle::make('is_station_manager')
                                ->label('Active Station Manager Role')
                                ->default(true)
                                ->required(),

                            Repeater::make('managed_branches')
                                ->label('Assigned Station Branches')
                                ->schema([
                                    Select::make('branch_id')
                                        ->label('Station / Branch')
                                        ->options(fn (): array => Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all())
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                ])
                                ->addActionLabel('Add Assigned Station')
                                ->collapsible()
                                ->defaultItems(1),
                        ])
                        ->fillForm(fn (Employee $record): array => [
                            'is_station_manager' => (bool) $record->is_station_manager,
                            'managed_branches' => $this->assignmentFormRows($record),
                        ])
                        ->action(function (Employee $record, array $data): void {
                            DB::transaction(function () use ($record, $data): void {
                                $rows = is_array($data['managed_branches'] ?? null) ? $data['managed_branches'] : [];
                                $cleanManaged = [];

                                foreach ($rows as $row) {
                                    $branchId = isset($row['branch_id']) ? (int) $row['branch_id'] : 0;
                                    if ($branchId > 0) {
                                        $cleanManaged[] = [
                                            'branch_id' => $branchId,
                                        ];
                                    }
                                }

                                $record->is_station_manager = (bool) ($data['is_station_manager'] ?? true);
                                $record->managed_branches = $cleanManaged;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Station Manager configuration updated')
                                ->success()
                                ->send();
                        }),

                    Action::make('revokeRole')
                        ->label('Revoke Manager Role')
                        ->icon(Heroicon::XCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Revoke Station Manager Role')
                        ->modalDescription('This will remove the Station Management section from this employee\'s Self-Service portal.')
                        ->modalSubmitActionLabel('Revoke Role')
                        ->action(function (Employee $record): void {
                            $record->is_station_manager = false;
                            $record->save();

                            Notification::make()
                                ->title('Station Manager role revoked')
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignManager')
                ->label('Assign Station Manager')
                ->icon(Heroicon::Plus)
                ->modalHeading('Assign Employee as Station Manager')
                ->modalDescription('Authorize an existing employee to manage attendance and D.T.R for assigned station branches.')
                ->modalSubmitActionLabel('Assign Manager')
                ->schema([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->options(fn (): array => Employee::query()
                            ->activeEmployment()
                            ->where('is_station_manager', false)
                            ->orderBy('lastname')
                            ->orderBy('firstname')
                            ->get()
                            ->mapWithKeys(fn (Employee $emp): array => [
                                $emp->id => "{$emp->full_name} ({$emp->company_id})",
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Repeater::make('managed_branches')
                        ->label('Assigned Station Branches')
                        ->schema([
                            Select::make('branch_id')
                                ->label('Station / Branch')
                                ->options(fn (): array => Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->addActionLabel('Add Assigned Station')
                        ->defaultItems(1)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $employeeId = (int) $data['employee_id'];
                    $employee = Employee::query()->findOrFail($employeeId);

                    DB::transaction(function () use ($employee, $data): void {
                        $rows = is_array($data['managed_branches'] ?? null) ? $data['managed_branches'] : [];
                        $cleanManaged = [];

                        foreach ($rows as $row) {
                            $branchId = isset($row['branch_id']) ? (int) $row['branch_id'] : 0;
                            if ($branchId > 0) {
                                $cleanManaged[] = [
                                    'branch_id' => $branchId,
                                ];
                            }
                        }

                        $employee->is_station_manager = true;
                        $employee->managed_branches = $cleanManaged;
                        $employee->save();
                    });

                    Notification::make()
                        ->title('Employee assigned as Station Manager')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function assignmentFormRows(Employee $record): array
    {
        $rows = [];
        foreach ($record->branchAssignments() as $item) {
            $rows[] = [
                'branch_id' => $item['branch_id'] ?? null,
            ];
        }

        return $rows !== [] ? $rows : [['branch_id' => null]];
    }

    protected function branchDisplayInline(Employee $record): string
    {
        $branchIds = $record->assignedBranchIds();
        if ($branchIds === []) {
            return 'None assigned';
        }

        $names = Branch::query()->whereIn('id', $branchIds)->pluck('branch_name')->all();

        return implode(', ', $names);
    }

    protected function branchDisplayList(Employee $record): string
    {
        $branchIds = $record->assignedBranchIds();
        if ($branchIds === []) {
            return 'No branches assigned';
        }

        $branches = Branch::query()->whereIn('id', $branchIds)->pluck('branch_name', 'id')->all();
        $lines = [];

        foreach ($record->branchAssignments() as $assignment) {
            $bId = $assignment['branch_id'] ?? null;
            $bName = $branches[$bId] ?? "Branch #{$bId}";

            $lines[] = "- {$bName}";
        }

        return implode("\n", $lines);
    }
}
