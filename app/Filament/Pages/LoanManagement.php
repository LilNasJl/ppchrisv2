<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class LoanManagement extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.pages.loan-management';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Loan Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    public string $activeLoanTab = 'list';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->defaultLoanData());
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return $this->loanFormSchema();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => EmployeeLoan::query()
                ->with(['employee.branch', 'employee.designation', 'amortizationStartPayrollPeriod'])
                ->latest('loan_date')
                ->latest('id'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery
                            ->where('lastname', 'like', "%{$search}%")
                            ->orWhere('middlename', 'like', "%{$search}%")
                            ->orWhere('firstname', 'like', "%{$search}%")
                            ->orWhere('uid', 'like', "%{$search}%")))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->leftJoin('employees as loan_employees', 'loan_employees.id', '=', 'employee_loans.employee_id')
                        ->orderBy('loan_employees.lastname', $direction)
                        ->orderBy('loan_employees.middlename', $direction)
                        ->orderBy('loan_employees.firstname', $direction)
                        ->select('employee_loans.*'))
                    ->wrap(),

                TextColumn::make('loan_type')
                    ->label('Loan Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->formatStateUsing(fn (mixed $state): string => $this->money($state))
                    ->sortable(),

                TextColumn::make('loan_interest')
                    ->label('Interest')
                    ->formatStateUsing(fn (mixed $state): string => $this->money($state))
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Loan Amount')
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->total_amount)
                    ->formatStateUsing(fn (mixed $state): string => $this->money($state)),

                TextColumn::make('balance_amount')
                    ->label('Total Balance')
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->balance_amount)
                    ->formatStateUsing(fn (mixed $state): string => $this->money($state)),

                TextColumn::make('payment_amount')
                    ->label('Payment')
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->payment_amount)
                    ->formatStateUsing(fn (mixed $state): string => $this->money($state)),

                TextColumn::make('loan_date')
                    ->label('Loan Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('amortizationStartPayrollPeriod.title')
                    ->label('Amortization Start')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmployeeLoan::STATUS_ACTIVE => 'success',
                        EmployeeLoan::STATUS_PAID => 'info',
                        EmployeeLoan::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('ledger')
                        ->label('View Ledger')
                        ->icon(Heroicon::ClipboardDocumentList)
                        ->modalHeading(fn (EmployeeLoan $record): string => 'Loan Ledger - '.$record->employee?->full_name)
                        ->modalWidth('7xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(fn (EmployeeLoan $record) => view('filament.pages.partials.loan-ledger', [
                            'loan' => $record->load(['payments.payrollPeriod']),
                        ])),

                    Action::make('edit')
                        ->label('Edit')
                        ->icon(Heroicon::PencilSquare)
                        ->schema($this->loanFormSchema())
                        ->fillForm(fn (EmployeeLoan $record): array => [
                            'employee_id' => $record->employee_id,
                            'loan_type' => $record->loan_type,
                            'loan_date' => optional($record->loan_date)->toDateString(),
                            'loan_amount' => $record->loan_amount,
                            'loan_interest' => $record->loan_interest,
                            'loan_terms_months' => $record->loan_terms_months,
                            'payment_amount' => $record->payment_amount,
                            'schedule' => $record->schedule,
                            'amortization_start_payroll_period_id' => $record->amortization_start_payroll_period_id,
                        ])
                        ->modalHeading('Edit Loan Information')
                        ->modalSubmitActionLabel('Update Loan')
                        ->action(fn (EmployeeLoan $record, array $data): mixed => $this->updateLoan($record, $data)),

                    DeleteAction::make()
                        ->label('Delete')
                        ->modalHeading('Delete loan record'),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(ComplianceBenefits::getUrl()),
        ];
    }

    public function showLoanTab(string $tab): void
    {
        if (! in_array($tab, ['list', 'requests', 'information'], true)) {
            return;
        }

        $this->activeLoanTab = $tab;
    }

    public function createLoan(): void
    {
        EmployeeLoan::query()->create($this->normalizeLoanData($this->form->getState()));

        $this->form->fill($this->blankLoanData());
        $this->activeLoanTab = 'information';
        $this->resetTable();

        Notification::make()
            ->title('Amortization generated')
            ->success()
            ->send();
    }

    protected function updateLoan(EmployeeLoan $loan, array $data): void
    {
        $loan->update($this->normalizeLoanData($data, $loan));

        Notification::make()
            ->title('Loan updated')
            ->success()
            ->send();
    }

    protected function loanFormSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    Fieldset::make('Employee Loan Details')
                        ->schema([
                            Select::make('employee_id')
                                ->label('Employee Name')
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search): array => $this->employeeSearchResults($search))
                                ->getOptionLabelUsing(fn (mixed $value): ?string => $this->employeeOptionLabel($value))
                                ->required(),

                            TextInput::make('loan_type')
                                ->label('Loan Type')
                                ->placeholder('Enter loan type, e.g., SSS, PAG-IBIG, Company Loan')
                                ->maxLength(191)
                                ->required(),

                            DatePicker::make('loan_date')
                                ->label('Loan Date')
                                ->default(now())
                                ->required(),

                            Select::make('schedule')
                                ->label('Schedule')
                                ->options(EmployeeLoan::scheduleOptions())
                                ->default(EmployeeLoan::SCHEDULE_EVERY_PAYROLL)
                                ->required(),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),

                    Fieldset::make('Loan Terms')
                        ->schema([
                            TextInput::make('loan_amount')
                                ->label('Loan Amount')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required(),

                            TextInput::make('loan_interest')
                                ->label('Loan Interest')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required(),

                            TextInput::make('loan_terms_months')
                                ->label('Loan Terms')
                                ->helperText('Every Payroll uses this as payroll-period count. 1st/2nd Quincena uses this as matching monthly deduction count.')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required(),

                            TextInput::make('payment_amount')
                                ->label('Payment')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required(),

                            Select::make('amortization_start_payroll_period_id')
                                ->label('Loan Amortization Start')
                                ->options(fn (): array => $this->openPayrollPeriodOptions())
                                ->getOptionLabelUsing(fn (mixed $value): ?string => $this->payrollPeriodOptionLabel($value))
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    protected function defaultLoanData(): array
    {
        return [
            'employee_id' => null,
            'loan_type' => 'Company Loan',
            'loan_date' => now()->toDateString(),
            'schedule' => EmployeeLoan::SCHEDULE_EVERY_PAYROLL,
            'loan_amount' => 0,
            'loan_interest' => 0,
            'loan_terms_months' => 1,
            'payment_amount' => 0,
            'amortization_start_payroll_period_id' => $this->defaultOpenPayrollPeriodId(),
        ];
    }

    protected function blankLoanData(): array
    {
        return [
            'employee_id' => null,
            'loan_type' => null,
            'loan_date' => null,
            'schedule' => null,
            'loan_amount' => null,
            'loan_interest' => null,
            'loan_terms_months' => null,
            'payment_amount' => null,
            'amortization_start_payroll_period_id' => null,
        ];
    }

    protected function normalizeLoanData(array $data, ?EmployeeLoan $loan = null): array
    {
        return [
            'employee_id' => (int) $data['employee_id'],
            'loan_type' => $data['loan_type'] ?? 'Company Loan',
            'loan_date' => $data['loan_date'] ?? now()->toDateString(),
            'schedule' => EmployeeLoan::normalizeSchedule($data['schedule'] ?? null),
            'loan_amount' => (float) ($data['loan_amount'] ?? 0),
            'loan_interest' => (float) ($data['loan_interest'] ?? 0),
            'loan_terms_months' => max(1, (int) ($data['loan_terms_months'] ?? 1)),
            'payment_amount' => (float) ($data['payment_amount'] ?? $loan?->payment_amount ?? 0),
            'paid_amount' => (float) ($data['paid_amount'] ?? $loan?->paid_amount ?? 0),
            'amortization_start_payroll_period_id' => filled($data['amortization_start_payroll_period_id'] ?? null)
                ? (int) $data['amortization_start_payroll_period_id']
                : null,
            'status' => $data['status'] ?? $loan?->status ?? EmployeeLoan::STATUS_ACTIVE,
        ];
    }

    protected function employeeSearchResults(string $search): array
    {
        return Employee::query()
            ->activeEmployment()
            ->with(['designation', 'branch'])
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('uid', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('middlename', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%");
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [
                $employee->id => $this->formatEmployeeOption($employee),
            ])
            ->all();
    }

    protected function employeeOptionLabel(mixed $employeeId): ?string
    {
        $employee = filled($employeeId)
            ? Employee::query()->with(['designation', 'branch'])->find($employeeId)
            : null;

        return $employee ? $this->formatEmployeeOption($employee) : null;
    }

    protected function formatEmployeeOption(Employee $employee): string
    {
        return trim(($employee->company_id ?: 'No ID').' - '.$employee->full_name.' | '.($employee->branch?->branch_name ?: 'No branch'));
    }

    protected function openPayrollPeriodOptions(): array
    {
        return PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->pluck('title', 'id')
            ->all();
    }

    protected function payrollPeriodOptionLabel(mixed $payrollPeriodId): ?string
    {
        return filled($payrollPeriodId)
            ? PayrollPeriod::query()->withTrashed()->find($payrollPeriodId)?->title
            : null;
    }

    protected function defaultOpenPayrollPeriodId(): ?int
    {
        return PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->value('id');
    }

    protected function money(mixed $amount): string
    {
        return number_format((float) $amount, 2);
    }
}
