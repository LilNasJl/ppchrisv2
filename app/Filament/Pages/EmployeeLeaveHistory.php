<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Leave;
use App\Services\DtrDayPartService;
use App\Services\LeaveScheduleOptionService;
use BackedEnum;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Override;
use RuntimeException;

class EmployeeLeaveHistory extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'employee-leave-history';

    protected static ?string $title = 'Employee Leave History';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    public ?int $employeeId = null;

    public ?Employee $employee = null;

    public ?string $returnUrl = null;

    public function mount(): void
    {
        $this->returnUrl = $this->normalizeReturnUrl(request()->query('returnUrl'));
        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->employee = Employee::query()
            ->with(['designation', 'branch', 'department'])
            ->findOrFail($this->employeeId);
    }

    public function getTitle(): string
    {
        return 'Leave History - '.$this->employee?->full_name;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Leave::query()
                ->where('employee_id', $this->employeeId)
                ->whereIn('status', ['Approved', 'Rejected'])
                ->latest('created_at'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date Filed')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('leave_from')
                    ->label('From')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('leave_to')
                    ->label('To')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('requested_days')
                    ->label('Days')
                    ->getStateUsing(fn (Leave $record): string => (string) $record->getRequestedLeaveDays()),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('reviewedBy.name')
                    ->label('Approved/Rejected By')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reviewed_at')
                    ->label('Approved/Rejected At')
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewLeave')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->modalSubmitAction(false)
                        ->modalHeading('Leave History Details')
                        ->modalContent(fn (Leave $record) => view('filament.employee.pages.partials.leave-request-details', [
                            'leave' => $record,
                        ])),

                    Action::make('editLeave')
                        ->label('Edit')
                        ->icon(Heroicon::PencilSquare)
                        ->schema($this->leaveHistorySchema())
                        ->fillForm(fn (Leave $record): array => $this->leaveHistoryFormData($record))
                        ->modalHeading('Edit Leave History')
                        ->modalSubmitActionLabel('Update Leave')
                        ->action(fn (Leave $record, array $data): mixed => $this->updateHistoryLeave($record, $data)),

                    Action::make('deleteLeaveHistory')
                        ->label('Delete')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete leave history')
                        ->modalDescription('This will return any deducted leave credits before permanently deleting the leave history record.')
                        ->action(fn (Leave $record): mixed => $this->deleteHistoryLeave($record)),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('addLeave')
                ->label('Add Leave')
                ->icon(Heroicon::Plus)
                ->schema($this->leaveHistorySchema())
                ->fillForm(fn (): array => $this->defaultLeaveHistoryData())
                ->modalHeading('Add Leave History')
                ->modalSubmitActionLabel('Save Leave')
                ->action(fn (array $data): mixed => $this->createHistoryLeave($data)),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => $this->getReturnUrl()),
        ];
    }

    protected function leaveHistorySchema(): array
    {
        return [
            Section::make()
                ->schema([
                    Select::make('status')
                        ->label('Final Status')
                        ->options([
                            'Approved' => 'Approved',
                            'Rejected' => 'Rejected',
                        ])
                        ->required(),

                    Select::make('leave_type')
                        ->label('Leave Type')
                        ->options(fn (Get $get): array => (bool) $get('is_half_day')
                            ? [Leave::HALF_DAY_LEAVE => Leave::HALF_DAY_LEAVE]
                            : $this->leaveTypeOptions())
                        ->disabled(fn (Get $get): bool => (bool) $get('is_half_day'))
                        ->dehydrated(true)
                        ->searchable()
                        ->required(),

                    Toggle::make('is_half_day')
                        ->label('Half Day')
                        ->live()
                        ->afterStateUpdated(function (Set $set, bool $state): void {
                            $set('leave_type', $state ? Leave::HALF_DAY_LEAVE : null);
                            $set('half_day_period', $state ? DtrDayPartService::MORNING : null);
                        }),

                    Select::make('half_day_period')
                        ->label('Half Day Period')
                        ->options(app(DtrDayPartService::class)->dayPartOptions())
                        ->visible(fn (Get $get): bool => (bool) $get('is_half_day'))
                        ->required(fn (Get $get): bool => (bool) $get('is_half_day'))
                        ->dehydrated(true),

                    Select::make('half_day_schedule')
                        ->label('Daily Rate Schedule')
                        ->options(fn (): array => app(LeaveScheduleOptionService::class)->optionsForEmployee($this->employee?->loadMissing('branch')))
                        ->visible(fn (Get $get): bool => (bool) $get('is_half_day')
                            && app(LeaveScheduleOptionService::class)->isDailyRateEmployee($this->employee))
                        ->required(fn (Get $get): bool => (bool) $get('is_half_day')
                            && app(LeaveScheduleOptionService::class)->isDailyRateEmployee($this->employee))
                        ->searchable()
                        ->preload()
                        ->dehydrated(true),

                    DatePicker::make('leave_from')
                        ->label('Leave From')
                        ->required(),

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

                    Textarea::make('reason')
                        ->label('Reason')
                        ->rows(4)
                        ->columnSpanFull()
                        ->required(),

                    Textarea::make('hr_comment')
                        ->label('HR Comment')
                        ->rows(4)
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
        ];
    }

    protected function createHistoryLeave(array $data): void
    {
        try {
            $this->saveHistoryLeave(null, $data);

            Notification::make()
                ->title('Leave history added')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Unable to add leave history')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function updateHistoryLeave(Leave $leave, array $data): void
    {
        try {
            $this->saveHistoryLeave($leave, $data);

            Notification::make()
                ->title('Leave history updated')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Unable to update leave history')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function deleteHistoryLeave(Leave $leave): void
    {
        try {
            DB::transaction(function () use ($leave): void {
                $leave = Leave::query()->lockForUpdate()->findOrFail($leave->id);

                $employee = Employee::query()->lockForUpdate()->findOrFail($leave->employee_id);
                $employee->resetLeaveCreditsIfNeeded();
                $employee->refresh();

                $this->restorePreviousLeaveCredits($employee, $leave);
                $employee->save();

                $leave->forceDelete();

                if ((int) $employee->id === (int) $this->employeeId) {
                    $this->employee = $employee->refresh();
                }
            });

            Notification::make()
                ->title('Leave history permanently deleted and credits returned')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Unable to delete leave history')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function saveHistoryLeave(?Leave $leave, array $data): Leave
    {
        $data = $this->normalizeLeaveHistoryData($data);
        $this->validateLeaveHistoryData($data);

        return DB::transaction(function () use ($leave, $data): Leave {
            $employee = Employee::query()->lockForUpdate()->findOrFail($this->employeeId);
            $employee->resetLeaveCreditsIfNeeded();
            $employee->refresh();

            $leave = $leave
                ? Leave::withTrashed()->lockForUpdate()->findOrFail($leave->id)
                : new Leave(['employee_id' => $employee->id]);

            if ((int) $leave->employee_id !== (int) $employee->id) {
                throw new RuntimeException('This leave record does not belong to the selected employee.');
            }

            if ($leave->exists && $leave->trashed()) {
                throw new RuntimeException('Deleted leave history can be viewed but cannot be edited.');
            }

            $this->restorePreviousLeaveCredits($employee, $leave);

            [$deductedLeaveCredits, $deductedBirthdayLeaveCredits] = $this->deductionsForFinalStatus($employee, $leave, $data);

            $employee->save();

            $leave->forceFill([
                'employee_id' => $employee->id,
                'leave_type' => $data['leave_type'],
                'leave_from' => $data['leave_from'],
                'leave_to' => $data['leave_to'],
                'is_half_day' => (bool) $data['is_half_day'],
                'half_day_period' => (bool) $data['is_half_day'] ? $data['half_day_period'] : null,
                'half_day_schedule' => (bool) $data['is_half_day'] ? ($data['half_day_schedule'] ?? null) : null,
                'reason' => $data['reason'],
                'hr_comment' => $data['hr_comment'] ?? null,
                'status' => $data['status'],
                'deducted_leave_credits' => $deductedLeaveCredits,
                'deducted_birthday_leave_credits' => $deductedBirthdayLeaveCredits,
                'status_updated_at' => now(),
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'attachment_path' => $data['attachment_path'] ?? null,
                'attachment_original_name' => $data['attachment_original_name'] ?? null,
            ])->save();

            $this->employee = $employee->refresh();

            return $leave->refresh();
        });
    }

    protected function restorePreviousLeaveCredits(Employee $employee, Leave $leave): void
    {
        if (! $leave->exists) {
            return;
        }

        $employee->leave_credits = (float) $employee->leave_credits + (float) $leave->deducted_leave_credits;
        $employee->birthday_leave_credits = (float) $employee->birthday_leave_credits + (float) $leave->deducted_birthday_leave_credits;
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function deductionsForFinalStatus(Employee $employee, Leave $leave, array $data): array
    {
        if ($data['status'] !== 'Approved') {
            return [0.0, 0.0];
        }

        $leaveDays = $this->requestedLeaveDays($data);

        if ($data['leave_type'] === Leave::BIRTHDAY_LEAVE) {
            $alreadyUsedBirthdayLeave = Leave::withTrashed()
                ->where('employee_id', $employee->id)
                ->where('leave_type', Leave::BIRTHDAY_LEAVE)
                ->where('status', 'Approved')
                ->whereYear('leave_from', Carbon::parse($data['leave_from'])->year)
                ->when($leave->exists, fn (Builder $query) => $query->whereKeyNot($leave->id))
                ->exists();

            if ($alreadyUsedBirthdayLeave || (float) $employee->birthday_leave_credits < 1) {
                throw new RuntimeException('Birthday leave has already been used for this year.');
            }

            $employee->birthday_leave_credits = (float) $employee->birthday_leave_credits - 1;

            return [0.0, 1.0];
        }

        if ((float) $employee->leave_credits < $leaveDays) {
            throw new RuntimeException('Employee does not have enough leave credits.');
        }

        $employee->leave_credits = (float) $employee->leave_credits - $leaveDays;

        return [$leaveDays, 0.0];
    }

    protected function requestedLeaveDays(array $data): float
    {
        return (new Leave([
            'employee_id' => $this->employeeId,
            'leave_type' => $data['leave_type'],
            'leave_from' => $data['leave_from'],
            'leave_to' => $data['leave_to'],
            'is_half_day' => (bool) $data['is_half_day'],
        ]))->getRequestedLeaveDays();
    }

    protected function normalizeLeaveHistoryData(array $data): array
    {
        $data['is_half_day'] = (bool) ($data['is_half_day'] ?? false);
        $data['status'] = in_array($data['status'] ?? null, ['Approved', 'Rejected'], true)
            ? $data['status']
            : 'Approved';

        if ($data['is_half_day']) {
            $data['leave_type'] = Leave::HALF_DAY_LEAVE;
            $data['half_day_period'] = app(DtrDayPartService::class)->normalize($data['half_day_period'] ?? DtrDayPartService::MORNING);
            $data['half_day_schedule'] = app(LeaveScheduleOptionService::class)->isDailyRateEmployee($this->employee)
                ? app(LeaveScheduleOptionService::class)->normalizeScheduleKey($data['half_day_schedule'] ?? null)
                : null;
        } else {
            $data['half_day_period'] = null;
            $data['half_day_schedule'] = null;
        }

        $data['leave_from'] = Carbon::parse($data['leave_from'])->toDateString();
        $data['leave_to'] = Carbon::parse($data['leave_to'])->toDateString();

        return $data;
    }

    protected function validateLeaveHistoryData(array $data): void
    {
        if (Carbon::parse($data['leave_to'])->lessThan(Carbon::parse($data['leave_from']))) {
            throw new RuntimeException('Leave To must be the same day or later than Leave From.');
        }

        if ($data['is_half_day'] && $data['leave_from'] !== $data['leave_to']) {
            throw new RuntimeException('For half-day leave, Leave From and Leave To must be the same date.');
        }

        if ($data['is_half_day'] && Carbon::parse($data['leave_from'])->isSaturday()) {
            throw new RuntimeException('Saturday does not allow half-day leave.');
        }

        if ($this->requestedLeaveDays($data) <= 0) {
            throw new RuntimeException('Leave date range is invalid.');
        }
    }

    protected function leaveHistoryFormData(Leave $record): array
    {
        return [
            'status' => in_array($record->status, ['Approved', 'Rejected'], true) ? $record->status : 'Approved',
            'leave_type' => $record->leave_type,
            'leave_from' => $record->leave_from?->toDateString(),
            'leave_to' => $record->leave_to?->toDateString(),
            'is_half_day' => (bool) $record->is_half_day,
            'half_day_period' => $record->half_day_period,
            'half_day_schedule' => $record->half_day_schedule,
            'reason' => $record->reason,
            'hr_comment' => $record->hr_comment,
            'attachment_path' => $record->attachment_path,
            'attachment_original_name' => $record->attachment_original_name,
        ];
    }

    protected function defaultLeaveHistoryData(): array
    {
        return [
            'status' => 'Approved',
            'leave_type' => null,
            'leave_from' => now()->toDateString(),
            'leave_to' => now()->toDateString(),
            'is_half_day' => false,
            'half_day_period' => null,
            'half_day_schedule' => null,
            'reason' => null,
            'hr_comment' => null,
            'attachment_path' => null,
            'attachment_original_name' => null,
        ];
    }

    protected function leaveTypeOptions(): array
    {
        return [
            'Vacation Leave' => 'Vacation Leave',
            'Sick Leave' => 'Sick Leave',
            'Emergency/Calamity Leave' => 'Emergency/Calamity Leave',
            'Maternity Leave' => 'Maternity Leave',
            'Paternity Leave' => 'Paternity Leave',
            'Important/Personal Matter' => 'Important/Personal Matter',
            Leave::BIRTHDAY_LEAVE => Leave::BIRTHDAY_LEAVE,
        ];
    }

    protected function getReturnUrl(): string
    {
        return $this->returnUrl ?: ViewEmployeeDetails::getUrl([
            'employeeId' => $this->employee?->publicKey(),
            'returnUrl' => EmployeeDetails::getUrl(),
        ]);
    }

    protected function normalizeReturnUrl(mixed $url): ?string
    {
        if (! is_string($url) || blank($url)) {
            return null;
        }

        $appUrl = url('/');
        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && preg_match('#^/livewire(?:-[A-Za-z0-9]+)?/update$#', $path)) {
            return null;
        }

        if (str_starts_with($url, $appUrl) || str_starts_with($url, '/')) {
            return $url;
        }

        return null;
    }
}
