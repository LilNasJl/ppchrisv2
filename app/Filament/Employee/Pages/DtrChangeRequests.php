<?php

namespace App\Filament\Employee\Pages;

use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\DtrChangeRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class DtrChangeRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.dtr-change-requests';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'dtr-change-requests';

    protected static ?string $title = 'D.T.R Change Requests';

    public static function canAccess(): bool
    {
        return parent::canAccess() && filled(auth()->user()?->employee);
    }

    public function mount(): void
    {
        $this->markReviewedRequestsAsSeen();
    }

    public function markReviewedRequestsAsSeen(): void
    {
        DtrChangeRequest::query()
            ->where('employee_id', $this->employee()->id)
            ->reviewed()
            ->unseenByEmployee()
            ->update(['employee_seen_at' => now()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DtrChangeRequest::query()
                ->where('employee_id', $this->employee()->id)
                ->with(['payrollPeriod', 'reviewedBySicRcAccount'])
                ->latest('created_at'))
            ->heading('My D.T.R Change Requests')
            ->description('Requests are reviewed by the SIC/RC account assigned to your branch. Submitting a request does not change your D.T.R directly.')
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('payroll_period_title_snapshot')
                    ->label('Payroll Period')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('date_from')->label('Date From')->date('M d, Y')->sortable(),
                TextColumn::make('date_to')->label('Date To')->date('M d, Y')->sortable(),

                TextColumn::make('request_type_label')
                    ->label('Request Type')
                    ->wrap(),

                TextColumn::make('description')
                    ->limit(55)
                    ->wrap(),

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
                Action::make('view')
                    ->label('View Details')
                    ->icon(Heroicon::Eye)
                    ->modalHeading('D.T.R Change Request')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema($this->detailsSchema())
                    ->fillForm(fn (DtrChangeRequest $record): array => $this->detailsData($record)),
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitChangeRequest')
                ->label('Submit Change Request')
                ->icon(Heroicon::Plus)
                ->modalHeading('Submit D.T.R Change Request')
                ->modalSubmitActionLabel('Submit Request')
                ->schema($this->requestSchema())
                ->action(function (array $data): void {
                    app(DtrChangeRequestService::class)->submit($this->employee(), $data);

                    Notification::make()
                        ->title('D.T.R change request submitted')
                        ->body('Your request was sent to the SIC/RC account assigned to your branch.')
                        ->success()
                        ->send();
                }),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Dtr::getUrl()),
        ];
    }

    protected function requestSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    Select::make('payroll_period_id')
                        ->label('Payroll Period')
                        ->options(fn (): array => PayrollPeriod::query()->newestFirst()->pluck('title', 'id')->all())
                        ->default(fn (): ?int => PayrollPeriod::resolvePublicId(request()->query('periodId')))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('date_from', null);
                            $set('date_to', null);
                        })
                        ->required(),

                    DatePicker::make('date_from')
                        ->label('Date From')
                        ->native(false)
                        ->minDate(fn (Get $get): ?string => $this->periodBoundary($get('payroll_period_id'), 'date_start'))
                        ->maxDate(fn (Get $get): ?string => $this->periodBoundary($get('payroll_period_id'), 'date_end'))
                        ->required(),

                    DatePicker::make('date_to')
                        ->label('Date To')
                        ->native(false)
                        ->minDate(fn (Get $get): ?string => $get('date_from') ?: $this->periodBoundary($get('payroll_period_id'), 'date_start'))
                        ->maxDate(fn (Get $get): ?string => $this->periodBoundary($get('payroll_period_id'), 'date_end'))
                        ->required(),

                    Select::make('request_type')
                        ->label('Change Request Type')
                        ->options(DtrChangeRequest::requestTypeOptions())
                        ->searchable()
                        ->required(),

                    Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Explain the D.T.R issue and the correction you are requesting.')
                        ->rows(5)
                        ->maxLength(2000)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    protected function detailsSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('date_submitted')->disabled()->dehydrated(false),
                    TextInput::make('status')->disabled()->dehydrated(false),
                    TextInput::make('payroll_period')->disabled()->dehydrated(false)->columnSpanFull(),
                    TextInput::make('date_range')->disabled()->dehydrated(false),
                    TextInput::make('request_type')->disabled()->dehydrated(false),
                    Textarea::make('description')->rows(5)->disabled()->dehydrated(false)->columnSpanFull(),
                    Textarea::make('reviewer_remarks')->label('SIC/RC Remarks')->rows(4)->disabled()->dehydrated(false)->columnSpanFull(),
                    TextInput::make('reviewed_by')->label('Reviewed By')->disabled()->dehydrated(false),
                    TextInput::make('reviewed_at')->label('Reviewed At')->disabled()->dehydrated(false),
                ])
                ->columns(2),
        ];
    }

    protected function detailsData(DtrChangeRequest $request): array
    {
        return [
            'date_submitted' => $request->created_at?->format('M d, Y h:i A'),
            'status' => $request->status,
            'payroll_period' => $request->payroll_period_title_snapshot,
            'date_range' => $request->date_from?->format('M d, Y').' - '.$request->date_to?->format('M d, Y'),
            'request_type' => $request->request_type_label,
            'description' => $request->description,
            'reviewer_remarks' => $request->reviewer_remarks ?: 'No remarks yet.',
            'reviewed_by' => $request->reviewedBySicRcAccount?->username ?: 'Not reviewed yet',
            'reviewed_at' => $request->reviewed_at?->format('M d, Y h:i A') ?: 'Not reviewed yet',
        ];
    }

    protected function periodBoundary(mixed $periodId, string $column): ?string
    {
        $value = filled($periodId) ? PayrollPeriod::query()->whereKey((int) $periodId)->value($column) : null;

        return filled($value) ? date('Y-m-d', strtotime((string) $value)) : null;
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->with('branch')->firstOrFail();
    }
}
