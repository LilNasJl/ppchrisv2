<?php

namespace App\Filament\Employee\Pages;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use App\Services\EmployeeLoanRequestService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Throwable;

class Loan extends Page implements HasTable
{
    use InteractsWithTable;

    #[Url(as: 'section', history: true)]
    public string $activeLoanSection = 'loans';

    protected string $view = 'filament.employee.pages.loan';

    protected static ?string $slug = 'loan';

    protected static ?string $title = 'Loan';

    protected static ?string $navigationLabel = 'Loan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'My Workspace';

    protected static ?int $navigationSort = 3;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestLoan')
                ->label('Request Loan')
                ->icon(Heroicon::Plus)
                ->modalHeading('Request Loan')
                ->modalDescription('The submitted terms are subject to HR review and may be adjusted before approval.')
                ->modalSubmitActionLabel('Submit Request')
                ->modalWidth('4xl')
                ->schema([
                    Fieldset::make('Loan Details')
                        ->schema([
                            TextInput::make('loan_type')
                                ->label('Loan Type')
                                ->placeholder('e.g., SSS, PAG-IBIG, Company Loan')
                                ->maxLength(191)
                                ->required(),

                            Select::make('schedule')
                                ->options(EmployeeLoan::scheduleOptions())
                                ->default(EmployeeLoan::SCHEDULE_EVERY_PAYROLL)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                    $set('preferred_start_payroll_period_id', null);
                                    $this->syncCalculatedPayment($get, $set);
                                })
                                ->required(),

                            TextInput::make('loan_amount')
                                ->label('Requested Amount')
                                ->numeric()
                                ->minValue(0.01)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->syncCalculatedPayment($get, $set))
                                ->required(),

                            TextInput::make('interest_rate')
                                ->label('Monthly Interest Rate (%)')
                                ->helperText('Uses flat add-on interest across the requested term months.')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->syncCalculatedPayment($get, $set))
                                ->required(),

                            TextInput::make('loan_terms_months')
                                ->label('Requested Terms (Months)')
                                ->helperText('One term equals one calendar month. Every Payroll deducts twice per month; quincena schedules deduct once per month.')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->syncCalculatedPayment($get, $set))
                                ->required(),

                            TextInput::make('payment_amount')
                                ->label('Payment per Payroll')
                                ->numeric()
                                ->minValue(0)
                                ->readOnly()
                                ->helperText('Calculated from the requested amount, interest, schedule, and requested term months.')
                                ->required(),

                            Select::make('preferred_start_payroll_period_id')
                                ->label('Preferred Amortization Start')
                                ->helperText('Select an open period. Deductions begin from this period and follow the selected schedule.')
                                ->options(fn (): array => $this->openPayrollPeriodOptions())
                                ->searchable()
                                ->preload()
                                ->required(),

                            Textarea::make('reason')
                                ->label('Reason')
                                ->rows(4)
                                ->maxLength(2000)
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),
                ])
                ->fillForm(fn (): array => [
                    'loan_type' => null,
                    'schedule' => EmployeeLoan::SCHEDULE_EVERY_PAYROLL,
                    'loan_amount' => null,
                    'interest_rate' => 0,
                    'loan_terms_months' => 1,
                    'payment_amount' => 0,
                    'preferred_start_payroll_period_id' => $this->defaultOpenPayrollPeriodId(),
                    'reason' => null,
                ])
                ->action(function (array $data, Action $action): void {
                    try {
                        app(EmployeeLoanRequestService::class)->create($this->employee(), $data);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->title('Unable to submit loan request')
                            ->body($this->firstValidationMessage($exception))
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();

                        return;
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Unable to submit loan request')
                            ->body('The request could not be saved. Please try again or contact HR.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();

                        return;
                    }

                    $this->activeLoanSection = 'requests';
                    $this->dispatch('loan-request-created');

                    Notification::make()
                        ->title('Loan request submitted')
                        ->body('HR has been notified and will review your request.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function showLoanSection(string $section): void
    {
        if (! in_array($section, ['loans', 'requests'], true)) {
            return;
        }

        $this->activeLoanSection = $section;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('My Loans')
            ->description('Loan details, current balance, status, and payment history.')
            ->query(fn (): Builder => EmployeeLoan::query()
                ->with(['amortizationStartPayrollPeriod', 'payments.payrollPeriod'])
                ->where('employee_id', $this->employee()->id)
                ->latest('loan_date')
                ->latest('id'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('loan_type')
                    ->label('Loan Type')
                    ->badge()
                    ->searchable(),

                TextColumn::make('loan_date')
                    ->label('Loan Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Loan')
                    ->alignEnd()
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->total_amount)
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('balance_amount')
                    ->label('Balance')
                    ->alignEnd()
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->balance_amount)
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('payment_amount')
                    ->label('Payment')
                    ->alignEnd()
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->payment_amount)
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('schedule')
                    ->label('Schedule')
                    ->wrap(),

                TextColumn::make('amortizationStartPayrollPeriod.title')
                    ->label('Start Period')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmployeeLoan::STATUS_ACTIVE => 'success',
                        EmployeeLoan::STATUS_PAID => 'info',
                        EmployeeLoan::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('history')
                    ->label('History')
                    ->icon(Heroicon::Clock)
                    ->modalHeading(fn (EmployeeLoan $record): string => 'Loan History - '.$record->loan_type)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (EmployeeLoan $record) => view('filament.pages.partials.loan-ledger', [
                        'loan' => $record,
                    ])),
            ]);
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
        $set('payment_amount', EmployeeLoan::plannedPaymentAmount(
            (float) ($get('loan_amount') ?? 0),
            EmployeeLoan::flatAddOnInterest(
                (float) ($get('loan_amount') ?? 0),
                (float) ($get('interest_rate') ?? 0),
                max(1, (int) ($get('loan_terms_months') ?? 1)),
            ),
            max(1, (int) ($get('loan_terms_months') ?? 1)),
            $get('schedule'),
            EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH,
        ));
    }

    protected function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            if (filled($messages[0] ?? null)) {
                return (string) $messages[0];
            }
        }

        return 'Please review the loan request details and try again.';
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->firstOrFail();
    }
}
