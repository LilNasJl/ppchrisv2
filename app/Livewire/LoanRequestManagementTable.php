<?php

namespace App\Livewire;

use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRequest;
use App\Models\PayrollPeriod;
use App\Services\EmployeeLoanRequestService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class LoanRequestManagementTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Loan Requests')
            ->description('Review employee loan requests before they become active payroll deductions.')
            ->query(fn (): Builder => EmployeeLoanRequest::query()
                ->with([
                    'employee.branch',
                    'preferredStartPayrollPeriod',
                    'approvedLoan.amortizationStartPayrollPeriod',
                    'reviewedBy',
                ])
                ->orderByRaw("CASE status WHEN 'Pending' THEN 0 WHEN 'Approved' THEN 1 WHEN 'Rejected' THEN 2 ELSE 3 END")
                ->latest('request_date')
                ->latest('id'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery
                            ->where(function (Builder $nameQuery) use ($search): void {
                                $nameQuery
                                    ->where('uid', 'like', "%{$search}%")
                                    ->orWhere('lastname', 'like', "%{$search}%")
                                    ->orWhere('middlename', 'like', "%{$search}%")
                                    ->orWhere('firstname', 'like', "%{$search}%");
                            })))
                    ->wrap(),

                TextColumn::make('employee.branch.branch_name')
                    ->label('Branch')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('loan_type')
                    ->label('Loan Type')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('loan_amount')
                    ->label('Requested Amount')
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('loan_terms_months')
                    ->label('Terms')
                    ->getStateUsing(fn (EmployeeLoanRequest $record): string => $record->loan_terms_months.' '.$record->terms_label)
                    ->alignCenter(),

                TextColumn::make('schedule')
                    ->label('Schedule')
                    ->wrap(),

                TextColumn::make('request_date')
                    ->label('Requested')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmployeeLoanRequest::STATUS_APPROVED => 'success',
                        EmployeeLoanRequest::STATUS_REJECTED => 'danger',
                        EmployeeLoanRequest::STATUS_CANCELLED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EmployeeLoanRequest::statusOptions())
                    ->default(EmployeeLoanRequest::STATUS_PENDING),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->modalHeading(fn (EmployeeLoanRequest $record): string => 'Loan Request - '.$record->employee?->full_name)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(fn (EmployeeLoanRequest $record) => view('filament.pages.partials.loan-request-details', [
                            'request' => $record->load([
                                'employee',
                                'preferredStartPayrollPeriod',
                                'approvedLoan.amortizationStartPayrollPeriod',
                                'reviewedBy',
                            ]),
                        ])),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon(Heroicon::Check)
                        ->color('success')
                        ->visible(fn (EmployeeLoanRequest $record): bool => $record->status === EmployeeLoanRequest::STATUS_PENDING)
                        ->modalHeading('Approve Loan Request')
                        ->modalDescription('Review and finalize the terms that will be used for payroll deductions.')
                        ->modalSubmitActionLabel('Approve and Create Loan')
                        ->modalWidth('5xl')
                        ->schema($this->approvalSchema())
                        ->fillForm(fn (EmployeeLoanRequest $record): array => $this->approvalData($record))
                        ->action(function (EmployeeLoanRequest $record, array $data): void {
                            app(EmployeeLoanRequestService::class)->approve($record, $data, auth()->user());

                            $this->resetTable();

                            Notification::make()
                                ->title('Loan request approved')
                                ->body('The active employee loan and amortization schedule were created.')
                                ->success()
                                ->send();
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon(Heroicon::XMark)
                        ->color('danger')
                        ->visible(fn (EmployeeLoanRequest $record): bool => $record->status === EmployeeLoanRequest::STATUS_PENDING)
                        ->requiresConfirmation()
                        ->modalHeading('Reject Loan Request')
                        ->modalSubmitActionLabel('Reject Request')
                        ->schema([
                            Textarea::make('hr_comment')
                                ->label('HR Comment')
                                ->rows(4)
                                ->maxLength(2000)
                                ->required(),
                        ])
                        ->action(function (EmployeeLoanRequest $record, array $data): void {
                            app(EmployeeLoanRequestService::class)->reject(
                                $record,
                                (string) $data['hr_comment'],
                                auth()->user(),
                            );

                            $this->resetTable();

                            Notification::make()
                                ->title('Loan request rejected')
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->emptyStateHeading('No loan requests found');
    }

    public function render()
    {
        return view('livewire.loan-request-management-table');
    }

    protected function approvalSchema(): array
    {
        return [
            Fieldset::make('Employee Loan Details')
                ->schema([
                    TextInput::make('employee_name')
                        ->label('Employee')
                        ->disabled()
                        ->dehydrated(false),

                    Hidden::make('terms_basis'),

                    Hidden::make('loan_interest'),

                    TextInput::make('loan_type')
                        ->label('Loan Type')
                        ->maxLength(191)
                        ->required(),

                    DatePicker::make('loan_date')
                        ->label('Loan Date')
                        ->required(),

                    Select::make('schedule')
                        ->options(EmployeeLoan::scheduleOptions())
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set): void {
                            $set('amortization_start_payroll_period_id', null);
                            $this->syncCalculatedPayment($get, $set);
                        })
                        ->required(),

                    Textarea::make('requested_reason')
                        ->label('Employee Reason')
                        ->rows(3)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),

            Fieldset::make('Final Loan Terms')
                ->schema([
                    TextInput::make('loan_amount')
                        ->label('Loan Amount')
                        ->numeric()
                        ->minValue(0.01)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->syncCalculatedPayment($get, $set))
                        ->required(),

                    TextInput::make('interest_rate')
                        ->label('Monthly Interest Rate (%)')
                        ->helperText('Uses flat add-on interest across the final loan term months.')
                        ->numeric()
                        ->minValue(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->syncCalculatedPayment($get, $set))
                        ->required(),

                    TextInput::make('loan_terms_months')
                        ->label('Loan Terms (Months)')
                        ->helperText('One term equals one calendar month. Every Payroll deducts twice per month; quincena schedules deduct once per month.')
                        ->numeric()
                        ->minValue(1)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->syncCalculatedPayment($get, $set))
                        ->required(),

                    TextInput::make('payment_amount')
                        ->label('Payment per Payroll')
                        ->numeric()
                        ->minValue(0)
                        ->readOnly()
                        ->helperText('Calculated from the final amount, interest, schedule, and term months.')
                        ->required(),

                    Select::make('amortization_start_payroll_period_id')
                        ->label('Loan Amortization Start')
                        ->helperText('Select an open period. Deductions begin from this period and follow the selected schedule.')
                        ->options(fn (): array => $this->openPayrollPeriodOptions())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Textarea::make('hr_comment')
                        ->label('HR Comment')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),
        ];
    }

    protected function approvalData(EmployeeLoanRequest $request): array
    {
        $preferredPeriodId = $request->preferred_start_payroll_period_id;
        $isPreferredOpen = filled($preferredPeriodId)
            && PayrollPeriod::query()->whereKey($preferredPeriodId)->where('is_locked', false)->exists();

        return [
            'employee_name' => $request->employee?->full_name,
            'loan_type' => $request->loan_type,
            'loan_date' => now()->toDateString(),
            'schedule' => $request->schedule,
            'requested_reason' => $request->reason,
            'loan_amount' => $request->loan_amount,
            'loan_interest' => $request->loan_interest,
            'interest_rate' => $request->interest_rate,
            'loan_terms_months' => $request->loan_terms_months,
            'terms_basis' => $request->terms_basis,
            'payment_amount' => $request->payment_amount,
            'amortization_start_payroll_period_id' => $isPreferredOpen
                ? $preferredPeriodId
                : $this->defaultOpenPayrollPeriodId(),
            'hr_comment' => null,
        ];
    }

    protected function openPayrollPeriodOptions(): array
    {
        return PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->pluck('title', 'id')
            ->all();
    }

    protected function defaultOpenPayrollPeriodId(): ?int
    {
        return array_key_first($this->openPayrollPeriodOptions());
    }

    protected function syncCalculatedPayment(Get $get, Set $set): void
    {
        if (EmployeeLoan::normalizeTermsBasis($get('terms_basis')) !== EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH) {
            return;
        }

        $set('payment_amount', EmployeeLoan::plannedPaymentAmount(
            (float) ($get('loan_amount') ?? 0),
            EmployeeLoan::flatAddOnInterest(
                (float) ($get('loan_amount') ?? 0),
                (float) ($get('interest_rate') ?? 0),
                max(1, (int) ($get('loan_terms_months') ?? 1)),
            ),
            max(1, (int) ($get('loan_terms_months') ?? 1)),
            $get('schedule'),
            $get('terms_basis'),
        ));
    }
}
