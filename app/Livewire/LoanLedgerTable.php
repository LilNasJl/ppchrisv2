<?php

namespace App\Livewire;

use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class LoanLedgerTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?int $loanId = null;

    public function mount(int $loanId): void
    {
        $this->loanId = $loanId;
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Payment History')
            ->description(fn (): string => $this->summary())
            ->query(fn (): Builder => EmployeeLoanPayment::query()
                ->with('payrollPeriod')
                ->where('employee_loan_id', $this->loanId ?? 0))
            ->defaultSort('processed_at', 'desc')
            ->paginated(false)
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('payrollPeriod.title')
                    ->label('Payroll Period')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('amount')
                    ->label('Deducted')
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(fn (EmployeeLoanPayment $record): string => $record->status ?: EmployeeLoanPayment::STATUS_POSTED)
                    ->color(fn (string $state): string => match ($state) {
                        EmployeeLoanPayment::STATUS_POSTED => 'success',
                        EmployeeLoanPayment::STATUS_VOIDED => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('processed_at')
                    ->label('Posted At')
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('-'),

                TextColumn::make('voided_at')
                    ->label('Reversed At')
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('-'),

                TextColumn::make('void_reason')
                    ->label('Remarks')
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->emptyStateHeading('No loan payment history yet')
            ->emptyStateDescription('Ledger entries will appear after a payroll period is locked.');
    }

    public function render()
    {
        return view('livewire.loan-ledger-table');
    }

    protected function summary(): string
    {
        $loan = EmployeeLoan::query()
            ->withCount([
                'payments as posted_payments_count' => fn (Builder $query) => $query
                    ->where('status', EmployeeLoanPayment::STATUS_POSTED),
                'payments as voided_payments_count' => fn (Builder $query) => $query
                    ->where('status', EmployeeLoanPayment::STATUS_VOIDED),
            ])
            ->find($this->loanId);

        if (! $loan) {
            return '';
        }

        return sprintf(
            'Total Loan: %s | Paid: %s | Balance: %s | Schedule: %s | Terms: %d | Payment: %s | %d posted, %d reversed',
            number_format((float) $loan->total_amount, 2),
            number_format((float) $loan->paid_amount, 2),
            number_format((float) $loan->balance_amount, 2),
            $loan->schedule,
            (int) $loan->loan_terms_months,
            number_format((float) $loan->payment_amount, 2),
            (int) ($loan->posted_payments_count ?? 0),
            (int) ($loan->voided_payments_count ?? 0),
        );
    }
}
