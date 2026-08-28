<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>&#8203;</title>

    <style>
        :root {
            color-scheme: light only;
        }

        @page {
            margin: 0;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            background: #e5e7eb;
            color: #0f172a;
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.35;
        }

        .print-toolbar {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            padding: 10px 16px;
        }

        .print-toolbar button {
            background: #ffffff;
            border: 1px solid #94a3b8;
            border-radius: 6px;
            color: #0f172a;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 7px 12px;
        }

        .print-toolbar .primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        .payslip {
            background: #ffffff;
            color: #0f172a;
            margin: 16px auto;
            min-height: 297mm;
            padding: 12mm;
            width: 210mm;
        }

        .payslip-header {
            align-items: center;
            border-bottom: 2px solid #2563eb;
            display: grid;
            gap: 14px;
            grid-template-columns: 84px 1fr auto;
            padding-bottom: 12px;
        }

        .company-logo {
            height: 58px;
            object-fit: contain;
            width: 82px;
        }

        .company-name {
            color: #1e3a8a;
            font-size: 17px;
            font-weight: 800;
            line-height: 1.2;
        }

        .company-address {
            color: #334155;
            font-size: 9px;
            line-height: 1.4;
            margin-top: 3px;
        }

        .payslip-title {
            color: #1e3a8a;
            font-size: 23px;
            font-weight: 850;
            letter-spacing: 0;
            text-align: right;
        }

        .information-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: 1.25fr 1fr;
            margin-top: 14px;
        }

        .information-block,
        .rate-block {
            border: 1px solid #cbd5e1;
        }

        .section-title {
            background: #2563eb;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            padding: 5px 7px;
            text-transform: uppercase;
        }

        .information-content {
            display: grid;
            gap: 5px 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 8px;
        }

        .information-item {
            min-width: 0;
        }

        .information-label {
            color: #64748b;
            display: block;
            font-size: 8px;
            font-weight: 700;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .information-value {
            color: #0f172a;
            display: block;
            font-size: 9.5px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .rate-block {
            margin-top: 12px;
        }

        .rate-grid {
            display: grid;
            gap: 0;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .rate-item {
            border-right: 1px solid #e2e8f0;
            min-width: 0;
            padding: 7px;
            text-align: center;
        }

        .rate-item:last-child {
            border-right: 0;
        }

        .rate-label {
            color: #64748b;
            display: block;
            font-size: 7.5px;
            font-weight: 700;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .rate-value {
            color: #0f172a;
            display: block;
            font-size: 9px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .pay-section {
            margin-top: 12px;
        }

        .pay-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #cbd5e1;
            color: #0f172a;
            padding: 4px 6px;
        }

        th {
            background: #e2e8f0;
            font-size: 8px;
            font-weight: 800;
            text-align: left;
            text-transform: uppercase;
        }

        th:last-child,
        td:last-child {
            text-align: right;
        }

        td {
            font-size: 8.5px;
        }

        td.basis {
            color: #64748b;
            text-align: center;
        }

        .table-total td {
            background: #e2e8f0;
            border-bottom: 0;
            font-size: 9px;
            font-weight: 850;
        }

        .totals {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 14px;
        }

        .total-item {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-right: 0;
            padding: 9px 11px;
        }

        .total-item:last-child {
            background: #dbeafe;
            border-right: 1px solid #cbd5e1;
        }

        .total-label {
            color: #475569;
            display: block;
            font-size: 8px;
            font-weight: 750;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .total-value {
            color: #0f172a;
            display: block;
            font-size: 13px;
            font-weight: 850;
            text-align: right;
        }

        .total-item:last-child .total-value {
            color: #1d4ed8;
        }

        .payslip-footer {
            color: #64748b;
            font-size: 8px;
            margin-top: 14px;
            text-align: right;
        }

        @media (max-width: 850px) {
            .payslip {
                margin: 0;
                min-height: 0;
                padding: 18px;
                width: 100%;
            }
        }

        @media print {
            html,
            body {
                background: #ffffff !important;
                color: #000000 !important;
                height: auto;
                overflow: visible;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .print-toolbar {
                display: none !important;
            }

            .payslip {
                margin: 0;
                min-height: 0;
                padding: 12mm;
                width: 100%;
            }

            .pay-section,
            .information-grid,
            .rate-block,
            .totals {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @php
        $money = static fn (mixed $value): string => number_format((float) ($value ?? 0), 2);
        $number = static fn (mixed $value): string => rtrim(rtrim(number_format((float) ($value ?? 0), 2, '.', ''), '0'), '.');
        $basePay = ($row['rate'] ?? null) === 'Daily'
            ? ($row['total_pay'] ?? 0)
            : ($row['half_month_pay'] ?? 0);
        $employeeId = \App\Models\Employee::companyIdFromUid($employee->uid) ?? '-';
        $companyAddress = 'Prk. 28-C, Kawayanan, Timog, Madaum, Tagum City, Davao del Norte';
    @endphp

    <div class="print-toolbar">
        <button type="button" onclick="window.close()">Close</button>
        <button type="button" class="primary" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="payslip">
        <header class="payslip-header">
            @if ($logo = \App\Support\CompanyExportHeader::logoDataUri())
                <img src="{{ $logo }}" alt="PhilFumes logo" class="company-logo">
            @else
                <div></div>
            @endif

            <div>
                <div class="company-name">PhilFumes Petroleum Corporation</div>
                <div class="company-address">{{ $companyAddress }}</div>
            </div>

            <div class="payslip-title">PAYSLIP</div>
        </header>

        <section class="information-grid">
            <div class="information-block">
                <div class="section-title">Employee Information</div>
                <div class="information-content">
                    <div class="information-item">
                        <span class="information-label">Employee ID</span>
                        <span class="information-value">{{ $employeeId }}</span>
                    </div>
                    <div class="information-item">
                        <span class="information-label">Employee Name</span>
                        <span class="information-value">{{ $employee->full_name }}</span>
                    </div>
                    <div class="information-item">
                        <span class="information-label">Designation</span>
                        <span class="information-value">{{ $employee->designation?->title ?: '-' }}</span>
                    </div>
                    <div class="information-item">
                        <span class="information-label">Department</span>
                        <span class="information-value">{{ $employee->department?->name ?: '-' }}</span>
                    </div>
                    <div class="information-item">
                        <span class="information-label">Branch</span>
                        <span class="information-value">{{ $employee->branch?->branch_name ?: '-' }}</span>
                    </div>
                    <div class="information-item">
                        <span class="information-label">Payment Method</span>
                        <span class="information-value">{{ filled($row['payment_type'] ?? null) ? strtoupper($row['payment_type']) : '-' }}</span>
                    </div>
                    @if (filled($row['bank_id_no'] ?? null))
                        <div class="information-item">
                            <span class="information-label">Bank ID No.</span>
                            <span class="information-value">{{ $row['bank_id_no'] }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="information-block">
                <div class="section-title">Payroll Period</div>
                <div class="information-content">
                    <div class="information-item" style="grid-column: 1 / -1;">
                        <span class="information-label">Period</span>
                        <span class="information-value">{{ $period->title }}</span>
                    </div>
                    <div class="information-item">
                        <span class="information-label">Date Start</span>
                        <span class="information-value">{{ $period->date_start?->format('M d, Y') ?: '-' }}</span>
                    </div>
                    <div class="information-item">
                        <span class="information-label">Date End</span>
                        <span class="information-value">{{ $period->date_end?->format('M d, Y') ?: '-' }}</span>
                    </div>
                    <div class="information-item">
                        <span class="information-label">Payout Date</span>
                        <span class="information-value">{{ $period->date_payout?->format('M d, Y') ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="rate-block">
            <div class="section-title">Rate &amp; Attendance</div>
            <div class="rate-grid">
                <div class="rate-item"><span class="rate-label">Rate Type</span><span class="rate-value">{{ $row['rate'] ?? '-' }}</span></div>
                <div class="rate-item"><span class="rate-label">Monthly Rate</span><span class="rate-value">{{ $money($row['monthly_rate'] ?? 0) }}</span></div>
                <div class="rate-item"><span class="rate-label">Rate Per Day</span><span class="rate-value">{{ $money($row['rate_per_day'] ?? 0) }}</span></div>
                <div class="rate-item"><span class="rate-label">Rate Per Hour</span><span class="rate-value">{{ $money($row['rate_per_hour'] ?? 0) }}</span></div>
                <div class="rate-item"><span class="rate-label">Days Worked</span><span class="rate-value">{{ $number($row['days_worked'] ?? 0) }}</span></div>
                <div class="rate-item"><span class="rate-label">Overtime Hours</span><span class="rate-value">{{ $number($row['overtime_hours'] ?? 0) }}</span></div>
            </div>
        </section>

        <section class="pay-section">
            <div class="section-title">Earnings</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 24%; text-align: center;">Basis</th>
                        <th style="width: 26%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Basic Pay</td><td class="basis">{{ ($row['rate'] ?? null) === 'Daily' ? $number($row['days_worked'] ?? 0).' day/s' : 'Half month' }}</td><td>{{ $money($basePay) }}</td></tr>
                    <tr><td>Salary Adjustment</td><td class="basis">-</td><td>{{ $money($row['salary_adjustment'] ?? 0) }}</td></tr>
                    <tr><td>Allowance</td><td class="basis">-</td><td>{{ $money($row['allowance'] ?? 0) }}</td></tr>
                    <tr><td>Overtime Pay</td><td class="basis">{{ $number($row['overtime_hours'] ?? 0) }} hour/s</td><td>{{ $money($row['overtime_amount'] ?? 0) }}</td></tr>
                    <tr><td>Shift 3 Additional Pay</td><td class="basis">10% of regular Shift 3 pay</td><td>{{ $money($row['shift3_premium'] ?? 0) }}</td></tr>
                    <tr><td>Regular Holiday Pay</td><td class="basis">-</td><td>{{ $money($row['regular_holiday'] ?? 0) }}</td></tr>
                    <tr><td>Special Holiday Pay</td><td class="basis">-</td><td>{{ $money($row['special_holiday'] ?? 0) }}</td></tr>
                    <tr class="table-total"><td colspan="2">Gross Pay</td><td>{{ $money($row['gross_pay'] ?? 0) }}</td></tr>
                </tbody>
            </table>
        </section>

        <section class="pay-section pay-grid">
            <div>
                <div class="section-title">Deductions</div>
                <table>
                    <thead><tr><th>Description</th><th style="width: 32%;">Amount</th></tr></thead>
                    <tbody>
                        <tr><td>Undertime ({{ $number($row['undertime_minutes'] ?? 0) }} min)</td><td>{{ $money($row['undertime_amount'] ?? 0) }}</td></tr>
                        <tr><td>Half Day</td><td>{{ $money($row['halfday'] ?? 0) }}</td></tr>
                        <tr><td>Absent</td><td>{{ $money($row['absent'] ?? 0) }}</td></tr>
                        <tr><td>Late</td><td>{{ $money($row['late'] ?? 0) }}</td></tr>
                        <tr><td>Shortages</td><td>{{ $money($row['shortages'] ?? 0) }}</td></tr>
                        <tr><td>Company Uniform</td><td>{{ $money($row['uniform'] ?? 0) }}</td></tr>
                        <tr><td>Other Deductions</td><td>{{ $money($row['other_deductions'] ?? 0) }}</td></tr>
                        <tr><td>Loan Payment</td><td>{{ $money($row['loan_payment'] ?? 0) }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div>
                <div class="section-title">Remittances</div>
                <table>
                    <thead><tr><th>Description</th><th style="width: 32%;">Amount</th></tr></thead>
                    <tbody>
                        <tr><td>SSS Loan</td><td>{{ $money($row['sss_loan'] ?? 0) }}</td></tr>
                        <tr><td>SSS EE</td><td>{{ $money($row['sss_ee'] ?? 0) }}</td></tr>
                        <tr><td>HDMF Loan</td><td>{{ $money($row['hdmf_loan'] ?? 0) }}</td></tr>
                        <tr><td>HDMF EE</td><td>{{ $money($row['hdmf_ee'] ?? 0) }}</td></tr>
                        <tr><td>PHIC EE</td><td>{{ $money($row['phic_ee'] ?? 0) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="totals">
            <div class="total-item">
                <span class="total-label">Gross Pay</span>
                <span class="total-value">{{ $money($row['gross_pay'] ?? 0) }}</span>
            </div>
            <div class="total-item">
                <span class="total-label">Total Deductions</span>
                <span class="total-value">{{ $money($row['total_deductions'] ?? 0) }}</span>
            </div>
            <div class="total-item">
                <span class="total-label">Net Pay</span>
                <span class="total-value">{{ $money($row['net_pay'] ?? 0) }}</span>
            </div>
        </section>

        <footer class="payslip-footer">
            Date Generated: {{ \App\Support\CompanyExportHeader::generatedAt() }}
        </footer>
    </main>
</body>
</html>
