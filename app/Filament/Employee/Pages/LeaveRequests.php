<?php

namespace App\Filament\Employee\Pages;

use App\Models\Employee;
use App\Models\Leave;
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

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'leave-requests';

    protected static ?string $title = 'Leave';

    protected static ?string $navigationLabel = 'Leave';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowLeftEndOnRectangle;

    protected static ?int $navigationSort = 4;

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->modifyQueryWithActiveTab(
                Leave::query()
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

                TextColumn::make('status')
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
        if ($leave->employee_id !== $this->employee()->id || $leave->status !== 'Pending') {
            Notification::make()
                ->title('Unable to cancel leave')
                ->body('Only pending leave requests can be cancelled.')
                ->danger()
                ->send();

            return;
        }

        $leave->delete();

        Notification::make()
            ->title('Leave request cancelled')
            ->success()
            ->send();
    }

    protected function leaveRequestSchema(): array
    {
        return [
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
                        }),

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
            Leave::validateCanCreateRequest(
                $this->employee(),
                (string) $data['leave_type'],
                $data['leave_from'],
                $data['leave_to'],
                (bool) ($data['is_half_day'] ?? false),
            );
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('Unable to send leave request')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Leave::create([
            'employee_id' => $this->employee()->id,
            'leave_type' => $data['leave_type'],
            'leave_from' => Carbon::parse($data['leave_from'])->toDateString(),
            'leave_to' => Carbon::parse($data['leave_to'])->toDateString(),
            'is_half_day' => (bool) ($data['is_half_day'] ?? false),
            'reason' => $data['reason'],
            'status' => 'Pending',
            'attachment_path' => $data['attachment_path'] ?? null,
            'attachment_original_name' => $data['attachment_original_name'] ?? null,
        ]);

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
