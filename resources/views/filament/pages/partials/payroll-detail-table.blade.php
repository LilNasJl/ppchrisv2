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
        min-width: 2220px;
        border-collapse: collapse;
        font-size: 12px;
    }

    .payroll-table th,
    .payroll-table td {
        border: 1px solid rgba(148, 163, 184, .35);
        padding: 7px 8px;
        vertical-align: middle;
        white-space: nowrap;
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
    }

    .payroll-table td {
        color: rgb(15, 23, 42);
    }

    .dark .payroll-table td {
        color: #e5e7eb;
    }

    .payroll-table .text-right {
        text-align: right;
    }

    .payroll-table .text-center {
        text-align: center;
    }

    .payroll-table tfoot td {
        background: rgb(241, 245, 249);
        color: rgb(15, 23, 42);
        font-weight: 700;
    }

    .dark .payroll-table tfoot td {
        background: rgb(30, 41, 59);
        color: #f8fafc;
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
        }

        .payroll-table th,
        .payroll-table td {
            background: #fff !important;
            border-color: #000 !important;
            color: #000 !important;
            padding: 2px 3px;
            white-space: normal;
            overflow-wrap: anywhere;
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
                <th rowspan="2">#</th>
                <th rowspan="2">Bank ID No.</th>
                <th rowspan="2">Name</th>
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
                <th rowspan="2">Regular Holiday</th>
                <th rowspan="2">Special Holiday</th>
                <th rowspan="2">Gross Pay</th>
                <th colspan="9">Deductions</th>
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
                    <td class="text-center">{{ $row['number'] }}</td>
                    <td>{{ $row['bank_id_no'] }}</td>
                    <td>{{ $row['name'] }}</td>
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
                    <td class="text-right">{{ $money($row['regular_holiday']) }}</td>
                    <td class="text-right">{{ $money($row['special_holiday']) }}</td>
                    <td class="text-right">{{ $money($row['gross_pay']) }}</td>
                    <td class="text-right">{{ $plainNumber($row['undertime_minutes']) }}</td>
                    <td class="text-right">{{ $money($row['undertime_amount']) }}</td>
                    <td class="text-right">{{ $money($row['halfday']) }}</td>
                    <td class="text-right">{{ $money($row['absent']) }}</td>
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
                    <td class="text-right">{{ $money($row['net_pay']) }}</td>
                    <td>{{ $row['signature'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="35" class="text-center">No payroll data available.</td>
                </tr>
            @endforelse
        </tbody>

        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="10">TOTAL</td>
                    <td class="text-right">{{ $plainNumber($rows->sum('days_worked')) }}</td>
                    <td class="text-right">{{ $sum('salary_adjustment') }}</td>
                    <td class="text-right">{{ $sum('allowance') }}</td>
                    <td class="text-right">{{ $plainNumber($rows->sum('overtime_hours')) }}</td>
                    <td class="text-right">{{ $sum('overtime_amount') }}</td>
                    <td class="text-right">{{ $sum('regular_holiday') }}</td>
                    <td class="text-right">{{ $sum('special_holiday') }}</td>
                    <td class="text-right">{{ $sum('gross_pay') }}</td>
                    <td class="text-right">{{ $plainNumber($rows->sum('undertime_minutes')) }}</td>
                    <td class="text-right">{{ $sum('undertime_amount') }}</td>
                    <td class="text-right">{{ $sum('halfday') }}</td>
                    <td class="text-right">{{ $sum('absent') }}</td>
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
