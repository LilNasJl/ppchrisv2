<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Branch as ModelsBranch;
use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class BranchTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    #[On('refreshBranchTable')]
    public function refresh(): void
    {
        // just re-renders the component
    }

    protected function getBranchViewSchema(): array
    {
        return [
            Section::make('Branch Details')
                ->schema([
                    TextInput::make('branch_name')
                        ->label('Branch Name')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('mobile_no')
                        ->label('Mobile No.')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('scheduling')
                        ->label('Schedule Type')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('sic_name')
                        ->label('Station In Charge')
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('branch_address')
                        ->label('Address')
                        ->rows(3)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Schedules')
                ->schema([
                    Fieldset::make('Regular Schedule')
                        ->schema([
                            TimePicker::make('reg_sched_start')
                                ->label('Regular Start')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('reg_sched_end')
                                ->label('Regular End')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->columns(2),

                    Fieldset::make('Shifting Schedules')
                        ->schema([
                            TimePicker::make('shift1_start')
                                ->label('Shift 1 Start')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('shift1_end')
                                ->label('Shift 1 End')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('shift2_start')
                                ->label('Shift 2 Start')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('shift2_end')
                                ->label('Shift 2 End')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('shift3_start')
                                ->label('Shift 3 Start')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('shift3_end')
                                ->label('Shift 3 End')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->columns(2),

                    Fieldset::make('Broken Shift Schedules')
                        ->schema([
                            TimePicker::make('broken_shift1_start')
                                ->label('Broken Shift 1 Start')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('broken_shift1_end')
                                ->label('Broken Shift 1 End')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('broken_shift2_start')
                                ->label('Broken Shift 2 Start')
                                ->disabled()
                                ->dehydrated(false),

                            TimePicker::make('broken_shift2_end')
                                ->label('Broken Shift 2 End')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->columns(2),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(false)
            ->query(fn (): Builder => ModelsBranch::query())
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('branch_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('work_schedule')
                    ->label('Schedule')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn ($record) => $record->opening_hrs && $record->closed_hrs
                            ? "{$record->opening_hrs} - {$record->closed_hrs}"
                            : '—'
                    )
                    ->searchable(),

                TextColumn::make('is_24hrs')
                    ->label('24 Hours')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? '24 Hours' : 'N/A')
                    ->colors([
                        'warning' => fn ($state) => $state,
                        'info' => fn ($state) => ! $state,
                    ])
                    ->icon(fn ($state) => $state ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                    ->searchable(),

                TextColumn::make('scheduling')
                    ->label('Schedule type')
                    ->limit(100)
                    ->badge()
                    ->wrap(),

                TextColumn::make('full_name')
                    ->label('Station In Charge (SIC)')
                    ->getStateUsing(fn ($record) => $record->employee
                            ? "{$record->employee->lastname}, {$record->employee->middlename} {$record->employee->firstname}"
                            : '—'
                    )
                    ->searchable(),

                // TextColumn::make('branch_address')
                //     ->label('Address')
                //     ->limit(100)
                //     ->wrap(),

            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([

            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View')
                        ->modalHeading(fn (ModelsBranch $record): string => 'View Branch: '.$record->branch_name)
                        ->fillForm(fn (ModelsBranch $record): array => [
                            'branch_name' => $record->branch_name,
                            'mobile_no' => $record->mobile_no,
                            'scheduling' => $record->scheduling,
                            'sic_name' => $record->employee
                                ? trim($record->employee->lastname.', '.$record->employee->middlename.' '.$record->employee->firstname)
                                : 'N/A',
                            'branch_address' => $record->branch_address,
                            'reg_sched_start' => $record->reg_sched_start,
                            'reg_sched_end' => $record->reg_sched_end,
                            'shift1_start' => $record->shift1_start,
                            'shift1_end' => $record->shift1_end,
                            'shift2_start' => $record->shift2_start,
                            'shift2_end' => $record->shift2_end,
                            'shift3_start' => $record->shift3_start,
                            'shift3_end' => $record->shift3_end,
                            'broken_shift1_start' => $record->broken_shift1_start,
                            'broken_shift1_end' => $record->broken_shift1_end,
                            'broken_shift2_start' => $record->broken_shift2_start,
                            'broken_shift2_end' => $record->broken_shift2_end,
                        ])
                        ->schema($this->getBranchViewSchema()),

                    DeleteAction::make()
                        ->label('Delete')
                        ->modalHeading('Delete Branch')
                        ->modalDescription('This will move the branch to trash. You can restore it from the trashed filter.')
                        ->successNotificationTitle('Branch deleted successfully'),

                    RestoreAction::make()
                        ->label('Restore')
                        ->successNotificationTitle('Branch restored successfully'),

                    // Edit for Regular Schedule
                    EditAction::make('edit_regular')
                        ->label('Edit')
                        ->visible(fn (ModelsBranch $record) => $record->scheduling === 'regular')
                        ->fillForm(fn (ModelsBranch $record) => [
                            'branch_name' => $record->branch_name,
                            'mobile_no' => $record->mobile_no,
                            'branch_address' => $record->branch_address,
                            'no_of_shifts' => $record->no_of_shifts,
                            'is_24hrs' => $record->is_24hrs,
                            'has_broken_time' => $record->has_broken_time,
                            'scheduling' => $record->scheduling,
                            'reg_sched_start' => $record->reg_sched_start,
                            'reg_sched_end' => $record->reg_sched_end,
                            'opening_hrs' => $record->opening_hrs,
                            'closed_hrs' => $record->closed_hrs,
                        ])
                        ->schema([
                            Section::make()
                                ->schema([
                                    Group::make()
                                        ->schema([

                                            TextInput::make('branch_name')
                                                ->label('Branch Name')
                                                ->unique(
                                                    table: Branch::class,
                                                    column: 'branch_name',
                                                    ignoreRecord: true
                                                )
                                                ->maxLength(255)
                                                ->required(),

                                            TextInput::make('mobile_no')
                                                ->label('Mobile No.')
                                                ->mask('9999-999-9999')
                                                ->placeholder('09-1234-56789')
                                                ->rules(['regex:/^09/'])
                                                ->validationMessages([
                                                    'regex' => 'The mobile number must start with 09.',
                                                ]),
                                        ])->columns(2),

                                    Textarea::make('branch_address')
                                        ->label('Address')
                                        ->rows(3)
                                        ->required(),

                                    Hidden::make('no_of_shifts')->default(0),
                                    Hidden::make('is_24hrs')->default(0),
                                    Hidden::make('has_broken_time')->default(0),
                                    Hidden::make('scheduling')->default('regular'),

                                    Group::make()
                                        ->schema([
                                            TimePicker::make('reg_sched_start')
                                                ->label('Schedule Start')
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set, $state) => $set('opening_hrs', $state)),

                                            TimePicker::make('reg_sched_end')
                                                ->label('Schedule End')
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set, $state) => $set('closed_hrs', $state)),

                                            TimePicker::make('opening_hrs')->visible(false)->dehydrated(true),
                                            TimePicker::make('closed_hrs')->visible(false)->dehydrated(true),
                                        ])->columns(2),
                                ]),
                        ])
                        ->action(function (ModelsBranch $record, array $data) {
                            $record->update([
                                'branch_name' => $data['branch_name'],
                                'branch_address' => $data['branch_address'],
                                'mobile_no' => $data['mobile_no'],
                                'no_of_shifts' => $data['no_of_shifts'],
                                'is_24hrs' => $data['is_24hrs'],
                                'has_broken_time' => $data['has_broken_time'],
                                'scheduling' => $data['scheduling'],
                                'reg_sched_start' => $data['reg_sched_start'],
                                'reg_sched_end' => $data['reg_sched_end'],
                                'opening_hrs' => $data['opening_hrs'] ?? $data['reg_sched_start'],
                                'closed_hrs' => $data['closed_hrs'] ?? $data['reg_sched_end'],
                            ]);

                            Notification::make()
                                ->title('Branch updated successfully')
                                ->success()
                                ->send();
                        })
                        ->extraModalFooterActions([
                            Action::make('delete')
                                ->label('Delete Branch')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Delete Branch')
                                ->modalDescription('Are you sure you want to delete this branch? This cannot be undone.')
                                ->modalSubmitActionLabel('Yes, delete it')
                                ->action(function (ModelsBranch $record) {
                                    $record->delete();

                                    Notification::make()
                                        ->title('Branch deleted successfully')
                                        ->danger()
                                        ->send();
                                })
                                ->cancelParentActions(), // 🔥 closes the edit modal after delete
                        ])
                        ->modalHeading('Edit Branch: Regular Schedule')
                        ->modalSubmitActionLabel('Update'),

                    // Edit for Regular & Shifting Schedule
                    EditAction::make('edit_regular_shift')
                        ->label('Edit')
                        ->visible(fn (ModelsBranch $record) => $record->scheduling === 'regular & shifting')
                        ->fillForm(fn (ModelsBranch $record) => [
                            'branch_name' => $record->branch_name,
                            'mobile_no' => $record->mobile_no,
                            'branch_address' => $record->branch_address,
                            'employee_id' => $record->employee_id,
                            'no_of_shifts' => $record->no_of_shifts,
                            'is_24hrs' => $record->is_24hrs,
                            'has_broken_time' => $record->has_broken_time,
                            'scheduling' => $record->scheduling,
                            'reg_sched_start' => $record->reg_sched_start,
                            'reg_sched_end' => $record->reg_sched_end,
                            'shift1_start' => $record->broken_shift1_start,
                            'shift1_end' => $record->shift1_end,
                            'shift2_start' => $record->shift2_start,
                            'shift2_end' => $record->shift2_end,
                            'broken_shift1_start' => $record->broken_shift1_start,
                            'broken_shift1_end' => $record->broken_shift1_end,
                            'broken_shift2_start' => $record->broken_shift2_start,
                            'broken_shift2_end' => $record->broken_shift2_end,
                            'opening_hrs' => $record->opening_hrs,
                            'closed_hrs' => $record->closed_hrs,
                        ])
                        ->schema([
                            Section::make()
                                ->schema([
                                    Group::make()
                                        ->schema([
                                            TextInput::make('branch_name')
                                                ->label('Branch Name')
                                                ->unique(
                                                    table: Branch::class,
                                                    column: 'branch_name',
                                                    ignoreRecord: true
                                                )
                                                ->maxLength(255)
                                                ->required(),

                                            Select::make('employee_id')
                                                ->label('Select SIC Employee')
                                                ->options(function () {
                                                    return Employee::query()
                                                        ->join('users', 'users.id', '=', 'employees.user_id')
                                                        ->where('users.role', 'employee')
                                                        ->pluck('users.name', 'employees.id');
                                                })
                                                ->default(fn (ModelsBranch $record) => $record->employee_id)
                                                ->searchable()
                                                ->preload(),

                                            TextInput::make('mobile_no')
                                                ->label('Mobile No.')
                                                ->mask('9999-999-9999')
                                                ->placeholder('09-1234-56789')
                                                ->rules(['regex:/^09/'])
                                                ->validationMessages([
                                                    'regex' => 'The mobile number must start with 09.',
                                                ]),
                                        ])->columns(2),

                                    Textarea::make('branch_address')
                                        ->label('Address')
                                        ->rows(3)
                                        ->required(),

                                    Hidden::make('no_of_shifts')->default(2),
                                    Hidden::make('is_24hrs')->default(0),
                                    Hidden::make('has_broken_time')->default(1),
                                    Hidden::make('scheduling')->default('regular & shifting'),
                                    Group::make()
                                        ->schema([
                                            Fieldset::make('Regular Schedule')
                                                ->schema([
                                                    TimePicker::make('reg_sched_start')
                                                        ->label('Regular Schedule Start')
                                                        ->required()
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, $state) {
                                                            $set('opening_hrs', $state);
                                                        }),

                                                    TimePicker::make('reg_sched_end')
                                                        ->label('Regular Schedule End')
                                                        ->required()
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, $state) {
                                                            $set('closed_hrs', $state);
                                                        }),
                                                ])->columnSpanFull(),

                                        ])->columns(2),
                                    Fieldset::make('Shifting Schedules')
                                        ->schema([
                                            TimePicker::make('shift1_start')
                                                ->label('1st Shift Start')
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set, $state) => $set('opening_hrs', $state)),

                                            TimePicker::make('shift1_end')
                                                ->label('1st Shift End')
                                                ->required(),

                                            TimePicker::make('shift2_start')
                                                ->label('2nd Shift Start')
                                                ->required(),

                                            TimePicker::make('shift2_end')
                                                ->label('2nd Shift End')
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set, $state) => $set('closed_hrs', $state)),

                                            TimePicker::make('opening_hrs')->visible(false)->dehydrated(true),
                                            TimePicker::make('closed_hrs')->visible(false)->dehydrated(true),
                                        ])->columns(2),

                                    Fieldset::make('Broken Shift Schedules')
                                        ->schema([
                                            TimePicker::make('broken_shift1_start')
                                                ->label('1st Shift Broken Start')
                                                ->required(),

                                            TimePicker::make('broken_shift1_end')
                                                ->label('1st Shift Broken End')
                                                ->required(),

                                            TimePicker::make('broken_shift2_start')
                                                ->label('2nd Shift Broken Start')
                                                ->required(),

                                            TimePicker::make('broken_shift2_end')
                                                ->label('2nd Shift Broken End')
                                                ->required(),

                                        ])->columns(2),

                                ]),
                        ])
                        ->action(function (ModelsBranch $record, array $data) {
                            $record->update([
                                'branch_name' => $data['branch_name'],
                                'branch_address' => $data['branch_address'],
                                'employee_id' => $data['employee_id'],
                                'mobile_no' => $data['mobile_no'],
                                'no_of_shifts' => $data['no_of_shifts'],
                                'is_24hrs' => $data['is_24hrs'],
                                'has_broken_time' => $data['has_broken_time'],
                                'scheduling' => $data['scheduling'],
                                'reg_sched_start' => $data['reg_sched_start'],
                                'reg_sched_end' => $data['reg_sched_end'],
                                'shift1_start' => $data['shift1_start'],
                                'shift1_end' => $data['shift1_end'],
                                'shift2_start' => $data['shift2_start'],
                                'shift2_end' => $data['shift2_end'],
                                'broken_shift1_start' => $data['broken_shift1_start'],
                                'broken_shift1_end' => $data['broken_shift1_end'],
                                'broken_shift2_start' => $data['broken_shift2_start'],
                                'broken_shift2_end' => $data['broken_shift2_end'],
                                'opening_hrs' => $data['opening_hrs'] ?? $data['shift1_start'],
                                'closed_hrs' => $data['closed_hrs'] ?? $data['shift2_end'],
                            ]);

                            Notification::make()
                                ->title('Branch updated successfully')
                                ->success()
                                ->send();
                        })
                        ->extraModalFooterActions([
                            Action::make('delete')
                                ->label('Delete Branch')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Delete Branch')
                                ->modalDescription('Are you sure you want to delete this branch? This cannot be undone.')
                                ->modalSubmitActionLabel('Yes, delete it')
                                ->action(function (ModelsBranch $record) {
                                    $record->delete();

                                    Notification::make()
                                        ->title('Branch deleted successfully')
                                        ->danger()
                                        ->send();
                                })
                                ->cancelParentActions(),
                        ])
                        ->modalHeading('Edit Branch: Regular & Shifting Schedule')
                        ->modalSubmitActionLabel('Update'),

                    // Edit for Regular & 24hrs Shifting Schedule
                    EditAction::make('edit_regular_24hrs')
                        ->label('Edit')
                        ->visible(fn (ModelsBranch $record) => $record->scheduling === 'regular & 24hrs shifting')
                        ->fillForm(fn (ModelsBranch $record) => [
                            'branch_name' => $record->branch_name,
                            'mobile_no' => $record->mobile_no,
                            'branch_address' => $record->branch_address,
                            'employee_id' => $record->employee_id,
                            'no_of_shifts' => $record->no_of_shifts,
                            'is_24hrs' => $record->is_24hrs,
                            'has_broken_time' => $record->has_broken_time,
                            'scheduling' => $record->scheduling,
                            'shift1_start' => $record->shift1_start,
                            'shift1_end' => $record->shift1_end,
                            'shift2_start' => $record->shift2_start,
                            'shift2_end' => $record->shift2_end,
                            'shift3_start' => $record->shift3_start,
                            'shift3_end' => $record->shift3_end,
                            'broken_shift1_start' => $record->broken_shift1_start,
                            'broken_shift1_end' => $record->broken_shift1_end,
                            'broken_shift2_start' => $record->broken_shift2_start,
                            'broken_shift2_end' => $record->broken_shift2_end,
                            'opening_hrs' => $record->opening_hrs,
                            'closed_hrs' => $record->closed_hrs,
                        ])
                        ->schema([
                            Section::make()
                                ->schema([
                                    Group::make()
                                        ->schema([
                                            TextInput::make('branch_name')
                                                ->label('Branch Name')
                                                ->unique(
                                                    table: Branch::class,
                                                    column: 'branch_name',
                                                    ignoreRecord: true
                                                )
                                                ->maxLength(255)
                                                ->required(),

                                            Select::make('employee_id')
                                                ->label('Select SIC Employee')
                                                ->options(function () {
                                                    return Employee::query()
                                                        ->join('users', 'users.id', '=', 'employees.user_id')
                                                        ->where('users.role', 'employee')
                                                        ->pluck('users.name', 'employees.id');
                                                })
                                                ->default(fn (ModelsBranch $record) => $record->employee_id) // 🔥 set default value
                                                ->searchable()
                                                ->preload(),

                                            TextInput::make('mobile_no')
                                                ->label('Mobile No.')
                                                ->mask('9999-999-9999')
                                                ->placeholder('09-1234-56789')
                                                ->rules(['regex:/^09/'])
                                                ->validationMessages([
                                                    'regex' => 'The mobile number must start with 09.',
                                                ]),
                                        ])->columns(2),

                                    Textarea::make('branch_address')
                                        ->label('Address')
                                        ->rows(3)
                                        ->required(),

                                    Hidden::make('no_of_shifts')->default(2),
                                    Hidden::make('is_24hrs')->default(0),
                                    Hidden::make('has_broken_time')->default(1),
                                    Hidden::make('scheduling')->default('regular & shifting'),

                                    Group::make()
                                        ->schema([
                                            Fieldset::make('Regular Schedule')
                                                ->schema([
                                                    TimePicker::make('reg_sched_start')
                                                        ->label('Regular Schedule Start')
                                                        ->required()
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, $state) {
                                                            $set('opening_hrs', $state);
                                                        }),

                                                    TimePicker::make('reg_sched_end')
                                                        ->label('Regular Schedule End')
                                                        ->required()
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, $state) {
                                                            $set('closed_hrs', $state);
                                                        }),
                                                ])->columnSpanFull(),

                                        ])->columns(2),

                                    Fieldset::make('Shifting Schedules')
                                        ->schema([
                                            TimePicker::make('shift1_start')
                                                ->label('1st Shift Start')
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set, $state) => $set('opening_hrs', $state)),

                                            TimePicker::make('shift1_end')
                                                ->label('1st Shift End')
                                                ->required(),

                                            TimePicker::make('shift2_start')
                                                ->label('2nd Shift Start')
                                                ->required(),

                                            TimePicker::make('shift2_end')
                                                ->label('2nd Shift End')
                                                ->required(),

                                            TimePicker::make('shift3_start')
                                                ->label('3rd Shift Start')
                                                ->required(),

                                            TimePicker::make('shift3_end')
                                                ->label('3rd Shift End')
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set, $state) => $set('closed_hrs', $state)),

                                            TimePicker::make('opening_hrs')->visible(false)->dehydrated(true),
                                            TimePicker::make('closed_hrs')->visible(false)->dehydrated(true),
                                        ])->columns(2),

                                    Fieldset::make('Broken Shift Schedules')
                                        ->schema([
                                            TimePicker::make('broken_shift1_start')
                                                ->label('1st Shift Broken Start')
                                                ->required(),

                                            TimePicker::make('broken_shift1_end')
                                                ->label('1st Shift Broken End')
                                                ->required(),

                                            TimePicker::make('broken_shift2_start')
                                                ->label('2nd Shift Broken Start')
                                                ->required(),

                                            TimePicker::make('broken_shift2_end')
                                                ->label('2nd Shift Broken End')
                                                ->required(),

                                        ])->columns(2),

                                ]),
                        ])
                        ->action(function (ModelsBranch $record, array $data) {
                            $record->update([
                                'branch_name' => $data['branch_name'],
                                'branch_address' => $data['branch_address'],
                                'employee_id' => $data['employee_id'],
                                'mobile_no' => $data['mobile_no'],
                                'no_of_shifts' => $data['no_of_shifts'],
                                'is_24hrs' => $data['is_24hrs'],
                                'has_broken_time' => $data['has_broken_time'],
                                'scheduling' => $data['scheduling'],
                                'reg_sched_start' => $data['reg_sched_start'],
                                'reg_sched_end' => $data['reg_sched_end'],
                                'shift1_start' => $data['shift1_start'],
                                'shift1_end' => $data['shift1_end'],
                                'shift2_start' => $data['shift2_start'],
                                'shift2_end' => $data['shift2_end'],
                                'shift3_start' => $data['shift3_start'],
                                'shift3_end' => $data['shift3_end'],
                                'broken_shift1_start' => $data['broken_shift1_start'],
                                'broken_shift1_end' => $data['broken_shift1_end'],
                                'broken_shift2_start' => $data['broken_shift2_start'],
                                'broken_shift2_end' => $data['broken_shift2_end'],
                                'opening_hrs' => $data['opening_hrs'] ?? $data['shift1_start'],
                                'closed_hrs' => $data['closed_hrs'] ?? $data['shift2_end'],
                            ]);

                            Notification::make()
                                ->title('Branch updated successfully')
                                ->success()
                                ->send();
                        })
                        ->extraModalFooterActions([
                            Action::make('delete')
                                ->label('Delete Branch')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Delete Branch')
                                ->modalDescription('Are you sure you want to delete this branch? This cannot be undone.')
                                ->modalSubmitActionLabel('Yes, delete it')
                                ->action(function (ModelsBranch $record) {
                                    $record->delete();

                                    Notification::make()
                                        ->title('Branch deleted successfully')
                                        ->danger()
                                        ->send();
                                })
                                ->cancelParentActions(),
                        ])
                        ->modalHeading('Edit Branch: Regular & 24hrs Shifting Schedule')
                        ->modalSubmitActionLabel('Update'),

                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
