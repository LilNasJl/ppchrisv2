<?php

namespace App\Filament\Resources\Leaves\Schemas;

use App\Models\Employee;
use App\Models\Leave;
use App\Services\DtrDayPartService;
use App\Services\LeaveScheduleOptionService;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group as ComponentsGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LeaveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        ComponentsGroup::make([
                            Select::make('employee_id')
                                ->label('Employee Name')
                                ->live()
                                ->afterStateUpdated(fn (Set $set): mixed => $set('half_day_schedule', null))
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search) => Employee::query()
                                    ->activeEmployment()
                                    ->where(function ($query) use ($search): void {
                                        $query
                                            ->where('firstname', 'like', "%{$search}%")
                                            ->orWhere('middlename', 'like', "%{$search}%")
                                            ->orWhere('lastname', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($employee) => [
                                        $employee->id => "{$employee->lastname}, {$employee->firstname} {$employee->middlename}",
                                    ])
                                )
                                ->getOptionLabelUsing(fn ($value) => optional(Employee::find($value))->full_name
                                )
                                ->required(),

                            Select::make('leave_type')
                                ->label('Leave Type')
                                ->options(fn (Get $get): array => (bool) $get('is_half_day')
                                    ? [Leave::HALF_DAY_LEAVE => Leave::HALF_DAY_LEAVE]
                                    : [
                                        'Vacation Leave' => 'Vacation Leave',
                                        'Sick Leave' => 'Sick Leave',
                                        'Emergency/Calamity Leave' => 'Emergency/Calamity Leave',
                                        'Maternity Leave' => 'Maternity Leave',
                                        'Paternity Leave' => 'Paternity Leave',
                                        'Important/Personal Matter' => 'Important/Personal Matter',
                                        Leave::BIRTHDAY_LEAVE => Leave::BIRTHDAY_LEAVE,
                                    ])
                                ->searchable()
                                ->disabled(fn (Get $get): bool => (bool) $get('is_half_day'))
                                ->dehydrated(true)
                                ->required(),

                            DatePicker::make('leave_from')
                                ->label('Leave From')
                                ->required()
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        if (! (bool) $get('is_half_day') || blank($value)) {
                                            return;
                                        }

                                        if (Carbon::parse($value)->isSaturday()) {
                                            $fail('Saturday does not allow half-day leave. Use whole-day leave.');
                                        }
                                    },
                                ]),

                            DatePicker::make('leave_to')
                                ->label('Leave To')
                                ->required()
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        if (! (bool) $get('is_half_day')) {
                                            return;
                                        }

                                        if ($value !== $get('leave_from')) {
                                            $fail('For half-day leave, Leave From and Leave To must be the same date.');
                                        }
                                    },
                                ]),

                            Toggle::make('is_half_day')
                                ->label('Half Day')
                                ->live()
                                ->afterStateUpdated(function (Set $set, bool $state): void {
                                    if ($state) {
                                        $set('leave_type', Leave::HALF_DAY_LEAVE);
                                        $set('half_day_period', DtrDayPartService::MORNING);

                                        return;
                                    }

                                    $set('leave_type', null);
                                    $set('half_day_period', null);
                                })
                                ->default(false),

                            Select::make('half_day_period')
                                ->label('Half Day Period')
                                ->options(app(DtrDayPartService::class)->dayPartOptions())
                                ->visible(fn (Get $get): bool => (bool) $get('is_half_day'))
                                ->required(fn (Get $get): bool => (bool) $get('is_half_day'))
                                ->dehydrated(true),

                            Select::make('half_day_schedule')
                                ->label('Daily Rate Schedule')
                                ->options(fn (Get $get): array => app(LeaveScheduleOptionService::class)
                                    ->optionsForEmployee(Employee::query()->with('branch')->find($get('employee_id'))))
                                ->visible(fn (Get $get): bool => (bool) $get('is_half_day')
                                    && app(LeaveScheduleOptionService::class)->isDailyRateEmployee(Employee::query()->find($get('employee_id'))))
                                ->required(fn (Get $get): bool => (bool) $get('is_half_day')
                                    && app(LeaveScheduleOptionService::class)->isDailyRateEmployee(Employee::query()->find($get('employee_id'))))
                                ->searchable()
                                ->preload()
                                ->dehydrated(true),

                            Textarea::make('reason')
                                ->label('Reason')
                                ->placeholder('Reason...')
                                ->rows(5)
                                ->columnSpanFull()
                                ->required(),

                            Textarea::make('hr_comment')
                                ->label('HR Comment')
                                ->rows(4)
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn (?Leave $record): bool => filled($record?->hr_comment))
                                ->columnSpanFull(),

                            FileUpload::make('attachment_path')
                                ->label('Attached File')
                                ->disk('local')
                                ->directory('leave-attachments')
                                ->acceptedFileTypes([
                                    'image/png',
                                    'image/jpeg',
                                    'application/pdf',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ])
                                ->maxSize(2048)
                                ->storeFileNamesIn('attachment_original_name')
                                ->fetchFileInformation(false)
                                ->downloadable()
                                ->openable()
                                ->columnSpanFull(),
                        ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
