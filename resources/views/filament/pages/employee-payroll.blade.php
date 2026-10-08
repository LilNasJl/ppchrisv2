<x-filament-panels::page>
    <style>
        .employee-payroll-workspace { display: grid; gap: 12px; min-width: 0; }
        .employee-payroll-context {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px 24px;
            padding: 12px 16px;
            border: 1px solid rgb(226, 232, 240);
            border-radius: 8px;
            background: rgb(248, 250, 252);
        }
        .dark .employee-payroll-context { border-color: rgb(55, 65, 81); background: rgb(17, 24, 39); }
        .employee-payroll-identity { min-width: 0; }
        .employee-payroll-period { display: block; color: rgb(100, 116, 139); font-size: .72rem; }
        .employee-payroll-name { color: rgb(15, 23, 42); font-size: 1rem; font-weight: 750; overflow-wrap: anywhere; }
        .dark .employee-payroll-period { color: rgb(148, 163, 184); }
        .dark .employee-payroll-name { color: rgb(248, 250, 252); }
        .employee-payroll-meta { display: flex; flex-wrap: wrap; gap: 8px 24px; margin: 0; }
        .employee-payroll-meta div { min-width: 110px; }
        .employee-payroll-meta dt { color: rgb(100, 116, 139); font-size: .68rem; }
        .employee-payroll-meta dd { color: rgb(15, 23, 42); font-size: .78rem; font-weight: 650; margin: 0; }
        .dark .employee-payroll-meta dt { color: rgb(148, 163, 184); }
        .dark .employee-payroll-meta dd { color: rgb(241, 245, 249); }
        .employee-payroll-overview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            align-items: start;
        }
        .employee-payroll-adjustments { max-width: 960px; min-width: 0; }

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
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .employee-payroll-group {
            min-width: 0;
            padding: 14px;
        }

        .employee-payroll-group + .employee-payroll-group {
            border-left: 1px solid rgb(226, 232, 240);
        }

        .dark .employee-payroll-group {
            border-color: rgb(55, 65, 81);
        }

        .employee-payroll-group h3 {
            color: rgb(15, 23, 42);
            font-size: .875rem;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .dark .employee-payroll-group h3 {
            color: rgb(248, 250, 252);
        }

        .employee-payroll-metrics {
            display: grid;
            gap: 0;
            grid-template-columns: minmax(0, 1fr);
        }

        .employee-payroll-metric {
            display: flex;
            gap: 8px;
            justify-content: space-between;
            min-width: 0;
            padding: 5px 0;
            border-bottom: 1px solid rgb(226, 232, 240);
        }
        .employee-payroll-metric:last-child { border-bottom: 0; }
        .dark .employee-payroll-metric { border-color: rgb(55, 65, 81); }

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
            flex-shrink: 0;
        }

        .dark .employee-payroll-metric dt {
            color: rgb(148, 163, 184);
        }

        .dark .employee-payroll-metric dd {
            color: rgb(241, 245, 249);
        }

        .employee-payroll-total {
            min-width: 0;
            min-height: 88px;
            padding: 12px;
            border: 1px solid rgb(226, 232, 240);
            border-radius: 8px;
            background: rgb(248, 250, 252);
            display: flex;
            align-items: center;
            gap: 11px;
        }
        .dark .employee-payroll-total {
            border-color: rgb(55, 65, 81);
            background: rgb(17, 24, 39);
        }
        .employee-payroll-total-icon {
            display: grid;
            flex: 0 0 38px;
            height: 38px;
            place-items: center;
            border-radius: 8px;
            margin: 0 !important;
        }
        .employee-payroll-total-icon svg { height: 20px; width: 20px; }
        .employee-payroll-total.gross .employee-payroll-total-icon { color: rgb(5, 150, 105); background: rgb(209, 250, 229); }
        .employee-payroll-total.deductions .employee-payroll-total-icon { color: rgb(180, 83, 9); background: rgb(254, 243, 199); }
        .employee-payroll-total.net .employee-payroll-total-icon { color: rgb(37, 99, 235); background: rgb(219, 234, 254); }
        .dark .employee-payroll-total.gross .employee-payroll-total-icon { color: rgb(110, 231, 183); background: rgba(5, 150, 105, .18); }
        .dark .employee-payroll-total.deductions .employee-payroll-total-icon { color: rgb(252, 211, 77); background: rgba(217, 119, 6, .18); }
        .dark .employee-payroll-total.net .employee-payroll-total-icon { color: rgb(147, 197, 253); background: rgba(37, 99, 235, .2); }
        .employee-payroll-total-copy { min-width: 0; }

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

        .employee-payroll-total.net strong { color: rgb(37, 99, 235); }

        .dark .employee-payroll-total span {
            color: rgb(148, 163, 184);
        }

        .dark .employee-payroll-total strong {
            color: rgb(248, 250, 252);
        }

        .dark .employee-payroll-total.net strong { color: rgb(96, 165, 250); }

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

        @media (max-width: 1450px) {
            .employee-payroll-groups {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .employee-payroll-group:nth-child(odd) { border-left: 0; }
            .employee-payroll-group:nth-child(n + 3) { border-top: 1px solid rgb(226, 232, 240); }
            .dark .employee-payroll-group:nth-child(n + 3) { border-top-color: rgb(55, 65, 81); }
        }

        @media (max-width: 700px) {
            .employee-payroll-groups { grid-template-columns: 1fr; }
            .employee-payroll-group + .employee-payroll-group { border-left: 0; border-top: 1px solid rgb(226, 232, 240); }
            .dark .employee-payroll-group + .employee-payroll-group { border-top-color: rgb(55, 65, 81); }
            .employee-payroll-overview { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .employee-payroll-total.net { grid-column: 1 / -1; }
            .employee-payroll-meta { width: 100%; }
        }

        @media (max-width: 560px) {
            .employee-payroll-overview { grid-template-columns: 1fr; }
            .employee-payroll-adjustments, .employee-payroll-total.net { grid-column: auto; }
            .employee-payroll-meta { gap: 8px 16px; }
            .employee-payroll-meta div { min-width: 0; }
            .employee-payroll-context { padding: 12px; }
        }

        .payroll-print-table { display: none; }

        @media print {
            .employee-payroll-overview,
            .employee-payroll-statement {
                display: none !important;
            }

            .payroll-print-table {
                display: block !important;
            }
        }

    </style>

    <div class="employee-payroll-workspace">
        <div class="employee-payroll-context">
            <div class="employee-payroll-identity">
                <span class="employee-payroll-period">{{ $this->selectedPeriod?->title ?: 'No payroll period selected' }}</span>
                @if ($this->employee)
                    <strong class="employee-payroll-name">{{ trim($this->employee->lastname . ', ' . (filled($this->employee->middlename) ? $this->employee->middlename . '. ' : '') . $this->employee->firstname) }}</strong>
                @endif
            </div>
            @if ($this->employee)
                <dl class="employee-payroll-meta">
                    <div><dt>Designation</dt><dd>{{ $this->employee->designation?->title ?: '-' }}</dd></div>
                    <div><dt>Department</dt><dd>{{ $this->employee->department?->name ?: '-' }}</dd></div>
                    <div><dt>Branch</dt><dd>{{ $this->employee->branch?->branch_name ?: '-' }}</dd></div>
                </dl>
            @endif
        </div>

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
            <div class="employee-payroll-overview">
                <div class="employee-payroll-total gross">
                    <span class="employee-payroll-total-icon"><x-filament::icon icon="heroicon-m-banknotes" /></span>
                    <div class="employee-payroll-total-copy">
                        <span>Gross Pay</span>
                        <strong>{{ $money($payrollRow['gross_pay'] ?? null) }}</strong>
                    </div>
                </div>
                <div class="employee-payroll-total deductions">
                    <span class="employee-payroll-total-icon"><x-filament::icon icon="heroicon-m-arrow-trending-down" /></span>
                    <div class="employee-payroll-total-copy">
                        <span>Total Deductions</span>
                        <strong>{{ $money($payrollRow['total_deductions'] ?? null) }}</strong>
                    </div>
                </div>
                <div class="employee-payroll-total net">
                    <span class="employee-payroll-total-icon"><x-filament::icon icon="heroicon-m-wallet" /></span>
                    <div class="employee-payroll-total-copy">
                        <span>Net Pay</span>
                        <strong>{{ $money($payrollRow['net_pay'] ?? null) }}</strong>
                    </div>
                </div>
            </div>

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
