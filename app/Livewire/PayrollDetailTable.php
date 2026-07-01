<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodEmployeeAdjustment;
use App\Models\PayrollSnapshot;
use App\Services\PayrollCalculator;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class PayrollDetailTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?int $periodId = null;

    public ?int $branchId = null;

    public ?int $employeeId = null;

    public ?string $paymentType = null;

    public bool $usePagination = true;

    protected array $rowCache = [];

    public function mount(
        ?int $periodId = null,
        ?int $branchId = null,
        ?int $employeeId = null,
        ?string $paymentType = null,
        bool $usePagination = true,
    ): void {
        $this->periodId = $periodId;
        $this->branchId = $branchId;
        $this->employeeId = $employeeId;
        $this->paymentType = filled($paymentType) ? strtolower($paymentType) : null;
        $this->usePagination = $usePagination;

        $this->ensureAdjustmentRows();
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->query())
            ->paginated($this->usePagination ? [10, 25, 50, 100] : false)
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                $this->textColumn('bank_id_no', 'Bank ID No.'),
                $this->textColumn('name', 'Name')->wrap(),
                $this->textColumn('designation', 'Designation')->placeholder('-')->wrap(),
                $this->textColumn('branch', 'Branch')->placeholder('-')->wrap(),
                $this->textColumn('rate', 'Rate')->alignCenter(),
                $this->moneyColumn('monthly_rate', 'Monthly Rate'),
                $this->moneyColumn('half_month_pay', 'Half Month Pay'),
                $this->moneyColumn('rate_per_day', 'Rate Per Day'),
                $this->moneyColumn('rate_per_hour', 'Rate Per Hour'),
                $this->numberColumn('days_worked', 'Days Work'),

                TextInputColumn::make('salary_adjustment')
                    ->label('Salary Adjustment')
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->disabled(fn (): bool => $this->isLocked())
                    ->afterStateUpdated(function (): void {
                        $this->rowCache = [];
                        $this->dispatch('payroll-adjustment-updated');
                    })
                    ->alignEnd(),

                $this->moneyColumn('allowance', 'Allowance'),
                $this->numberColumn('overtime_hours', 'OT Hrs'),
                $this->moneyColumn('overtime_amount', 'OT Amount'),
                $this->moneyColumn('regular_holiday', 'Regular Holiday'),
                $this->moneyColumn('special_holiday', 'Special Holiday'),
                $this->moneyColumn('gross_pay', 'Gross Pay'),
                $this->numberColumn('undertime_minutes', 'Undertime Minutes'),
                $this->moneyColumn('undertime_amount', 'Undertime Amount'),
                $this->moneyColumn('halfday', 'Halfday'),
                $this->moneyColumn('absent', 'Absent'),
                $this->moneyColumn('late', 'Late'),

                TextInputColumn::make('shortages')
                    ->label('Shortages')
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->disabled(fn (): bool => $this->isLocked())
                    ->afterStateUpdated(function (): void {
                        $this->rowCache = [];
                        $this->dispatch('payroll-adjustment-updated');
                    })
                    ->alignEnd(),

                $this->moneyColumn('uniform', 'Uniform'),
                $this->moneyColumn('other_deductions', 'Other Deductions'),
                $this->moneyColumn('loan_payment', 'Loan Payment'),
                $this->moneyColumn('sss_loan', 'SSS Loan'),
                $this->moneyColumn('sss_ee', 'SSS EE'),
                $this->moneyColumn('hdmf_loan', 'HDMF Loan'),
                $this->moneyColumn('hdmf_ee', 'HDMF EE'),
                $this->moneyColumn('phic_ee', 'PHIC EE'),
                $this->moneyColumn('total_deductions', 'Total Deductions'),
                $this->moneyColumn('net_pay', 'Net Pay'),
                $this->textColumn('signature', 'Signature'),
            ])
            ->emptyStateHeading('No payroll data available.');
    }

    public function render()
    {
        return view('livewire.payroll-detail-table');
    }

    protected function query(): Builder
    {
        $this->ensureAdjustmentRows();

        $employeeIds = $this->includedEmployeeIds();

        return PayrollPeriodEmployeeAdjustment::query()
            ->with(['employee.designation', 'employee.branch', 'employee.department', 'employee.user', 'employee.employeeDeductions.deduction'])
            ->join('employees as payroll_employees', 'payroll_employees.id', '=', 'payroll_period_employee_adjustments.employee_id')
            ->select('payroll_period_employee_adjustments.*')
            ->where('payroll_period_employee_adjustments.payroll_period_id', $this->periodId ?? 0)
            ->when(
                $employeeIds->isEmpty(),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
                fn (Builder $query) => $query->whereIn('payroll_period_employee_adjustments.employee_id', $employeeIds->all()),
            )
            ->orderBy('payroll_employees.lastname')
            ->orderBy('payroll_employees.firstname')
            ->orderBy('payroll_employees.id');
    }

    protected function ensureAdjustmentRows(): void
    {
        $period = $this->period();

        if (! $period) {
            return;
        }

        $this->includedEmployeeIds()
            ->each(fn (int $employeeId): PayrollPeriodEmployeeAdjustment => PayrollPeriodEmployeeAdjustment::firstOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employeeId,
                ],
                [
                    'salary_adjustment' => 0,
                    'shortages' => 0,
                ],
            ));
    }

    protected function includedEmployeeIds(): Collection
    {
        $period = $this->period();

        if (! $period) {
            return collect();
        }

        $calculator = app(PayrollCalculator::class);

        if ((bool) $period->is_locked) {
            $snapshotEmployeeIds = $this->snapshotEmployeeIds($period);

            if ($snapshotEmployeeIds->isNotEmpty()) {
                return $snapshotEmployeeIds;
            }
        }

        if (filled($this->employeeId)) {
            $employee = Employee::query()
                ->with(['user'])
                ->activeEmployment()
                ->find($this->employeeId);

            if (! $employee || $calculator->isEmployeePayrollExcluded($period, $employee)) {
                return collect();
            }

            if (filled($this->branchId) && (int) $employee->branch_id !== (int) $this->branchId) {
                return collect();
            }

            return collect([(int) $employee->id]);
        }

        $employeeIds = $calculator->includedEmployeeIds($period, filled($this->branchId) ? (int) $this->branchId : null);

        if (blank($this->paymentType) || $employeeIds->isEmpty()) {
            return $employeeIds->map(fn (mixed $id): int => (int) $id)->values();
        }

        return Employee::query()
            ->whereIn('id', $employeeIds->all())
            ->when(
                $this->paymentType === 'atm',
                fn (Builder $query) => $query->where('payment_type', 'like', '%atm%'),
                fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query
                        ->whereNull('payment_type')
                        ->orWhere('payment_type', 'not like', '%atm%');
                }),
            )
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();
    }

    protected function snapshotEmployeeIds(PayrollPeriod $period): Collection
    {
        return PayrollSnapshot::query()
            ->where('payroll_period_id', $period->id)
            ->when(filled($this->branchId), fn (Builder $query) => $query->where('branch_id', (int) $this->branchId))
            ->get()
            ->filter(function (PayrollSnapshot $snapshot): bool {
                if (filled($this->employeeId) && (int) $snapshot->employee_id !== (int) $this->employeeId) {
                    return false;
                }

                if (blank($this->paymentType)) {
                    return true;
                }

                $paymentType = str((string) data_get($snapshot->data, 'payment_type'))->lower();

                return $this->paymentType === 'atm'
                    ? $paymentType->contains('atm')
                    : ! $paymentType->contains('atm');
            })
            ->pluck('employee_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    protected function row(PayrollPeriodEmployeeAdjustment $record): array
    {
        if (array_key_exists($record->id, $this->rowCache)) {
            return $this->rowCache[$record->id];
        }

        $period = $this->period();

        if (! $period || ! $record->employee) {
            return $this->rowCache[$record->id] = [];
        }

        if ((bool) $period->is_locked) {
            $snapshot = PayrollSnapshot::query()
                ->where('payroll_period_id', $period->id)
                ->where('employee_id', $record->employee_id)
                ->first();

            if ($snapshot) {
                return $this->rowCache[$record->id] = $snapshot->data ?? [];
            }
        }

        return $this->rowCache[$record->id] = app(PayrollCalculator::class)->row($record->employee, $period);
    }

    protected function rowValue(PayrollPeriodEmployeeAdjustment $record, string $key): mixed
    {
        return $this->row($record)[$key] ?? null;
    }

    protected function period(): ?PayrollPeriod
    {
        if (blank($this->periodId)) {
            return null;
        }

        return PayrollPeriod::query()->find($this->periodId);
    }

    protected function isLocked(): bool
    {
        return (bool) $this->period()?->is_locked;
    }

    protected function textColumn(string $key, string $label): TextColumn
    {
        return TextColumn::make($key)
            ->label($label)
            ->getStateUsing(fn (PayrollPeriodEmployeeAdjustment $record): string => (string) $this->rowValue($record, $key));
    }

    protected function moneyColumn(string $key, string $label): TextColumn
    {
        return TextColumn::make($key)
            ->label($label)
            ->getStateUsing(fn (PayrollPeriodEmployeeAdjustment $record): mixed => $this->rowValue($record, $key))
            ->formatStateUsing(fn (mixed $state): string => $this->money($state))
            ->alignEnd();
    }

    protected function numberColumn(string $key, string $label): TextColumn
    {
        return TextColumn::make($key)
            ->label($label)
            ->getStateUsing(fn (PayrollPeriodEmployeeAdjustment $record): mixed => $this->rowValue($record, $key))
            ->formatStateUsing(fn (mixed $state): string => $this->plainNumber($state))
            ->alignEnd();
    }

    protected function money(mixed $value): string
    {
        return $value === null || $value === '' ? '' : number_format((float) $value, 2);
    }

    protected function plainNumber(mixed $value): string
    {
        return $value === null || $value === ''
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
