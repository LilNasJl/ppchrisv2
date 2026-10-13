@php
    $rows = collect($rows ?? []);
    $money = fn ($value) => $value === null || $value === '' ? '' : number_format((float) $value, 2);
    $plainNumber = fn ($value) => $value === null || $value === '' ? '' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $sum = fn (string $key) => $money($rows->sum(fn ($row) => (float) ($row[$key] ?? 0)));
@endphp

<style>
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

    @media print {
        @page {
            size: landscape;
            margin: 0;
        }

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
            overflow: visible;
            border: 0;
        }

        .payroll-table {
            min-width: 0;
            table-layout: fixed;
            font-size: 5.5px;
            width: 100%;
            border-collapse: collapse;
        }

        .payroll-table th,
        .payroll-table td {
            background: #fff !important;
            border: 1px solid #000 !important;
            color: #000 !important;
            padding: 2px 3px;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .payroll-col-index,
        .payroll-col-bank-id,
        .payroll-col-name {
            position: static !important;
            box-shadow: none !important;
            border-right-color: #000 !important;
        }

        .payroll-table th {
            background: #f3f4f6 !important;
            color: #000 !important;
        }

        .payroll-table tfoot td {
            background: #f3f4f6 !important;
            color: #000 !important;
        }
    }
</style>

<div class="payroll-scroll">
    <table class="payroll-table">
        <thead>
            <tr>
                <th rowspan="2" class="payroll-col-index">#</th>
                <th rowspan="2" class="payroll-col-bank-id">Bank ID No.</th>
                <th rowspan="2" class="payroll-col-name">Name</th>
                <th rowspan="2">Designation</th>
                <th rowspan="2">Branch</th>
                <th rowspan="2">Rate</th>
                <th rowspan="2">Monthly Rate</th>
                <th rowspan="2">Half Month Pay</th>
                <th rowspan="2">Rate Per Day</th>
                <th rowspan="2">Rate Per Hour</th>
                <th rowspan="2">Days Work</th>
                <th rowspan="2">Salary Adjustment</th>
                <th rowspan="2">Allowance</th>
                <th colspan="2">Overtime</th>
                <th rowspan="2">10%</th>
                <th rowspan="2">Regular Holiday</th>
                <th rowspan="2">Special Holiday</th>
                <th rowspan="2">Gross Pay</th>
                <th colspan="10">Deductions</th>
                <th colspan="5">Remittances</th>
                <th rowspan="2">Total Deductions</th>
                <th rowspan="2">Net Pay</th>
                <th rowspan="2">Signature</th>
            </tr>
            <tr>
                <th>Hrs</th>
                <th>Amount</th>
                <th>Undertime Minutes</th>
                <th>Undertime Amount</th>
                <th>Halfday</th>
                <th>Absent</th>
                <th>Unpaid Leave</th>
                <th>Late</th>
                <th>Shortages</th>
                <th>Uniform</th>
                <th>Other Deductions</th>
                <th>Loan Payment</th>
                <th>SSS Loan</th>
                <th>SSS EE</th>
                <th>HDMF Loan</th>
                <th>HDMF EE</th>
                <th>PHIC EE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="text-center payroll-col-index">{{ $row['number'] }}</td>
                    <td class="payroll-col-bank-id">{{ $row['bank_id_no'] }}</td>
                    <td class="payroll-col-name font-semibold">{{ $row['name'] }}</td>
                    <td>{{ $row['designation'] }}</td>
                    <td>{{ $row['branch'] }}</td>
                    <td class="text-center">{{ $row['rate'] }}</td>
                    <td class="text-right">{{ $money($row['monthly_rate']) }}</td>
                    <td class="text-right">{{ $money($row['half_month_pay']) }}</td>
                    <td class="text-right">{{ $money($row['rate_per_day']) }}</td>
                    <td class="text-right">{{ $money($row['rate_per_hour']) }}</td>
                    <td class="text-right">{{ $plainNumber($row['days_worked']) }}</td>
                    <td class="text-right">{{ $money($row['salary_adjustment']) }}</td>
                    <td class="text-right">{{ $money($row['allowance']) }}</td>
                    <td class="text-right">{{ $plainNumber($row['overtime_hours']) }}</td>
                    <td class="text-right">{{ $money($row['overtime_amount']) }}</td>
                    <td class="text-right">{{ $money($row['shift3_premium'] ?? 0) }}</td>
                    <td class="text-right">{{ $money($row['regular_holiday']) }}</td>
                    <td class="text-right">{{ $money($row['special_holiday']) }}</td>
                    <td class="text-right">{{ $money($row['gross_pay']) }}</td>
                    <td class="text-right">{{ $plainNumber($row['undertime_minutes']) }}</td>
                    <td class="text-right">{{ $money($row['undertime_amount']) }}</td>
                    <td class="text-right">{{ $money($row['halfday']) }}</td>
                    <td class="text-right">{{ $money($row['absent']) }}</td>
                    <td class="text-right">{{ $money($row['unpaid_leave'] ?? 0) }}</td>
                    <td class="text-right">{{ $money($row['late']) }}</td>
                    <td class="text-right">{{ $money($row['shortages']) }}</td>
                    <td class="text-right">{{ $money($row['uniform']) }}</td>
                    <td class="text-right">{{ $money($row['other_deductions']) }}</td>
                    <td class="text-right">{{ $money($row['loan_payment'] ?? 0) }}</td>
                    <td class="text-right">{{ $money($row['sss_loan']) }}</td>
                    <td class="text-right">{{ $money($row['sss_ee']) }}</td>
                    <td class="text-right">{{ $money($row['hdmf_loan']) }}</td>
                    <td class="text-right">{{ $money($row['hdmf_ee']) }}</td>
                    <td class="text-right">{{ $money($row['phic_ee']) }}</td>
                    <td class="text-right">{{ $money($row['total_deductions']) }}</td>
                    <td class="text-right font-semibold">{{ $money($row['net_pay']) }}</td>
                    <td>{{ $row['signature'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="37" class="text-center">No payroll data available.</td>
                </tr>
            @endforelse
        </tbody>

        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td class="text-center payroll-col-index">TOTAL</td>
                    <td class="payroll-col-bank-id"></td>
                    <td class="payroll-col-name"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ $sum('monthly_rate') }}</td>
                    <td class="text-right">{{ $sum('half_month_pay') }}</td>
                    <td class="text-right">{{ $sum('rate_per_day') }}</td>
                    <td class="text-right">{{ $sum('rate_per_hour') }}</td>
                    <td class="text-right">{{ $plainNumber($rows->sum('days_worked')) }}</td>
                    <td class="text-right">{{ $sum('salary_adjustment') }}</td>
                    <td class="text-right">{{ $sum('allowance') }}</td>
                    <td class="text-right">{{ $plainNumber($rows->sum('overtime_hours')) }}</td>
                    <td class="text-right">{{ $sum('overtime_amount') }}</td>
                    <td class="text-right">{{ $sum('shift3_premium') }}</td>
                    <td class="text-right">{{ $sum('regular_holiday') }}</td>
                    <td class="text-right">{{ $sum('special_holiday') }}</td>
                    <td class="text-right">{{ $sum('gross_pay') }}</td>
                    <td class="text-right">{{ $plainNumber($rows->sum('undertime_minutes')) }}</td>
                    <td class="text-right">{{ $sum('undertime_amount') }}</td>
                    <td class="text-right">{{ $sum('halfday') }}</td>
                    <td class="text-right">{{ $sum('absent') }}</td>
                    <td class="text-right">{{ $sum('unpaid_leave') }}</td>
                    <td class="text-right">{{ $sum('late') }}</td>
                    <td class="text-right">{{ $sum('shortages') }}</td>
                    <td class="text-right">{{ $sum('uniform') }}</td>
                    <td class="text-right">{{ $sum('other_deductions') }}</td>
                    <td class="text-right">{{ $sum('loan_payment') }}</td>
                    <td class="text-right">{{ $sum('sss_loan') }}</td>
                    <td class="text-right">{{ $sum('sss_ee') }}</td>
                    <td class="text-right">{{ $sum('hdmf_loan') }}</td>
                    <td class="text-right">{{ $sum('hdmf_ee') }}</td>
                    <td class="text-right">{{ $sum('phic_ee') }}</td>
                    <td class="text-right">{{ $sum('total_deductions') }}</td>
                    <td class="text-right">{{ $sum('net_pay') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
