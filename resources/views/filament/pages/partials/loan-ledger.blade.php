<livewire:loan-ledger-table
    :loan-id="$loan->id"
    :key="'loan-ledger-table-'.$loan->id"
/>

@php($adjustments = $loan->adjustments()->with(['effectivePayrollPeriod', 'changedBy'])->latest('id')->get())
@if ($adjustments->isNotEmpty())
    <section class="mt-5 border-t border-gray-700 pt-4">
        <h3 class="text-sm font-semibold text-gray-100">Loan adjustments</h3>
        <div class="mt-2 divide-y divide-gray-700 text-sm">
            @foreach ($adjustments as $adjustment)
                <div class="grid gap-2 py-3 md:grid-cols-3">
                    <div>
                        <div class="font-medium text-gray-100">{{ $adjustment->type === 'correct_total' ? 'Corrected total' : 'Remaining payment plan' }}</div>
                        <div class="text-gray-400">From {{ $adjustment->effectivePayrollPeriod?->title ?? 'Unknown period' }}</div>
                    </div>
                    <div class="text-gray-200">
                        @if ($adjustment->new_loan_amount !== null)
                            Loan amount: {{ number_format((float) $adjustment->previous_loan_amount, 2) }} to {{ number_format((float) $adjustment->new_loan_amount, 2) }}<br>
                        @endif
                        Total: {{ number_format((float) $adjustment->previous_total, 2) }} to {{ number_format((float) $adjustment->new_total, 2) }}<br>
                        {{ $adjustment->remaining_deductions }} deductions at {{ number_format((float) $adjustment->payment_amount, 2) }}
                    </div>
                    <div class="text-gray-400">
                        {{ $adjustment->reason }}<br>
                        {{ $adjustment->changedBy?->name ?? 'Unknown user' }} - {{ $adjustment->created_at?->format('M d, Y h:i A') }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
