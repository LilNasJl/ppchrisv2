@php
    $rows = collect($rows ?? []);
    $loanTypeColumns = $loanTypeColumns ?? null;
    $money = fn ($value) => $value === null || $value === '' ? '' : number_format((float) $value, 2);
    $plainNumber = fn ($value) => $value === null || $value === '' ? '' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $sum = fn (string $key) => $money($rows->sum(fn ($row) => (float) ($row[$key] ?? 0)));

    // Dynamic content-based column weighting
    // Matches column widths to actual content length so there is no wasted whitespace or compressed overflowing text
    $maxLen = fn (string $key, int $min = 0): int => max($min, (int) $rows->max(fn ($r) => mb_strlen((string) ($r[$key] ?? ''))));

    $longestName = $maxLen('name', 12);
    $longestDesig = $maxLen('designation', 10);
    $longestBranch = $maxLen('branch', 6);
    $longestBankId = $maxLen('bank_id_no', 8);

    $weights = [
        'idx' => 4.5,
        'bank_id' => max(8.5, min(12.5, $longestBankId * 0.9)),
        'name' => max(16.0, min(36.0, $longestName * 0.92)),
        'designation' => max(11.0, min(18.0, $longestDesig * 0.72)),
        'branch' => max(8.5, min(12.0, $longestBranch * 0.85)),
        'rate' => 8.0,
        'monthly_rate' => 9.5,
        'rate_day' => 8.0,
        'rate_hr' => 7.5,
        'days_work' => 6.0,
        'sal_adj' => 7.5,
        'allowance' => 7.5,
        'ot_hrs' => 5.5,
        'ot_amt' => 7.5,
        'shift3' => 6.0,
        'reg_hol' => 7.5,
        'spec_hol' => 7.5,
        'gross' => 10.5,
        'undertime_amt' => 7.5,
        'halfday' => 6.5,
        'absent' => 6.5,
        'late' => 6.5,
        'shortages' => 6.5,
        'uniform' => 6.5,
        'other_ded' => 7.5,
    ];

    if ($loanTypeColumns === null) {
        $weights['loan_single'] = 7.5;
    } else {
        foreach ($loanTypeColumns as $id => $name) {
            $weights['loan_' . $id] = 7.0;
        }
    }

    $weights['sss_loan'] = 7.0;
    $weights['sss_ee'] = 7.0;
    $weights['hdmf_loan'] = 7.0;
    $weights['hdmf_ee'] = 7.0;
    $weights['phic_ee'] = 7.0;
    $weights['total_ded'] = 10.5;
    $weights['net'] = 11.0;
    $weights['signature'] = 9.5;

    $totalWeight = max(1.0, array_sum($weights));
    $colWidth = fn (string $key): string => number_format((($weights[$key] ?? 7.0) / $totalWeight) * 100, 2);
@endphp

<style>
    @media screen {
        .payroll-scroll {
            overflow-x: auto;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 8px;
        }

        .payroll-table {
            width: 100%;
            min-width: 2280px;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
            font-variant-numeric: tabular-nums;
        }

        .payroll-table th,
        .payroll-table td {
            border-bottom: 1px solid rgba(148, 163, 184, .35);
            border-right: 1px solid rgba(148, 163, 184, .35);
            padding: 7px 8px;
            vertical-align: middle;
            white-space: nowrap;
        }

        .payroll-table thead tr:first-child th {
            border-top: 1px solid rgba(148, 163, 184, .35);
        }

        .payroll-table th {
            background: rgb(248, 250, 252);
            color: rgb(15, 23, 42);
            font-weight: 700;
            text-align: center;
        }

        .dark .payroll-table th {
            background: rgb(31, 41, 55);
            color: #f8fafc;
            border-color: rgba(71, 85, 105, .45);
        }

        .payroll-table td {
            color: rgb(15, 23, 42);
        }

        .dark .payroll-table td {
            color: #e5e7eb;
            border-color: rgba(71, 85, 105, .45);
        }

        .payroll-table tbody tr:nth-child(odd) td {
            background: rgb(255, 255, 255);
        }

        .payroll-table tbody tr:nth-child(even) td {
            background: rgb(248, 250, 252);
        }

        .dark .payroll-table tbody tr:nth-child(odd) td {
            background: rgb(15, 23, 42);
        }

        .dark .payroll-table tbody tr:nth-child(even) td {
            background: rgb(30, 41, 59);
        }

        .payroll-table tbody tr:hover td {
            background: rgba(59, 130, 246, .08);
        }

        .dark .payroll-table tbody tr:hover td {
            background: rgba(96, 165, 250, .16);
        }

        .payroll-table .text-right {
            text-align: right;
        }

        .payroll-table .text-center {
            text-align: center;
        }

        .payroll-table tfoot td {
            background: rgb(219, 234, 254);
            border-top: 2px solid rgb(37, 99, 235);
            color: rgb(15, 23, 42);
            font-weight: 700;
        }

        .dark .payroll-table tfoot td {
            background: rgb(30, 58, 138);
            border-top-color: rgb(96, 165, 250);
            color: #f8fafc;
        }

        /* Sticky Columns (1: Index, 2: Bank ID, 3: Name) */
        .payroll-col-index {
            position: sticky !important;
            left: 0 !important;
            inset-inline-start: 0 !important;
            width: 2.75rem !important;
            min-width: 2.75rem !important;
            max-width: 2.75rem !important;
            text-align: center !important;
            border-right: 1px solid rgba(203, 213, 225, 0.95) !important;
            z-index: 5;
        }

        .payroll-col-bank-id {
            position: sticky !important;
            left: 2.75rem !important;
            inset-inline-start: 2.75rem !important;
            width: 6.5rem !important;
            min-width: 6.5rem !important;
            max-width: 6.5rem !important;
            border-right: 1px solid rgba(203, 213, 225, 0.95) !important;
            z-index: 5;
        }

        .payroll-col-name {
            position: sticky !important;
            left: 9.25rem !important;
            inset-inline-start: 9.25rem !important;
            width: 13rem !important;
            min-width: 13rem !important;
            max-width: 13rem !important;
            border-right: 2px solid rgba(148, 163, 184, 0.85) !important;
            box-shadow: 4px 0 8px -2px rgba(0, 0, 0, 0.2) !important;
            z-index: 5;
        }

        .dark .payroll-col-index,
        .dark .payroll-col-bank-id {
            border-right: 1px solid rgba(51, 65, 85, 0.95) !important;
        }

        .dark .payroll-col-name {
            border-right: 2px solid rgba(71, 85, 105, 0.95) !important;
            box-shadow: 4px 0 10px -2px rgba(0, 0, 0, 0.5) !important;
        }

        /* Opaque backgrounds for sticky headers to prevent scrolled text bleed-through */
        .payroll-table thead th.payroll-col-index,
        .payroll-table thead th.payroll-col-bank-id,
        .payroll-table thead th.payroll-col-name {
            background: rgb(241, 245, 249) !important;
            z-index: 10 !important;
        }

        .dark .payroll-table thead th.payroll-col-index,
        .dark .payroll-table thead th.payroll-col-bank-id,
        .dark .payroll-table thead th.payroll-col-name {
            background: rgb(30, 41, 59) !important;
            z-index: 10 !important;
        }

        /* Opaque backgrounds for sticky body cells to prevent scrolled text bleed-through */
        .payroll-table tbody tr:nth-child(odd) td.payroll-col-index,
        .payroll-table tbody tr:nth-child(odd) td.payroll-col-bank-id,
        .payroll-table tbody tr:nth-child(odd) td.payroll-col-name {
            background: #ffffff !important;
        }

        .payroll-table tbody tr:nth-child(even) td.payroll-col-index,
        .payroll-table tbody tr:nth-child(even) td.payroll-col-bank-id,
        .payroll-table tbody tr:nth-child(even) td.payroll-col-name {
            background: #f8fafc !important;
        }

        .dark .payroll-table tbody tr:nth-child(odd) td.payroll-col-index,
        .dark .payroll-table tbody tr:nth-child(odd) td.payroll-col-bank-id,
        .dark .payroll-table tbody tr:nth-child(odd) td.payroll-col-name {
            background: rgb(15, 23, 42) !important;
        }

        .dark .payroll-table tbody tr:nth-child(even) td.payroll-col-index,
        .dark .payroll-table tbody tr:nth-child(even) td.payroll-col-bank-id,
        .dark .payroll-table tbody tr:nth-child(even) td.payroll-col-name {
            background: rgb(30, 41, 59) !important;
        }

        /* Opaque backgrounds on hover for sticky cells */
        .payroll-table tbody tr:hover td.payroll-col-index,
        .payroll-table tbody tr:hover td.payroll-col-bank-id,
        .payroll-table tbody tr:hover td.payroll-col-name {
            background: rgb(239, 246, 255) !important;
        }

        .dark .payroll-table tbody tr:hover td.payroll-col-index,
        .dark .payroll-table tbody tr:hover td.payroll-col-bank-id,
        .dark .payroll-table tbody tr:hover td.payroll-col-name {
            background: rgb(30, 58, 138) !important;
        }

        /* Opaque backgrounds on summary/total row for sticky cells */
        .payroll-table tfoot td.payroll-col-index,
        .payroll-table tfoot td.payroll-col-bank-id,
        .payroll-table tfoot td.payroll-col-name {
            background: rgb(219, 234, 254) !important;
            z-index: 8 !important;
        }

        .dark .payroll-table tfoot td.payroll-col-index,
        .dark .payroll-table tfoot td.payroll-col-bank-id,
        .dark .payroll-table tfoot td.payroll-col-name {
            background: rgb(30, 58, 138) !important;
            z-index: 8 !important;
        }

        /* Screen preview styling inside print document containers */
        .payroll-document .payroll-scroll,
        .payroll-print-table .payroll-scroll {
            overflow: visible !important;
            border: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .payroll-document .payroll-table,
        .payroll-print-table .payroll-table {
            min-width: 0 !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            font-family: 'Arial Narrow', Arial, sans-serif !important;
            font-size: 5pt !important;
            line-height: 1.1 !important;
        }

        .payroll-document .payroll-table th,
        .payroll-document .payroll-table td,
        .payroll-print-table .payroll-table th,
        .payroll-print-table .payroll-table td {
            background: #fff !important;
            border: 0.5pt solid #444 !important;
            color: #000 !important;
            padding: 1.5px 1px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            text-overflow: clip !important;
            font-size: 5pt !important;
        }

        .payroll-document .payroll-table th,
        .payroll-print-table .payroll-table th {
            background: #f1f5f9 !important;
            font-weight: 700 !important;
            text-align: center !important;
            line-height: 1.05 !important;
            word-break: normal !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
        }

        .payroll-document .payroll-col-index,
        .payroll-document .payroll-col-bank-id,
        .payroll-document .payroll-col-name,
        .payroll-print-table .payroll-col-index,
        .payroll-print-table .payroll-col-bank-id,
        .payroll-print-table .payroll-col-name {
            position: static !important;
            inset-inline-start: auto !important;
            left: auto !important;
            width: auto !important;
            min-width: 0 !important;
            max-width: none !important;
            box-shadow: none !important;
            border-right: 0.5pt solid #444 !important;
        }

        .payroll-document .payroll-table td.text-right,
        .payroll-print-table .payroll-table td.text-right {
            text-align: right !important;
            white-space: nowrap !important;
            font-variant-numeric: tabular-nums !important;
        }

        .payroll-document .payroll-table td.text-center,
        .payroll-print-table .payroll-table td.text-center {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-document .payroll-table td.col-name,
        .payroll-print-table .payroll-table td.col-name {
            text-align: left !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: clip !important;
        }

        .payroll-document .payroll-table td.col-rate,
        .payroll-print-table .payroll-table td.col-rate {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-document .payroll-table td.col-bank-id,
        .payroll-print-table .payroll-table td.col-bank-id {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-document .payroll-table td.col-branch,
        .payroll-print-table .payroll-table td.col-branch {
            text-align: left !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: clip !important;
        }

        .payroll-document .payroll-table td.col-designation,
        .payroll-print-table .payroll-table td.col-designation {
            text-align: left !important;
            white-space: normal !important;
            word-break: normal !important;
            overflow-wrap: break-word !important;
            line-height: 1.05 !important;
        }

        .payroll-document .payroll-table tfoot td,
        .payroll-print-table .payroll-table tfoot td {
            background: #f8fafc !important;
            font-weight: 700 !important;
            border-top: 1.5pt solid #000 !important;
        }
    }

    @media print {
        body {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .fi-sidebar,
        .fi-topbar,
        .fi-header,
        form,
        .fi-ac {
            display: none !important;
        }

        .payroll-scroll {
            overflow: visible !important;
            border: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .payroll-table {
            min-width: 0 !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            font-family: 'Arial Narrow', Arial, sans-serif !important;
            font-size: 5pt !important;
            line-height: 1.1 !important;
        }

        .payroll-table thead {
            display: table-header-group !important;
        }

        .payroll-table tfoot {
            display: table-footer-group !important;
        }

        .payroll-table tr {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .payroll-table th,
        .payroll-table td {
            background: #fff !important;
            border: 0.5pt solid #444 !important;
            color: #000 !important;
            padding: 1.5px 1px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            text-overflow: clip !important;
        }

        .payroll-table th {
            background: #f1f5f9 !important;
            font-size: 5pt !important;
            font-weight: 700 !important;
            text-align: center !important;
            line-height: 1.05 !important;
            word-break: normal !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
        }

        .payroll-col-index,
        .payroll-col-bank-id,
        .payroll-col-name {
            position: static !important;
            inset-inline-start: auto !important;
            left: auto !important;
            width: auto !important;
            min-width: 0 !important;
            max-width: none !important;
            box-shadow: none !important;
            border-right: 0.5pt solid #444 !important;
        }

        .payroll-table td {
            font-size: 5pt !important;
        }

        .payroll-table td.text-right {
            text-align: right !important;
            white-space: nowrap !important;
            font-variant-numeric: tabular-nums !important;
        }

        .payroll-table td.text-center {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-table td.col-name {
            text-align: left !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: clip !important;
        }

        .payroll-table td.col-rate {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-table td.col-bank-id {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-table td.col-branch {
            text-align: left !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: clip !important;
        }

        .payroll-table td.col-designation {
            text-align: left !important;
            white-space: normal !important;
            word-break: normal !important;
            overflow-wrap: break-word !important;
            line-height: 1.05 !important;
        }

        .payroll-table tfoot td {
            background: #f8fafc !important;
            font-weight: 700 !important;
            border-top: 1.5pt solid #000 !important;
        }
    }
</style>

<div class="payroll-scroll">
    <table class="payroll-table">
        <colgroup>
            <col class="col-idx" style="width: {{ $colWidth('idx') }}%;">
            <col class="col-bank-id" style="width: {{ $colWidth('bank_id') }}%;">
            <col class="col-name" style="width: {{ $colWidth('name') }}%;">
            <col class="col-designation" style="width: {{ $colWidth('designation') }}%;">
            <col class="col-branch" style="width: {{ $colWidth('branch') }}%;">
            <col class="col-rate" style="width: {{ $colWidth('rate') }}%;">
            <col class="col-monthly-rate" style="width: {{ $colWidth('monthly_rate') }}%;">
            <col class="col-rate-day" style="width: {{ $colWidth('rate_day') }}%;">
            <col class="col-rate-hr" style="width: {{ $colWidth('rate_hr') }}%;">
            <col class="col-days-work" style="width: {{ $colWidth('days_work') }}%;">
            <col class="col-sal-adj" style="width: {{ $colWidth('sal_adj') }}%;">
            <col class="col-allowance" style="width: {{ $colWidth('allowance') }}%;">
            <col class="col-ot-hrs" style="width: {{ $colWidth('ot_hrs') }}%;">
            <col class="col-ot-amt" style="width: {{ $colWidth('ot_amt') }}%;">
            <col class="col-shift3" style="width: {{ $colWidth('shift3') }}%;">
            <col class="col-reg-hol" style="width: {{ $colWidth('reg_hol') }}%;">
            <col class="col-spec-hol" style="width: {{ $colWidth('spec_hol') }}%;">
            <col class="col-gross" style="width: {{ $colWidth('gross') }}%;">
            <col class="col-undertime-amt" style="width: {{ $colWidth('undertime_amt') }}%;">
            <col class="col-halfday" style="width: {{ $colWidth('halfday') }}%;">
            <col class="col-absent" style="width: {{ $colWidth('absent') }}%;">
            <col class="col-late" style="width: {{ $colWidth('late') }}%;">
            <col class="col-shortages" style="width: {{ $colWidth('shortages') }}%;">
            <col class="col-uniform" style="width: {{ $colWidth('uniform') }}%;">
            <col class="col-other-ded" style="width: {{ $colWidth('other_ded') }}%;">
            @if ($loanTypeColumns === null)
                <col class="col-loan" style="width: {{ $colWidth('loan_single') }}%;">
            @else
                @foreach ($loanTypeColumns as $id => $name)
                    <col class="col-loan" style="width: {{ $colWidth('loan_' . $id) }}%;">
                @endforeach
            @endif
            <col class="col-remittance" style="width: {{ $colWidth('sss_loan') }}%;">
            <col class="col-remittance" style="width: {{ $colWidth('sss_ee') }}%;">
            <col class="col-remittance" style="width: {{ $colWidth('hdmf_loan') }}%;">
            <col class="col-remittance" style="width: {{ $colWidth('hdmf_ee') }}%;">
            <col class="col-remittance" style="width: {{ $colWidth('phic_ee') }}%;">
            <col class="col-total-ded" style="width: {{ $colWidth('total_ded') }}%;">
            <col class="col-net" style="width: {{ $colWidth('net') }}%;">
            <col class="col-signature" style="width: {{ $colWidth('signature') }}%;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2" class="payroll-col-index col-idx">#</th>
                <th rowspan="2" class="payroll-col-bank-id col-bank-id">Bank ID No.</th>
                <th rowspan="2" class="payroll-col-name col-name">Name</th>
                <th rowspan="2" class="col-designation">Designation</th>
                <th rowspan="2" class="col-branch">Branch</th>
                <th rowspan="2" class="col-rate">Rate</th>
                <th rowspan="2" class="col-monthly-rate">Monthly Rate</th>
                <th rowspan="2" class="col-rate-day">Rate Per Day</th>
                <th rowspan="2" class="col-rate-hr">Rate Per Hour</th>
                <th rowspan="2" class="col-days-work">Days Work</th>
                <th rowspan="2" class="col-sal-adj">Salary Adjustment</th>
                <th rowspan="2" class="col-allowance">Allowance</th>
                <th colspan="2">Overtime</th>
                <th rowspan="2" class="col-shift3">10%</th>
                <th rowspan="2" class="col-reg-hol">Regular Holiday</th>
                <th rowspan="2" class="col-spec-hol">Special Holiday</th>
                <th rowspan="2" class="col-gross">Gross Pay</th>
                <th colspan="{{ 7 + ($loanTypeColumns === null ? 1 : count($loanTypeColumns)) }}">Deductions</th>
                <th colspan="5">Remittances</th>
                <th rowspan="2" class="col-total-ded">Total Deductions</th>
                <th rowspan="2" class="col-net">Net Pay</th>
                <th rowspan="2" class="col-signature">Signature</th>
            </tr>
            <tr>
                <th class="col-ot-hrs">Hrs</th>
                <th class="col-ot-amt">Amount</th>
                <th class="col-undertime-amt">Undertime Amount</th>
                <th class="col-halfday">Halfday</th>
                <th class="col-absent">Absent</th>
                <th class="col-late">Late</th>
                <th class="col-shortages">Shortages</th>
                <th class="col-uniform">Uniform</th>
                <th class="col-other-ded">Other Deductions</th>
                @if ($loanTypeColumns === null)
                    <th>Loan Payment</th>
                @else
                    @foreach ($loanTypeColumns as $name)
                        <th>{{ $name }}</th>
                    @endforeach
                @endif
                <th class="col-remittance">SSS Loan</th>
                <th class="col-remittance">SSS EE</th>
                <th class="col-remittance">HDMF Loan</th>
                <th class="col-remittance">HDMF EE</th>
                <th class="col-remittance">PHIC EE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="text-center payroll-col-index col-idx">{{ $row['number'] }}</td>
                    <td class="payroll-col-bank-id col-bank-id">{{ $row['bank_id_no'] }}</td>
                    <td class="payroll-col-name col-name font-semibold">{{ $row['name'] }}</td>
                    <td class="col-designation">{{ $row['designation'] }}</td>
                    <td class="col-branch">{{ $row['branch'] }}</td>
                    <td class="text-center col-rate">{{ $row['rate'] }}</td>
                    <td class="text-right col-monthly-rate">{{ $money($row['monthly_rate']) }}</td>
                    <td class="text-right col-rate-day">{{ $money($row['rate_per_day']) }}</td>
                    <td class="text-right col-rate-hr">{{ $money($row['rate_per_hour']) }}</td>
                    <td class="text-right col-days-work">{{ $plainNumber($row['days_worked']) }}</td>
                    <td class="text-right col-sal-adj">{{ $money($row['salary_adjustment']) }}</td>
                    <td class="text-right col-allowance">{{ $money($row['allowance']) }}</td>
                    <td class="text-right col-ot-hrs">{{ $plainNumber($row['overtime_hours']) }}</td>
                    <td class="text-right col-ot-amt">{{ $money($row['overtime_amount']) }}</td>
                    <td class="text-right col-shift3">{{ $money($row['shift3_premium'] ?? 0) }}</td>
                    <td class="text-right col-reg-hol">{{ $money($row['regular_holiday']) }}</td>
                    <td class="text-right col-spec-hol">{{ $money($row['special_holiday']) }}</td>
                    <td class="text-right col-gross font-semibold">{{ $money($row['gross_pay']) }}</td>
                    <td class="text-right col-undertime-amt">{{ $money($row['undertime_amount']) }}</td>
                    <td class="text-right col-halfday">{{ $money($row['halfday']) }}</td>
                    <td class="text-right col-absent">{{ $money($row['absent']) }}</td>
                    <td class="text-right col-late">{{ $money($row['late']) }}</td>
                    <td class="text-right col-shortages">{{ $money($row['shortages']) }}</td>
                    <td class="text-right col-uniform">{{ $money($row['uniform']) }}</td>
                    <td class="text-right col-other-ded">{{ $money($row['other_deductions']) }}</td>
                    @if ($loanTypeColumns === null)
                        <td class="text-right col-loan">{{ $money($row['loan_payment'] ?? 0) }}</td>
                    @else
                        @foreach ($loanTypeColumns as $id => $name)
                            <td class="text-right col-loan">{{ $money($row['loan_type_'.$id] ?? 0) }}</td>
                        @endforeach
                    @endif
                    <td class="text-right col-remittance">{{ $money($row['sss_loan']) }}</td>
                    <td class="text-right col-remittance">{{ $money($row['sss_ee']) }}</td>
                    <td class="text-right col-remittance">{{ $money($row['hdmf_loan']) }}</td>
                    <td class="text-right col-remittance">{{ $money($row['hdmf_ee']) }}</td>
                    <td class="text-right col-remittance">{{ $money($row['phic_ee']) }}</td>
                    <td class="text-right col-total-ded font-semibold">{{ $money($row['total_deductions']) }}</td>
                    <td class="text-right col-net font-semibold">{{ $money($row['net_pay']) }}</td>
                    <td class="col-signature">{{ $row['signature'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 33 + ($loanTypeColumns === null ? 1 : count($loanTypeColumns)) }}" class="text-center">No payroll data available.</td>
                </tr>
            @endforelse
        </tbody>

        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td class="text-center payroll-col-index col-idx">TOTAL</td>
                    <td class="payroll-col-bank-id col-bank-id"></td>
                    <td class="payroll-col-name col-name"></td>
                    <td class="col-designation"></td>
                    <td class="col-branch"></td>
                    <td class="col-rate"></td>
                    <td class="text-right col-monthly-rate">{{ $sum('monthly_rate') }}</td>
                    <td class="text-right col-rate-day">{{ $sum('rate_per_day') }}</td>
                    <td class="text-right col-rate-hr">{{ $sum('rate_per_hour') }}</td>
                    <td class="text-right col-days-work">{{ $plainNumber($rows->sum('days_worked')) }}</td>
                    <td class="text-right col-sal-adj">{{ $sum('salary_adjustment') }}</td>
                    <td class="text-right col-allowance">{{ $sum('allowance') }}</td>
                    <td class="text-right col-ot-hrs">{{ $plainNumber($rows->sum('overtime_hours')) }}</td>
                    <td class="text-right col-ot-amt">{{ $sum('overtime_amount') }}</td>
                    <td class="text-right col-shift3">{{ $sum('shift3_premium') }}</td>
                    <td class="text-right col-reg-hol">{{ $sum('regular_holiday') }}</td>
                    <td class="text-right col-spec-hol">{{ $sum('special_holiday') }}</td>
                    <td class="text-right col-gross">{{ $sum('gross_pay') }}</td>
                    <td class="text-right col-undertime-amt">{{ $sum('undertime_amount') }}</td>
                    <td class="text-right col-halfday">{{ $sum('halfday') }}</td>
                    <td class="text-right col-absent">{{ $sum('absent') }}</td>
                    <td class="text-right col-late">{{ $sum('late') }}</td>
                    <td class="text-right col-shortages">{{ $sum('shortages') }}</td>
                    <td class="text-right col-uniform">{{ $sum('uniform') }}</td>
                    <td class="text-right col-other-ded">{{ $sum('other_deductions') }}</td>
                    @if ($loanTypeColumns === null)
                        <td class="text-right col-loan">{{ $sum('loan_payment') }}</td>
                    @else
                        @foreach ($loanTypeColumns as $id => $name)
                            <td class="text-right col-loan">{{ $sum('loan_type_'.$id) }}</td>
                        @endforeach
                    @endif
                    <td class="text-right col-remittance">{{ $sum('sss_loan') }}</td>
                    <td class="text-right col-remittance">{{ $sum('sss_ee') }}</td>
                    <td class="text-right col-remittance">{{ $sum('hdmf_loan') }}</td>
                    <td class="text-right col-remittance">{{ $sum('hdmf_ee') }}</td>
                    <td class="text-right col-remittance">{{ $sum('phic_ee') }}</td>
                    <td class="text-right col-total-ded">{{ $sum('total_deductions') }}</td>
                    <td class="text-right col-net">{{ $sum('net_pay') }}</td>
                    <td class="col-signature"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
