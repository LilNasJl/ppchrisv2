<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BranchTable;
use App\Models\Branch as ModelsBranch;
use App\Models\Employee;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group as ComponentsGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Branch extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.branch';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Organizational Set Up';

    protected static ?string $navigationLabel = 'Branch';

    protected static ?int $navigationSort = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                // Regular Schedule Branch
                Action::make('regular')
                    ->label('Regular Schedule')
                    ->tooltip('Non-rotational, fixed working hours')
                    ->icon(Heroicon::PlusCircle)
                    ->schema([
                        Section::make()
                            ->schema([
                                ComponentsGroup::make()
                                    ->schema([
                                        TextInput::make('branch_name')
                                            ->label('Branch Name')
                                            ->maxLength('255')
                                            ->placeholder('e.g., Tagum Station')
                                            ->unique(
                                                table: ModelsBranch::class,
                                                column: 'branch_name',
                                            )
                                            ->required(),

                                        // Select::make('employee_id')
                                        //     ->label('Select SIC Employee')
                                        //     ->options(function () {
                                        //         return \App\Models\Employee::query()
                                        //             ->join('users', 'users.id', '=', 'employees.user_id')
                                        //             ->where('users.role', 'employee')
                                        //             ->pluck('users.name', 'employees.id');
                                        //     })
                                        //     ->searchable()
                                        //     ->preload()
                                        //     ->required(),

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
                                    ->placeholder('e.g, Tagum City, Davao del Norte')
                                    ->rows(3)
                                    ->required(),

                                // Hidden Inputs
                                Hidden::make('no_of_shifts')
                                    ->default(0),

                                Hidden::make('is_24hrs')
                                    ->default(0),

                                Hidden::make('has_broken_time')
                                    ->default(0),

                                Hidden::make('scheduling')
                                    ->default('regular'),

                                ComponentsGroup::make()
                                    ->schema([
                                        TimePicker::make('reg_sched_start')
                                            ->label('Schedule Start')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                $set('opening_hrs', $state);
                                            }),

                                        TimePicker::make('reg_sched_end')
                                            ->label('Schedule End')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                $set('closed_hrs', $state);
                                            }),

                                        TimePicker::make('opening_hrs')
                                            ->visible(false)
                                            ->dehydrated(true),

                                        TimePicker::make('closed_hrs')
                                            ->visible(false)
                                            ->dehydrated(true),

                                    ])
                                    ->columns(2),
                            ]),

                    ])
                    ->action(function (array $data) {
                        ModelsBranch::create([
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

                        $this->dispatch('refreshBranchTable');

                        Notification::make()
                            ->title('New branch created')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Create New Branch: Regular Schedule')
                    ->modalSubmitActionLabel('Save'),

                // Regular & Shifting Schedule Branch
                Action::make('regular&shift')
                    ->label('Regular & Shifting Schedule')
                    ->tooltip('Regular hours & dual-shift split schedule')
                    ->icon(Heroicon::PlusCircle)
                    ->schema([
                        Section::make()
                            ->schema([
                                ComponentsGroup::make()
                                    ->schema([
                                        TextInput::make('branch_name')
                                            ->label('Branch Name')
                                            ->maxLength('255')
                                            ->placeholder('e.g., Tagum Station')
                                            ->unique(
                                                table: ModelsBranch::class,
                                                column: 'branch_name',
                                            )
                                            ->required(),

                                        Select::make('employee_id')
                                            ->label('Select SIC Employee')
                                            ->options(function () {
                                                return Employee::query()
                                                    ->join('users', 'users.id', '=', 'employees.user_id')
                                                    ->where('users.role', 'employee')
                                                    ->pluck('users.name', 'employees.id');
                                            })
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
                                    ->placeholder('e.g, Tagum City, Davao del Norte')
                                    ->rows(3)
                                    ->required(),

                                // Hidden Inputs
                                Hidden::make('no_of_shifts')
                                    ->default(2),

                                Hidden::make('is_24hrs')
                                    ->default(0),

                                Hidden::make('has_broken_time')
                                    ->default(1),

                                Hidden::make('scheduling')
                                    ->default('regular & shifting'),

                                ComponentsGroup::make()
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

                                ComponentsGroup::make()
                                    ->schema([
                                        Fieldset::make('Shifting Schedules')
                                            ->schema([
                                                TimePicker::make('shift1_start')
                                                    ->label('1st Shift Start')
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        $set('opening_hrs', $state);
                                                    }),

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
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        $set('closed_hrs', $state);
                                                    }),

                                                TimePicker::make('opening_hrs')
                                                    ->visible(false)
                                                    ->dehydrated(true),

                                                TimePicker::make('closed_hrs')
                                                    ->visible(false)
                                                    ->dehydrated(true),
                                            ])->columnSpanFull(),

                                    ])->columns(2),

                                ComponentsGroup::make()
                                    ->schema([
                                        Fieldset::make('Broken Time Schedules')
                                            ->schema([
                                                TimePicker::make('broken_shift1_start')
                                                    ->label('1st Shift Broken Start')
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        $set('opening_hrs', $state);
                                                    }),

                                                TimePicker::make('broken_shift1_end')
                                                    ->label('1st Shift Broken End')
                                                    ->required(),

                                                TimePicker::make('broken_shift2_start')
                                                    ->label('2nd Shift Broken Start')
                                                    ->required(),

                                                TimePicker::make('broken_shift2_end')
                                                    ->label('2nd Shift Broken End')
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        $set('closed_hrs', $state);
                                                    }),

                                            ])->columnSpanFull(),

                                    ])->columns(2),

                            ]),
                    ])
                    ->action(function (array $data) {
                        ModelsBranch::create([
                            'branch_name' => $data['branch_name'],
                            'branch_address' => $data['branch_address'],
                            'mobile_no' => $data['mobile_no'],
                            'employee_id' => $data['employee_id'],
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
                            'broken_shift2_start' => $data['broken_shift2_end'],
                            'broken_shift2_end' => $data['broken_shift2_start'],
                            'opening_hrs' => $data['opening_hrs'] ?? $data['shift1_start'],
                            'closed_hrs' => $data['closed_hrs'] ?? $data['shift2_end'],
                        ]);

                        $this->dispatch('refreshBranchTable');

                        Notification::make()
                            ->title('New branch created')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Create New Branch: Regular & Shifting Schedule')
                    ->modalSubmitActionLabel('Save'),

                // REGULAR AND 24 HOURS SHIFT
                Action::make('regular&24hrsshift')
                    ->label('Regular & 24hrs Shifting Schedule')
                    ->tooltip('24/7 split-shift rotation with fixed hours')
                    ->icon(Heroicon::PlusCircle)
                    ->schema([
                        Section::make()
                            ->schema([
                                ComponentsGroup::make()
                                    ->schema([
                                        TextInput::make('branch_name')
                                            ->label('Branch Name')
                                            ->maxLength('255')
                                            ->placeholder('e.g., Tagum Station')
                                            ->unique(
                                                table: ModelsBranch::class,
                                                column: 'branch_name',
                                            )
                                            ->required(),

                                        Select::make('employee_id')
                                            ->label('Select SIC Employee')
                                            ->options(function () {
                                                return Employee::query()
                                                    ->join('users', 'users.id', '=', 'employees.user_id')
                                                    ->where('users.role', 'employee')
                                                    ->pluck('users.name', 'employees.id');
                                            })
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
                                    ->placeholder('e.g, Tagum City, Davao del Norte')
                                    ->rows(3)
                                    ->required(),

                                // Hidden Inputs
                                Hidden::make('no_of_shifts')
                                    ->default(3),

                                Hidden::make('is_24hrs')
                                    ->default(1),

                                Hidden::make('has_broken_time')
                                    ->default(1),

                                Hidden::make('scheduling')
                                    ->default('regular & 24hrs shifting'),

                                ComponentsGroup::make()
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

                                ComponentsGroup::make()
                                    ->schema([
                                        Fieldset::make('Shifting Schedules')
                                            ->schema([
                                                TimePicker::make('shift1_start')
                                                    ->label('1st Shift Start')
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        $set('opening_hrs', $state);
                                                    }),

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
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        $set('closed_hrs', $state);
                                                    }),

                                                TimePicker::make('opening_hrs')
                                                    ->visible(false)
                                                    ->dehydrated(true),

                                                TimePicker::make('closed_hrs')
                                                    ->visible(false)
                                                    ->dehydrated(true),
                                            ])->columnSpanFull(),

                                    ])->columns(2),

                                ComponentsGroup::make()
                                    ->schema([
                                        Fieldset::make('Broken Time Schedules')
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

                                            ])->columnSpanFull(),

                                    ])->columns(2),

                            ]),
                    ])
                    ->action(function (array $data) {
                        ModelsBranch::create([
                            'branch_name' => $data['branch_name'],
                            'branch_address' => $data['branch_address'],
                            'mobile_no' => $data['mobile_no'],
                            'employee_id' => $data['employee_id'],
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
                            'closed_hrs' => $data['closed_hrs'] ?? $data['shift3_end'],
                        ]);

                        $this->dispatch('refreshBranchTable');

                        Notification::make()
                            ->title('New branch created')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Create New Branch: Regular & 24Hrs Shifting')
                    ->modalSubmitActionLabel('Save'),

            ])
                ->label('Add new branch')
                ->icon(Heroicon::BuildingOffice2)
                ->button(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BranchTable::class,
        ];
    }
}
