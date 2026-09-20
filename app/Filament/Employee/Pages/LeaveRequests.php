<?php

namespace App\Filament\Employee\Pages;

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
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequests extends Page implements HasForms, HasTable
{
    use HasTabs;
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.table-page';

    protected static ?string $slug = 'leave-requests';

    protected static ?string $title = 'Leave';

    protected static ?string $navigationLabel = 'Leave';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowLeftEndOnRectangle;

    protected static string|\UnitEnum|null $navigationGroup = 'My Workspace';

    protected static ?int $navigationSort = 3;

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->modifyQueryWithActiveTab(
                Leave::query()
                    ->with('approvalSteps')
                    ->where('employee_id', $this->employee()->id)
                    ->latest('created_at')
            ))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date Filed')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('leave_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('leave_from')->label('From')->date('M d, Y'),
                TextColumn::make('leave_to')->label('To')->date('M d, Y'),
                TextColumn::make('requested_days')->label('Days')->state(fn (Leave $record) => $record->getRequestedLeaveDays()),

                TextColumn::make('status')
                    ->formatStateUsing(fn (Leave $record) => $record->approval_label)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewLeave')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->modalSubmitAction(false)
                        ->modalHeading('Leave Request Details')
                        ->modalContent(fn (Leave $record) => view('filament.employee.pages.partials.leave-request-details', [
                            'leave' => $record,
                        ])),

                    Action::make('cancel')
                        ->label('Cancel')
                        ->icon(Heroicon::XCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Leave $record): bool => $record->status === 'Pending')
                        ->action(fn (Leave $record): mixed => $this->cancelLeave($record)),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                EmbeddedTable::make(),
            ]);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Pending')),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Approved')),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Rejected')),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Cancelled')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newLeaveRequest')
                ->label('New Leave Request')
                ->icon(Heroicon::Plus)
                ->schema($this->leaveRequestSchema())
                ->fillForm(fn (): array => $this->defaultLeaveRequestData())
                ->modalHeading('New Leave Request')
                ->modalSubmitActionLabel('Send Request')
                ->action(fn (array $data) => $this->createLeave($data)),

            Action::make('leaveCredits')
                ->label('Leave Credits')
                ->icon(Heroicon::InformationCircle)
                ->modalSubmitAction(false)
                ->modalHeading('Remaining Leave Credits')
                ->modalContent(fn () => view('filament.employee.pages.partials.leave-credits', [
                    'employee' => $this->employee(),
                ])),
        ];
    }

    protected function cancelLeave(Leave $leave): void
    {
        try {
            app(\App\Services\LeaveApprovalService::class)->cancel($leave, auth()->user());
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('Unable to cancel leave')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Leave request cancelled')
            ->success()
            ->send();
    }

    protected function leaveRequestSchema(): array
    {
        return [
            \Filament\Schemas\Components\View::make('filament.leave.workflow-preview')
                ->viewData(fn (): array => ['employee' => $this->employee()]),
            Section::make()
                ->schema([
                    Select::make('leave_type')
                        ->label('Leave Type')
                        ->options(fn (Get $get): array => (bool) $get('is_half_day')
                            ? [Leave::HALF_DAY_LEAVE => Leave::HALF_DAY_LEAVE]
                            : $this->leaveTypeOptions())
                        ->disabled(fn (Get $get): bool => (bool) $get('is_half_day'))
                        ->dehydrated(true)
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
                        ->options(fn (): array => app(LeaveScheduleOptionService::class)->optionsForEmployee($this->employee()->loadMissing('branch')))
                        ->visible(fn (Get $get): bool => (bool) $get('is_half_day')
                            && app(LeaveScheduleOptionService::class)->isDailyRateEmployee($this->employee()))
                        ->required(fn (Get $get): bool => (bool) $get('is_half_day')
                            && app(LeaveScheduleOptionService::class)->isDailyRateEmployee($this->employee()))
                        ->searchable()
                        ->preload()
                        ->dehydrated(true),

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

                    Textarea::make('reason')
                        ->rows(4)
                        ->columnSpanFull()
                        ->required(),

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

    protected function createLeave(array $data): void
    {
        if ((bool) ($data['is_half_day'] ?? false)) {
            $data['leave_type'] = Leave::HALF_DAY_LEAVE;
            $data['half_day_period'] = app(DtrDayPartService::class)->normalize($data['half_day_period'] ?? DtrDayPartService::MORNING);
            $data['half_day_schedule'] = app(LeaveScheduleOptionService::class)->isDailyRateEmployee($this->employee())
                ? app(LeaveScheduleOptionService::class)->normalizeScheduleKey($data['half_day_schedule'] ?? null)
                : null;

            if ($data['leave_from'] !== $data['leave_to']) {
                Notification::make()
                    ->title('Unable to send leave request')
                    ->body('For half-day leave, Leave From and Leave To must be the same date.')
                    ->danger()
                    ->send();

                return;
            }
        }

        if (Carbon::parse($data['leave_to'])->lessThan(Carbon::parse($data['leave_from']))) {
            Notification::make()
                ->title('Unable to send leave request')
                ->body('Leave To must be the same day or later than Leave From.')
                ->danger()
                ->send();

            return;
        }

        try {
            app(\App\Services\LeaveApprovalService::class)->submit($this->employee(), $data, auth()->user());
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('Unable to send leave request')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Leave request sent')
            ->success()
            ->send();
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

    protected function defaultLeaveRequestData(): array
    {
        return [
            'leave_type' => null,
            'leave_from' => now()->toDateString(),
            'leave_to' => now()->toDateString(),
            'is_half_day' => false,
            'half_day_period' => null,
            'half_day_schedule' => null,
            'reason' => null,
            'attachment_path' => null,
            'attachment_original_name' => null,
        ];
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->firstOrFail();
    }
}
