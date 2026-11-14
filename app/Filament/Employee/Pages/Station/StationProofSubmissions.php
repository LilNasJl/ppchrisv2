<?php

namespace App\Filament\Employee\Pages\Station;

use App\Filament\Employee\Pages\Dtr;
use App\Filament\Employee\Pages\DtrChangeRequests;
use App\Models\DtrSubmission;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\DtrChangeRequestService;
use App\Services\OnFieldDtrService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Override;

class StationProofSubmissions extends Page implements HasTable
{
    use HasTabs;
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.station-proof-submissions';

    protected static ?string $slug = 'station/proof-submissions';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'On Field DTR';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;

    public ?string $periodId = null;

    public static function canAccess(): bool
    {
        return parent::canAccess() && filled(auth()->user()?->employee);
    }

    public function mount(): void
    {
        $requestedPeriod = request()->query('periodId');
        $resolvedPeriodId = filled($requestedPeriod)
            ? (PayrollPeriod::resolvePublicId($requestedPeriod) ?: (int) $requestedPeriod)
            : null;

        $period = filled($resolvedPeriodId)
            ? PayrollPeriod::query()->find($resolvedPeriodId)
            : null;

        $this->periodId = $period ? (string) $period->getKey() : null;

        if (! auth()->user()?->employee) {
            abort(403, 'An employee account is required to submit On Field DTR.');
        }

        $this->loadDefaultActiveTab();
    }

    public function updatedPeriodId($value): void
    {
        $this->periodId = filled($value) ? (string) $value : null;
        $this->resetPage();
        $this->resetTable();
    }

    public function payrollPeriods(): Collection
    {
        return PayrollPeriod::query()
            ->newestFirst()
            ->get();
    }

    public function getSelectedPeriodProperty(): ?PayrollPeriod
    {
        $resolvedPeriodId = filled($this->periodId)
            ? (PayrollPeriod::resolvePublicId($this->periodId) ?: (int) $this->periodId)
            : null;

        return filled($resolvedPeriodId)
            ? PayrollPeriod::query()->find($resolvedPeriodId)
            : null;
    }

    public function getStatsProperty(): array
    {
        $employeeId = auth()->user()?->employee?->id;
        if (! $employeeId) {
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ];
        }

        $resolvedPeriodId = filled($this->periodId)
            ? (PayrollPeriod::resolvePublicId($this->periodId) ?: (int) $this->periodId)
            : null;

        $counts = DtrSubmission::query()
            ->where('submitted_by_employee_id', $employeeId)
            ->where('submission_type', DtrSubmission::TYPE_PROOF)
            ->when(filled($resolvedPeriodId), fn (Builder $q) => $q->where('payroll_period_id', $resolvedPeriodId))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $pending = (int) ($counts[DtrSubmission::STATUS_PENDING] ?? 0);
        $approved = (int) ($counts[DtrSubmission::STATUS_APPROVED] ?? 0);
        $rejected = (int) ($counts[DtrSubmission::STATUS_REJECTED] ?? 0);
        $total = $pending + $approved + $rejected;

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn (): ?int => $this->getTabCount(null)),

            'pending' => Tab::make('Pending')
                ->badge(fn (): ?int => $this->getTabCount(DtrSubmission::STATUS_PENDING))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrSubmission::STATUS_PENDING)),

            'approved' => Tab::make('Approved')
                ->badge(fn (): ?int => $this->getTabCount(DtrSubmission::STATUS_APPROVED))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrSubmission::STATUS_APPROVED)),

            'rejected' => Tab::make('Rejected')
                ->badge(fn (): ?int => $this->getTabCount(DtrSubmission::STATUS_REJECTED))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrSubmission::STATUS_REJECTED)),
        ];
    }

    protected function getTabCount(?string $status): ?int
    {
        $employeeId = auth()->user()?->employee?->id;
        if (! $employeeId) {
            return null;
        }

        $resolvedPeriodId = filled($this->periodId)
            ? (PayrollPeriod::resolvePublicId($this->periodId) ?: (int) $this->periodId)
            : null;

        $count = DtrSubmission::query()
            ->where('submitted_by_employee_id', $employeeId)
            ->where('submission_type', DtrSubmission::TYPE_PROOF)
            ->when(filled($resolvedPeriodId), fn (Builder $q) => $q->where('payroll_period_id', $resolvedPeriodId))
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->count();

        return $count > 0 ? $count : null;
    }

    public static function onFieldScheduleOptions(): array
    {
        return [
            'Regular' => 'Regular',
            'Flexible' => 'Flexible',
            'Shift1' => 'Shift 1',
            'Shift2' => 'Shift 2',
            'Shift3' => 'Shift 3',
            'Broken1' => 'Broken Shift 1',
            'Broken2' => 'Broken Shift 2',
            'Broken3' => 'Broken Shift 3',
            'Saturday' => 'Saturday',
            'Overtime' => 'Overtime Only',
        ];
    }

    protected function formatAttendanceTooltip(DtrSubmission $record): string
    {
        $entries = $record->normalizedEntries();

        if ($entries === []) {
            return 'No attendance entries.';
        }

        $service = app(OnFieldDtrService::class);
        $scheduleLabels = static::onFieldScheduleOptions();

        return collect($entries)
            ->values()
            ->map(function (array $entry, int $index) use ($record, $service, $scheduleLabels, $entries): string {
                $dateIn = filled($entry['date_in'] ?? null)
                    ? Carbon::parse($entry['date_in'])->format('M d, Y')
                    : '-';
                $in = filled($entry['time_in'] ?? null)
                    ? Carbon::parse($entry['time_in'])->format('g:i A')
                    : '-';
                $out = filled($entry['time_out'] ?? null)
                    ? Carbon::parse($entry['time_out'])->format('g:i A')
                    : '-';
                $defaults = $service->resolveEntryScheduleDefaults($record, $entry);
                $type = (string) ($defaults['schedule_type'] ?? 'Regular');
                $typeLabel = $scheduleLabels[$type] ?? $type;
                $schedStart = Carbon::parse($defaults['schedule_start'])->format('g:i A');
                $schedEnd = Carbon::parse($defaults['schedule_end'])->format('g:i A');
                $prefix = count($entries) > 1 ? 'Entry '.($index + 1).': ' : '';

                return "{$prefix}{$dateIn} ({$in} – {$out}) · {$typeLabel} ({$schedStart} – {$schedEnd})";
            })
            ->implode(' | ');
    }

    public function table(Table $table): Table
    {
        $employeeId = auth()->user()?->employee?->id;
        $resolvedPeriodId = filled($this->periodId)
            ? (PayrollPeriod::resolvePublicId($this->periodId) ?: (int) $this->periodId)
            : null;

        return $table
            ->query(fn (): Builder => $this->modifyQueryWithActiveTab(
                DtrSubmission::query()
                    ->with(['payrollPeriod', 'branch', 'reviewedBy'])
                    ->where('submitted_by_employee_id', $employeeId)
                    ->where('submission_type', DtrSubmission::TYPE_PROOF)
                    ->when(filled($resolvedPeriodId), fn (Builder $query): Builder => $query->where('payroll_period_id', $resolvedPeriodId))
                    ->latest()
            ))
            ->headerActions([
                Action::make('periodSelector')
                    ->view('filament.employee.pages.partials.dtr-period-select-action'),
            ])
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('attendance_summary')
                    ->label('Attendance')
                    ->getStateUsing(function (DtrSubmission $record): string {
                        $entries = $record->normalizedEntries();
                        $first = $entries[0] ?? null;

                        if (! $first) {
                            return '-';
                        }

                        $date = filled($first['date_in'] ?? null)
                            ? Carbon::parse($first['date_in'])->format('M d, Y')
                            : '-';
                        $extra = count($entries) > 1 ? ' (+'.(count($entries) - 1).')' : '';

                        return "{$date}{$extra}";
                    })
                    ->weight(FontWeight::SemiBold)
                    ->description(function (DtrSubmission $record): string {
                        $entries = $record->normalizedEntries();
                        $first = $entries[0] ?? null;

                        if (! $first) {
                            return '';
                        }

                        $in = filled($first['time_in'] ?? null)
                            ? Carbon::parse($first['time_in'])->format('g:i A')
                            : '-';
                        $out = filled($first['time_out'] ?? null)
                            ? Carbon::parse($first['time_out'])->format('g:i A')
                            : '-';

                        $overnight = '';
                        if (
                            filled($first['date_in'] ?? null)
                            && filled($first['date_out'] ?? null)
                            && $first['date_out'] !== $first['date_in']
                        ) {
                            $overnight = ' (+1d)';
                        }

                        $countSuffix = count($entries) > 1
                            ? ' · '.count($entries).' entries'
                            : '';

                        return "{$in} – {$out}{$overnight}{$countSuffix}";
                    })
                    ->tooltip(fn (DtrSubmission $record): string => $this->formatAttendanceTooltip($record))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('date_in', $direction))
                    ->wrap(),

                TextColumn::make('schedule_summary')
                    ->label('Schedule')
                    ->getStateUsing(function (DtrSubmission $record): string {
                        $entries = $record->normalizedEntries();
                        $first = $entries[0] ?? null;

                        if (! $first) {
                            return '-';
                        }

                        $defaults = app(OnFieldDtrService::class)->resolveEntryScheduleDefaults($record, $first);
                        $type = (string) ($defaults['schedule_type'] ?? 'Regular');

                        return static::onFieldScheduleOptions()[$type] ?? $type;
                    })
                    ->weight(FontWeight::Medium)
                    ->description(function (DtrSubmission $record): string {
                        $entries = $record->normalizedEntries();
                        $first = $entries[0] ?? null;

                        if (! $first) {
                            return '';
                        }

                        $defaults = app(OnFieldDtrService::class)->resolveEntryScheduleDefaults($record, $first);
                        $start = Carbon::parse($defaults['schedule_start'])->format('g:i A');
                        $end = Carbon::parse($defaults['schedule_end'])->format('g:i A');

                        return "{$start} – {$end}";
                    })
                    ->tooltip(fn (DtrSubmission $record): string => $this->formatAttendanceTooltip($record))
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('payrollPeriod.title')
                    ->label('Payroll Period')
                    ->weight(FontWeight::Medium)
                    ->description(fn (DtrSubmission $record): string => match (true) {
                        $record->payrollPeriod === null => '-',
                        (bool) $record->payrollPeriod->is_locked => 'Locked',
                        default => 'Open',
                    })
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('branch_name_snapshot')
                    ->label('Station / Branch')
                    ->getStateUsing(fn (DtrSubmission $record): string => $record->submittedBranchName())
                    ->searchable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->date('M d, Y')
                    ->weight(FontWeight::Medium)
                    ->description(fn (DtrSubmission $record): ?string => $record->created_at
                        ? $record->created_at->format('g:i A').' · '.$record->created_at->diffForHumans(short: true)
                        : null)
                    ->tooltip(fn (DtrSubmission $record): ?string => $record->created_at
                        ? $record->created_at->format('M d, Y g:i A').' ('.$record->created_at->diffForHumans().')'
                        : null)
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->icon(fn (string $state): Heroicon => match ($state) {
                        DtrSubmission::STATUS_APPROVED => Heroicon::CheckCircle,
                        DtrSubmission::STATUS_REJECTED => Heroicon::XCircle,
                        default => Heroicon::Clock,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        DtrSubmission::STATUS_APPROVED => 'success',
                        DtrSubmission::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->description(function (DtrSubmission $record): string {
                        if ($record->reviewed_at) {
                            $by = $record->reviewedBy?->username ?: 'HR';

                            return "By {$by}";
                        }

                        return $record->viewed_at ? 'Viewed by HR' : 'Pending review';
                    })
                    ->tooltip(function (DtrSubmission $record): string {
                        if ($record->reviewed_at) {
                            $by = $record->reviewedBy?->username ?: 'HR';
                            $when = $record->reviewed_at->format('M d, Y g:i A');
                            $remarks = filled($record->reviewer_remarks) ? " — {$record->reviewer_remarks}" : '';

                            return "{$record->status} by {$by} on {$when}{$remarks}";
                        }

                        return $record->viewed_at
                            ? 'Viewed by HR. Awaiting decision.'
                            : 'Submitted. Waiting for HR review.';
                    }),

                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('None')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->modalHeading('On Field DTR Details')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('4xl')
                        ->modalContent(fn (DtrSubmission $record) => view('filament.pages.partials.on-field-dtr-details', [
                            'submission' => $record,
                            'showReviewer' => true,
                        ])),
                    DeleteAction::make()
                        ->label('Delete')
                        ->visible(fn (DtrSubmission $record): bool => $record->isPending() && ! (bool) $record->payrollPeriod?->is_locked)
                        ->requiresConfirmation()
                        ->modalDescription('Only this pending request and its proof file will be deleted.')
                        ->before(function (DtrSubmission $record): void {
                            if ($record->file_path && Storage::disk('local')->exists($record->file_path)) {
                                Storage::disk('local')->delete($record->file_path);
                            }
                        }),
                ])->icon(Heroicon::EllipsisHorizontal)->tooltip('Actions'),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->poll('15s')
            ->emptyStateHeading('No On Field DTR submissions found')
            ->emptyStateDescription('Submit your attendance records with proof for HR review.')
            ->emptyStateIcon(Heroicon::DocumentCheck);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            EmbeddedTable::make(),
        ]);
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->url(fn (): string => Dtr::getUrl(array_filter([
                'periodId' => filled($this->periodId) ? PayrollPeriod::query()->find((int) $this->periodId)?->publicKey() : null,
            ]))),
            Action::make('submitDtr')
                ->label('Submit On Field DTR')
                ->icon(Heroicon::ArrowUpTray)
                ->modalHeading('Submit On Field DTR')
                ->modalDescription(fn (): string => $this->identityDescription())
                ->modalSubmitActionLabel('Submit DTR')
                ->modalWidth('4xl')
                ->disabled(fn (): bool => ! $this->hasValidEmployeeBinding())
                ->schema([
                    Select::make('payroll_period_id')
                        ->label('Payroll Period')
                        ->options(fn (): array => PayrollPeriod::query()
                            ->where('is_locked', false)
                            ->newestFirst()
                            ->pluck('title', 'id')
                            ->all())
                        ->default(fn () => $this->defaultOpenPayrollPeriodId())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Repeater::make('entries')
                        ->label('Attendance Records')
                        ->addActionLabel('Add DTR Entry')
                        ->defaultItems(1)
                        ->minItems(1)
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => filled($state['date_in'] ?? null)
                            ? 'D.T.R Entry: '.Carbon::parse($state['date_in'])->format('M d, Y')
                                .(filled($state['schedule_type'] ?? null) ? ' ['.$state['schedule_type'].']' : '')
                                .' ('.($state['time_in'] ?? '-').' - '.($state['time_out'] ?? '-').')'
                            : 'New On-Field D.T.R Entry')
                        ->schema([
                                    DatePicker::make('date_in')
                                        ->label('Date In')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                            if (blank($state)) {
                                                return;
                                            }
                                            $start = $get('schedule_start');
                                            $end = $get('schedule_end');
                                            if (filled($start) && filled($end)) {
                                                try {
                                                    if (Carbon::parse($end)->lessThanOrEqualTo(Carbon::parse($start))) {
                                                        $set('date_out', Carbon::parse($state)->addDay()->toDateString());
                                                        return;
                                                    }
                                                } catch (\Throwable) {}
                                            }
                                            if (blank($get('date_out'))) {
                                                $set('date_out', $state);
                                            }
                                        }),

                                    TimePicker::make('time_in')
                                        ->label('Time In')
                                        ->required(),

                                    DatePicker::make('date_out')
                                        ->label('Date Out')
                                        ->required()
                                        ->default(fn (Get $get): ?string => $get('date_in')),

                                    TimePicker::make('time_out')
                                        ->label('Time Out')
                                        ->required(),

                                    Select::make('schedule_type')
                                        ->label('Sched Type')
                                        ->options(static::onFieldScheduleOptions())
                                        ->default('Regular')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                            $employee = auth()->user()?->employee;
                                            $branch = $employee?->branch;
                                            [$start, $end] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, $state, formatForInput: true);
                                            $set('schedule_start', $start ?? '');
                                            $set('schedule_end', $end ?? '');

                                            $dateIn = $get('date_in');
                                            if (filled($dateIn) && filled($start) && filled($end)) {
                                                try {
                                                    if (Carbon::parse($end)->lessThanOrEqualTo(Carbon::parse($start))) {
                                                        $set('date_out', Carbon::parse($dateIn)->addDay()->toDateString());
                                                    } else {
                                                        $set('date_out', Carbon::parse($dateIn)->toDateString());
                                                    }
                                                } catch (\Throwable) {}
                                            }
                                        }),

                                    TextInput::make('schedule_start')
                                        ->label('Sched Start')
                                        ->placeholder('Auto-populated from schedule')
                                        ->readOnly()
                                        ->dehydrated(true)
                                        ->extraInputAttributes(['tabindex' => '-1'])
                                        ->required()
                                        ->default(function (): string {
                                            $employee = auth()->user()?->employee;
                                            $branch = $employee?->branch;
                                            [$start] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, 'Regular', formatForInput: true);

                                            return $start ?: '08:00 AM';
                                        }),

                                    TextInput::make('schedule_end')
                                        ->label('Sched End')
                                        ->placeholder('Auto-populated from schedule')
                                        ->readOnly()
                                        ->dehydrated(true)
                                        ->extraInputAttributes(['tabindex' => '-1'])
                                        ->required()
                                        ->default(function (): string {
                                            $employee = auth()->user()?->employee;
                                            $branch = $employee?->branch;
                                            [, $end] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, 'Regular', formatForInput: true);

                                            return $end ?: '05:00 PM';
                                        }),
                                ])
                                ->columns(['default' => 1, 'sm' => 2])
                                ->columnSpanFull(),

                    FileUpload::make('proof_file')
                        ->label('Proof File')
                        ->disk('local')
                        ->directory('dtr-proof-submissions')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                        ->maxSize(20480)
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $employee = $this->employee();

                    if (! $employee) {
                        Notification::make()->title('Employee record not found')->danger()->send();

                        return;
                    }

                    $submission = app(OnFieldDtrService::class)->submit($employee, $data);
                    $entryCount = count($submission->normalizedEntries());

                    Notification::make()
                        ->title('On Field DTR submitted')
                        ->body("The request ({$entryCount} ".str('entry')->plural($entryCount).') is pending HR review. No official D.T.R entry was created yet.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function employee(): ?Employee
    {
        return auth()->user()?->employee;
    }

    protected function hasValidEmployeeBinding(): bool
    {
        $employee = $this->employee();

        return (bool) ($employee && ! $employee->trashed() && $employee->branch && ! $employee->branch->trashed());
    }

    protected function identityDescription(): string
    {
        $employee = $this->employee();

        if (! $employee) {
            return 'No bound employee record was found.';
        }

        $branchName = $employee->branch?->branch_name ?? 'No Branch';

        return "Submitting as: {$employee->full_name} ({$employee->company_id}) - {$branchName}";
    }

    protected function defaultOpenPayrollPeriodId(): ?int
    {
        $selectedPeriod = filled($this->periodId)
            ? PayrollPeriod::query()->find((int) $this->periodId)
            : null;

        if ($selectedPeriod && ! $selectedPeriod->is_locked) {
            return (int) $selectedPeriod->getKey();
        }

        $latestOpenPeriodId = PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->value('id');

        return $latestOpenPeriodId ? (int) $latestOpenPeriodId : null;
    }
}
