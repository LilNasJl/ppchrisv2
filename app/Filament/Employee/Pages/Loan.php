<?php

namespace App\Filament\Employee\Pages;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use App\Services\EmployeeLoanRequestService;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Throwable;

class Loan extends Page implements HasTable
{
    use InteractsWithTable;

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
                                ->required(),

                            TextInput::make('loan_amount')
                                ->label('Requested Amount')
                                ->numeric()
                                ->minValue(0.01)
                                ->required(),

                            TextInput::make('loan_interest')
                                ->label('Proposed Interest')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required(),

                            TextInput::make('loan_terms_months')
                                ->label('Requested Terms')
                                ->helperText('Every Payroll counts payroll periods. Quincena schedules count matching monthly periods.')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required(),

                            TextInput::make('payment_amount')
                                ->label('Proposed Payment')
                                ->numeric()
                                ->minValue(0.01)
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        $totalLoan = (float) ($get('loan_amount') ?? 0)
                                            + (float) ($get('loan_interest') ?? 0);
                                        $terms = max(1, (int) ($get('loan_terms_months') ?? 1));
                                        $scheduledTotal = (float) $value * $terms;

                                        if ($totalLoan > 0 && $scheduledTotal + 0.001 < $totalLoan) {
                                            $minimumPayment = ceil(($totalLoan / $terms) * 100) / 100;

                                            $fail(
                                                'The proposed payment must be at least '
                                                .number_format($minimumPayment, 2)
                                                ." for {$terms} term(s).",
                                            );
                                        }
                                    },
                                ])
                                ->required(),

                            Select::make('preferred_start_payroll_period_id')
                                ->label('Preferred Amortization Start')
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
                    'loan_interest' => 0,
                    'loan_terms_months' => 1,
                    'payment_amount' => null,
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
        return PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->value('id');
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
