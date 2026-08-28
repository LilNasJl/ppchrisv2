<x-filament-panels::page>
    <style>
        .employee-payroll-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .employee-payroll-value {
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .employee-payroll-adjustments {
            max-width: 760px;
        }

        .employee-payroll-statement {
            background: rgb(255, 255, 255);
            border: 1px solid rgb(226, 232, 240);
            border-radius: 8px;
            overflow: hidden;
        }

        .dark .employee-payroll-statement {
            background: rgb(17, 24, 39);
            border-color: rgb(55, 65, 81);
        }

        .employee-payroll-groups {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .employee-payroll-group {
            border-bottom: 1px solid rgb(226, 232, 240);
            min-width: 0;
            padding: 18px;
        }

        .employee-payroll-group:nth-child(odd) {
            border-right: 1px solid rgb(226, 232, 240);
        }

        .dark .employee-payroll-group {
            border-color: rgb(55, 65, 81);
        }

        .employee-payroll-group h3 {
            color: rgb(15, 23, 42);
            font-size: .875rem;
            font-weight: 700;
            margin: 0 0 14px;
        }

        .dark .employee-payroll-group h3 {
            color: rgb(248, 250, 252);
        }

        .employee-payroll-metrics {
            display: grid;
            gap: 10px 18px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .employee-payroll-metric {
            display: flex;
            gap: 12px;
            justify-content: space-between;
            min-width: 0;
        }

        .employee-payroll-metric dt {
            color: rgb(100, 116, 139);
            font-size: .75rem;
            line-height: 1.35;
        }

        .employee-payroll-metric dd {
            color: rgb(15, 23, 42);
            font-size: .8rem;
            font-variant-numeric: tabular-nums;
            font-weight: 650;
            margin: 0;
            text-align: right;
            white-space: nowrap;
        }

        .dark .employee-payroll-metric dt {
            color: rgb(148, 163, 184);
        }

        .dark .employee-payroll-metric dd {
            color: rgb(241, 245, 249);
        }

        .employee-payroll-totals {
            background: rgb(248, 250, 252);
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .dark .employee-payroll-totals {
            background: rgb(15, 23, 42);
        }

        .employee-payroll-total {
            min-width: 0;
            padding: 16px 18px;
        }

        .employee-payroll-total + .employee-payroll-total {
            border-left: 1px solid rgb(226, 232, 240);
        }

        .dark .employee-payroll-total + .employee-payroll-total {
            border-color: rgb(55, 65, 81);
        }

        .employee-payroll-total span {
            color: rgb(100, 116, 139);
            display: block;
            font-size: .72rem;
            margin-bottom: 4px;
        }

        .employee-payroll-total strong {
            color: rgb(15, 23, 42);
            display: block;
            font-size: 1rem;
            font-variant-numeric: tabular-nums;
            overflow-wrap: anywhere;
        }

        .employee-payroll-total:last-child strong {
            color: rgb(37, 99, 235);
        }

        .dark .employee-payroll-total span {
            color: rgb(148, 163, 184);
        }

        .dark .employee-payroll-total strong {
            color: rgb(248, 250, 252);
        }

        .dark .employee-payroll-total:last-child strong {
            color: rgb(96, 165, 250);
        }

        .employee-payroll-empty {
            border: 1px dashed rgb(203, 213, 225);
            border-radius: 8px;
            color: rgb(100, 116, 139);
            padding: 28px;
            text-align: center;
        }

        .dark .employee-payroll-empty {
            border-color: rgb(71, 85, 105);
            color: rgb(148, 163, 184);
        }

        @media (max-width: 900px) {
            .employee-payroll-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .employee-payroll-groups {
                grid-template-columns: 1fr;
            }

            .employee-payroll-group:nth-child(odd) {
                border-right: 0;
            }
        }

        @media (max-width: 560px) {
            .employee-payroll-summary {
                grid-template-columns: 1fr;
            }

            .employee-payroll-metrics,
            .employee-payroll-totals {
                grid-template-columns: 1fr;
            }

            .employee-payroll-total + .employee-payroll-total {
                border-left: 0;
                border-top: 1px solid rgb(226, 232, 240);
            }

            .dark .employee-payroll-total + .employee-payroll-total {
                border-color: rgb(55, 65, 81);
            }
        }

        .payroll-print-table {
            display: none;
        }

        @media print {
            .employee-payroll-adjustments,
            .employee-payroll-statement {
                display: none !important;
            }

            .payroll-print-table {
                display: block !important;
            }
        }
    </style>

    <div style="display: grid; gap: 16px;">
        <div style="font-size: 14px; color: #64748b;">
            Payroll Period:
            <span style="font-weight: 700; color: inherit;">{{ $this->selectedPeriod?->title ?: 'No payroll period selected' }}</span>
        </div>

        @if ($this->employee)
            <div class="employee-payroll-summary">
                <div>
                    <div style="font-size: 12px; color: #94a3b8;">Employee</div>
                    <div class="employee-payroll-value">
                        <span style="display: inline-flex; align-items: center; border-radius: 999px; background: rgba(37, 99, 235, .16); color: #2563eb; padding: 4px 10px; max-width: 100%; overflow-wrap: anywhere;">
                            {{ trim($this->employee->lastname . ', ' . (filled($this->employee->middlename) ? $this->employee->middlename . '. ' : '') . $this->employee->firstname) }}
                        </span>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #94a3b8;">Designation</div>
                    <div class="employee-payroll-value">{{ $this->employee->designation?->title ?: '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #94a3b8;">Department</div>
                    <div class="employee-payroll-value">{{ $this->employee->department?->name ?: '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #94a3b8;">Branch</div>
                    <div class="employee-payroll-value">{{ $this->employee->branch?->branch_name ?: '-' }}</div>
                </div>
            </div>
        @endif

        @php
            $payrollRow = $this->payrollRow;
            $money = static fn (mixed $value): string => blank($value) && $value !== 0 && $value !== '0'
                ? '-'
                : number_format((float) $value, 2);
            $number = static fn (mixed $value): string => blank($value) && $value !== 0 && $value !== '0'
                ? '-'
                : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        @endphp

        @if ($payrollRow)
            <div class="employee-payroll-adjustments">
                {{ $this->form }}
            </div>

            <div class="employee-payroll-statement">
                <div class="employee-payroll-groups">
                    <section class="employee-payroll-group">
                        <h3>Rate & Attendance</h3>
                        <dl class="employee-payroll-metrics">
                            <div class="employee-payroll-metric"><dt>Rate Type</dt><dd>{{ $payrollRow['rate'] ?? '-' }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Monthly Rate</dt><dd>{{ $money($payrollRow['monthly_rate'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Half Month Pay</dt><dd>{{ $money($payrollRow['half_month_pay'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Rate Per Day</dt><dd>{{ $money($payrollRow['rate_per_day'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Rate Per Hour</dt><dd>{{ $money($payrollRow['rate_per_hour'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Days Worked</dt><dd>{{ $number($payrollRow['days_worked'] ?? null) }}</dd></div>
                        </dl>
                    </section>

                    <section class="employee-payroll-group">
                        <h3>Earnings</h3>
                        <dl class="employee-payroll-metrics">
                            <div class="employee-payroll-metric"><dt>Salary Adjustment</dt><dd>{{ $money($payrollRow['salary_adjustment'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Allowance</dt><dd>{{ $money($payrollRow['allowance'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Overtime Hours</dt><dd>{{ $number($payrollRow['overtime_hours'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Overtime Amount</dt><dd>{{ $money($payrollRow['overtime_amount'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Shift 3 (10%)</dt><dd>{{ $money($payrollRow['shift3_premium'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Regular Holiday</dt><dd>{{ $money($payrollRow['regular_holiday'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Special Holiday</dt><dd>{{ $money($payrollRow['special_holiday'] ?? null) }}</dd></div>
                        </dl>
                    </section>

                    <section class="employee-payroll-group">
                        <h3>Deductions</h3>
                        <dl class="employee-payroll-metrics">
                            <div class="employee-payroll-metric"><dt>Undertime Minutes</dt><dd>{{ $number($payrollRow['undertime_minutes'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Undertime Amount</dt><dd>{{ $money($payrollRow['undertime_amount'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Half Day</dt><dd>{{ $money($payrollRow['halfday'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Absent</dt><dd>{{ $money($payrollRow['absent'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Late</dt><dd>{{ $money($payrollRow['late'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Shortages</dt><dd>{{ $money($payrollRow['shortages'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Uniform</dt><dd>{{ $money($payrollRow['uniform'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Other Deductions</dt><dd>{{ $money($payrollRow['other_deductions'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>Loan Payment</dt><dd>{{ $money($payrollRow['loan_payment'] ?? null) }}</dd></div>
                        </dl>
                    </section>

                    <section class="employee-payroll-group">
                        <h3>Remittances</h3>
                        <dl class="employee-payroll-metrics">
                            <div class="employee-payroll-metric"><dt>SSS Loan</dt><dd>{{ $money($payrollRow['sss_loan'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>SSS EE</dt><dd>{{ $money($payrollRow['sss_ee'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>HDMF Loan</dt><dd>{{ $money($payrollRow['hdmf_loan'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>HDMF EE</dt><dd>{{ $money($payrollRow['hdmf_ee'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric"><dt>PHIC EE</dt><dd>{{ $money($payrollRow['phic_ee'] ?? null) }}</dd></div>
                        </dl>
                    </section>
                </div>

                <div class="employee-payroll-totals">
                    <div class="employee-payroll-total">
                        <span>Gross Pay</span>
                        <strong>{{ $money($payrollRow['gross_pay'] ?? null) }}</strong>
                    </div>
                    <div class="employee-payroll-total">
                        <span>Total Deductions</span>
                        <strong>{{ $money($payrollRow['total_deductions'] ?? null) }}</strong>
                    </div>
                    <div class="employee-payroll-total">
                        <span>Net Pay</span>
                        <strong>{{ $money($payrollRow['net_pay'] ?? null) }}</strong>
                    </div>
                </div>
            </div>
        @else
            <div class="employee-payroll-empty">No payroll data available.</div>
        @endif

        <div class="payroll-print-table">
            @include('filament.pages.partials.payroll-detail-table', [
                'rows' => $this->payrollRow ? collect([$this->payrollRow]) : collect(),
            ])
        </div>
    </div>
</x-filament-panels::page>
