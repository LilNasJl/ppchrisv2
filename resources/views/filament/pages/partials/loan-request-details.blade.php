@php
    $money = fn (mixed $value): string => number_format((float) $value, 2);
    $approvedLoan = $request->approvedLoan;
@endphp

<div class="loan-request-details" style="display: grid; gap: 18px;">
    <section>
        <h3 style="font-size: 14px; font-weight: 800; margin-bottom: 10px;">Requested Terms</h3>
        <dl style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
            <div><dt style="color:#64748b;font-size:12px;">Employee</dt><dd style="font-weight:700;">{{ $request->employee?->full_name ?: '-' }}</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Status</dt><dd style="font-weight:700;">{{ $request->status }}</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Loan Type</dt><dd>{{ $request->loan_type }}</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Date Requested</dt><dd>{{ $request->request_date?->format('M d, Y') ?: '-' }}</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Amount</dt><dd>{{ $money($request->loan_amount) }}</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Interest</dt><dd>{{ $money($request->loan_interest) }}</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Total</dt><dd>{{ $money($request->total_amount) }}</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Payment</dt><dd>{{ $money($request->payment_amount) }}</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Terms</dt><dd>{{ $request->loan_terms_months }} period(s)</dd></div>
            <div><dt style="color:#64748b;font-size:12px;">Schedule</dt><dd>{{ $request->schedule }}</dd></div>
            <div style="grid-column:1/-1;"><dt style="color:#64748b;font-size:12px;">Preferred Start</dt><dd>{{ $request->preferredStartPayrollPeriod?->title ?: '-' }}</dd></div>
            <div style="grid-column:1/-1;"><dt style="color:#64748b;font-size:12px;">Reason</dt><dd style="white-space:pre-wrap;">{{ $request->reason }}</dd></div>
        </dl>
    </section>

    @if ($request->status !== \App\Models\EmployeeLoanRequest::STATUS_PENDING)
        <section style="border-top:1px solid rgba(148,163,184,.24);padding-top:16px;">
            <h3 style="font-size: 14px; font-weight: 800; margin-bottom: 10px;">HR Review</h3>
            <dl style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                <div><dt style="color:#64748b;font-size:12px;">Reviewed By</dt><dd>{{ $request->reviewedBy?->username ?: '-' }}</dd></div>
                <div><dt style="color:#64748b;font-size:12px;">Reviewed At</dt><dd>{{ $request->reviewed_at?->format('M d, Y h:i A') ?: '-' }}</dd></div>
                <div style="grid-column:1/-1;"><dt style="color:#64748b;font-size:12px;">HR Comment</dt><dd style="white-space:pre-wrap;">{{ $request->hr_comment ?: '-' }}</dd></div>
            </dl>
        </section>
    @endif

    @if ($approvedLoan)
        <section style="border-top:1px solid rgba(148,163,184,.24);padding-top:16px;">
            <h3 style="font-size: 14px; font-weight: 800; margin-bottom: 10px;">Approved Loan Terms</h3>
            <dl style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                <div><dt style="color:#64748b;font-size:12px;">Loan Amount</dt><dd>{{ $money($approvedLoan->loan_amount) }}</dd></div>
                <div><dt style="color:#64748b;font-size:12px;">Interest</dt><dd>{{ $money($approvedLoan->loan_interest) }}</dd></div>
                <div><dt style="color:#64748b;font-size:12px;">Payment</dt><dd>{{ $money($approvedLoan->payment_amount) }}</dd></div>
                <div><dt style="color:#64748b;font-size:12px;">Terms</dt><dd>{{ $approvedLoan->loan_terms_months }} period(s)</dd></div>
                <div><dt style="color:#64748b;font-size:12px;">Schedule</dt><dd>{{ $approvedLoan->schedule }}</dd></div>
                <div><dt style="color:#64748b;font-size:12px;">Amortization Start</dt><dd>{{ $approvedLoan->amortizationStartPayrollPeriod?->title ?: '-' }}</dd></div>
            </dl>
        </section>
    @endif
</div>

<style>
    @media (max-width: 640px) {
        .loan-request-details dl {
            grid-template-columns: 1fr !important;
        }

        .loan-request-details dl > div {
            grid-column: auto !important;
        }
    }
</style>
