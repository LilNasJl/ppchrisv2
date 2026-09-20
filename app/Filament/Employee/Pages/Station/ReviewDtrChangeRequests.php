<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Services\DtrChangeRequestService;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UnitEnum;

class ReviewDtrChangeRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'station/change-requests';

    protected static ?string $title = 'Station Change Requests';

    protected static ?string $navigationLabel = 'Change Requests';

    protected static string|UnitEnum|null $navigationGroup = 'Station Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        if (! SchemaFacade::hasTable('dtr_change_requests')) {
            return null;
        }

        $user = auth()->user();
        if (! StationManagementAccess::canAccessStationManagement($user)) {
            return null;
        }

        $branchIds = StationManagementAccess::getManagedBranchIds($user);
        if ($branchIds === []) {
            return null;
        }

        $count = DtrChangeRequest::query()
            ->whereIn('branch_id', $branchIds)
            ->pending()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending station D.T.R change requests';
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            throw new HttpException(403, 'Unauthorized access to Station Management.');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DtrChangeRequest::query()
                ->whereIn('branch_id', $this->assignedBranchIds())
                ->with(['employee', 'branch', 'payrollPeriod', 'reviewedByEmployee.user'])
                ->latest('created_at'))
            ->heading('Employee D.T.R Change Requests')
            ->description('Approving a request records the decision. Use Open Employee D.T.R when a correction is required.')
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),

                TextColumn::make('employee_name_snapshot')
                    ->label('Employee')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('employee_company_id_snapshot')
                    ->label('Company ID')
                    ->searchable()
                    ->badge(),

                TextColumn::make('branch_name_snapshot')
                    ->label('Station / Branch')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('payroll_period_title_snapshot')
                    ->label('Payroll Period')
                    ->wrap(),

                TextColumn::make('date_from')->label('Date From')->date('M d, Y')->sortable(),
                TextColumn::make('date_to')->label('Date To')->date('M d, Y')->sortable(),

                TextColumn::make('request_type_label')
                    ->label('Request Type')
                    ->wrap(),

                TextColumn::make('description')
                    ->limit(55)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        DtrChangeRequest::STATUS_APPROVED => 'success',
                        DtrChangeRequest::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon(Heroicon::Eye)
                        ->modalHeading('D.T.R Change Request')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->schema($this->detailsSchema())
                        ->fillForm(fn (DtrChangeRequest $record): array => $this->detailsData($record)),

                    Action::make('openDtr')
                        ->label('Open Employee D.T.R')
                        ->icon(Heroicon::Clock)
                        ->visible(fn (DtrChangeRequest $record): bool => filled($record->employee) && filled($record->branch) && filled($record->payrollPeriod))
                        ->url(fn (DtrChangeRequest $record): string => ManageEmployeeDtr::getUrl([
                            'branchId' => $record->branch?->publicKey(),
                            'employeeId' => $record->employee?->publicKey(),
                            'periodId' => $record->payrollPeriod?->publicKey(),
                        ])),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (DtrChangeRequest $record): bool => $record->status === DtrChangeRequest::STATUS_PENDING)
                        ->modalHeading('Approve D.T.R Change Request')
                        ->modalDescription('This approves the request record. It will not automatically alter biometric punches.')
                        ->modalSubmitActionLabel('Approve Request')
                        ->schema([
                            Textarea::make('reviewer_remarks')
                                ->label('Station Manager Remarks')
                                ->rows(4)
                                ->maxLength(2000),
                        ])
                        ->action(function (DtrChangeRequest $record, array $data): void {
                            $reviewer = $this->managerEmployee();
                            $record = app(DtrChangeRequestService::class)->approve(
                                $record,
                                $reviewer,
                                $data['reviewer_remarks'] ?? null,
                            );

                            $this->notifyEmployee($record);
                            Notification::make()->title('D.T.R change request approved')->success()->send();
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn (DtrChangeRequest $record): bool => $record->status === DtrChangeRequest::STATUS_PENDING)
                        ->modalHeading('Reject D.T.R Change Request')
                        ->modalDescription('Explain why the change request cannot be approved.')
                        ->modalSubmitActionLabel('Reject Request')
                        ->schema([
                            Textarea::make('reviewer_remarks')
                                ->label('Rejection Remarks')
                                ->rows(4)
                                ->maxLength(2000)
                                ->required(),
                        ])
                        ->action(function (DtrChangeRequest $record, array $data): void {
                            $reviewer = $this->managerEmployee();
                            $record = app(DtrChangeRequestService::class)->reject(
                                $record,
                                $reviewer,
                                (string) $data['reviewer_remarks'],
                            );

                            $this->notifyEmployee($record);
                            Notification::make()->title('D.T.R change request rejected')->success()->send();
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->poll('15s')
            ->striped()
            ->defaultPaginationPageOption(10);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function detailsSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('employee')->disabled()->dehydrated(false),
                    TextInput::make('employee_id')->label('Company ID')->disabled()->dehydrated(false),
                    TextInput::make('branch')->label('Station Branch')->disabled()->dehydrated(false),
                    TextInput::make('date_submitted')->label('Date Submitted')->disabled()->dehydrated(false),
                    TextInput::make('payroll_period')->disabled()->dehydrated(false)->columnSpanFull(),
                    TextInput::make('date_range')->disabled()->dehydrated(false),
                    TextInput::make('request_type')->disabled()->dehydrated(false),
                    Textarea::make('description')->rows(5)->disabled()->dehydrated(false)->columnSpanFull(),
                    Textarea::make('reviewer_remarks')->label('Manager Remarks')->rows(4)->disabled()->dehydrated(false)->columnSpanFull(),
                    TextInput::make('status')->disabled()->dehydrated(false),
                    TextInput::make('reviewed_by')->label('Reviewed By')->disabled()->dehydrated(false),
                    TextInput::make('reviewed_at')->label('Reviewed At')->disabled()->dehydrated(false),
                ])
                ->columns(2),
        ];
    }

    protected function detailsData(DtrChangeRequest $request): array
    {
        return [
            'employee' => $request->employee_name_snapshot,
            'employee_id' => $request->employee_company_id_snapshot ?: 'N/A',
            'branch' => $request->branch_name_snapshot,
            'date_submitted' => $request->created_at?->format('M d, Y h:i A'),
            'payroll_period' => $request->payroll_period_title_snapshot,
            'date_range' => $request->date_from?->format('M d, Y').' - '.$request->date_to?->format('M d, Y'),
            'request_type' => $request->request_type_label,
            'description' => $request->description,
            'reviewer_remarks' => $request->reviewer_remarks ?: 'No remarks yet.',
            'status' => $request->status,
            'reviewed_by' => $request->reviewer_name ?: 'Not reviewed yet',
            'reviewed_at' => $request->reviewed_at?->format('M d, Y h:i A') ?: 'Not reviewed yet',
        ];
    }

    protected function managerEmployee(): Employee
    {
        $employee = auth()->user()?->employee;
        abort_unless($employee instanceof Employee, 403);

        return $employee;
    }

    protected function assignedBranchIds(): array
    {
        return StationManagementAccess::getManagedBranchIds(auth()->user());
    }

    protected function notifyEmployee(DtrChangeRequest $request): void
    {
        $user = $request->employee?->user;
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('D.T.R change request '.$request->status)
            ->body($request->reviewer_remarks ?: 'Open Change Requests to view the updated status.')
            ->color($request->status === DtrChangeRequest::STATUS_APPROVED ? 'success' : 'danger')
            ->sendToDatabase($user);
    }
}
