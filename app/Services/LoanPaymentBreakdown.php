<?php

namespace App\Services;

use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Models\PayrollPeriod;
use Illuminate\Support\Collection;

class LoanPaymentBreakdown
{
    public function addColumns(PayrollPeriod $period, Collection $rows, array $types): Collection
    {
        $breakdowns = $this->forEmployees($period, $rows->pluck('employee_id'));

        return $rows->map(function (array $row) use ($breakdowns, $types): array {
            foreach ($types as $id => $name) {
                $row['loan_type_'.$id] = $breakdowns[(int) ($row['employee_id'] ?? 0)][$name] ?? 0;
            }

            return $row;
        });
    }

    /** @return array<int, array<string, float>> */
    public function forEmployees(PayrollPeriod $period, Collection $employeeIds): array
    {
        $ids = $employeeIds->filter()->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        $totals = [];
        if ($period->is_locked) {
            $payments = EmployeeLoanPayment::query()
                ->with('loan')
                ->where('payroll_period_id', $period->id)
                ->where('status', EmployeeLoanPayment::STATUS_POSTED)
                ->whereHas('loan', fn ($query) => $query->withTrashed()->whereIn('employee_id', $ids))
                ->get();
            foreach ($payments as $payment) {
                $loan = $payment->loan;
                if ($loan) {
                    $this->add($totals, (int) $loan->employee_id, $loan->loan_type, (float) $payment->amount);
                }
            }
        } else {
            $loans = EmployeeLoan::query()
                ->with(['amortizationStartPayrollPeriod', 'postedPayments.payrollPeriod', 'adjustments.effectivePayrollPeriod'])
                ->whereIn('employee_id', $ids)
                ->whereIn('status', [EmployeeLoan::STATUS_ACTIVE, EmployeeLoan::STATUS_PAID])
                ->get();
            foreach ($loans as $loan) {
                $this->add($totals, (int) $loan->employee_id, $loan->loan_type, $loan->paymentAmountForPeriod($period));
            }
        }

        return collect($totals)->map(fn (array $types): array => collect($types)
            ->map(fn (int $cents): float => $cents / 100)->all())->all();
    }

    private function add(array &$totals, int $employeeId, ?string $type, float $amount): void
    {
        $cents = (int) round($amount * 100);
        if ($cents <= 0) {
            return;
        }

        $name = trim((string) $type) ?: 'Unclassified Loan';
        $totals[$employeeId][$name] = ($totals[$employeeId][$name] ?? 0) + $cents;
    }
}
