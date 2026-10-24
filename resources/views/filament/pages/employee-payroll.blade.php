<x-filament-panels::page>
    <style>
        .employee-payroll-workspace {
            display: grid;
            gap: 10px;
            min-width: 0;
        }

        /* ── Context / Employee Header Bar ── */
        .employee-payroll-context {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px 18px;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgb(226, 232, 240);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .dark .employee-payroll-context {
            border-color: rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.75) 100%);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .employee-payroll-identity {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .employee-payroll-avatar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgb(59, 130, 246) 0%, rgb(99, 102, 241) 100%);
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 0.85rem;
            font-weight: 750;
            letter-spacing: 0.02em;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25);
        }

        .employee-payroll-identity-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .employee-payroll-period-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            color: rgb(100, 116, 139);
        }
        .dark .employee-payroll-period-badge {
            color: rgb(148, 163, 184);
        }

        .employee-payroll-name {
            color: rgb(15, 23, 42);
            font-size: 1.05rem;
            font-weight: 750;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }
        .dark .employee-payroll-name {
            color: rgb(248, 250, 252);
        }

        .employee-payroll-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 12px;
            margin: 0;
        }

        .employee-payroll-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 8px;
            background: rgba(241, 245, 249, 0.8);
            border: 1px solid rgba(226, 232, 240, 0.8);
            font-size: 0.76rem;
        }
        .dark .employee-payroll-meta-item {
            background: rgba(30, 41, 59, 0.45);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .employee-payroll-meta-item dt {
            color: rgb(100, 116, 139);
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .dark .employee-payroll-meta-item dt {
            color: rgb(148, 163, 184);
        }

        .employee-payroll-meta-item dd {
            color: rgb(15, 23, 42);
            font-weight: 650;
            margin: 0;
            white-space: nowrap;
        }
        .dark .employee-payroll-meta-item dd {
            color: rgb(241, 245, 249);
        }

        /* ── Overview Cards (Gross, Deductions, Net) ── */
        .employee-payroll-overview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            align-items: stretch;
        }

        .employee-payroll-total {
            min-width: 0;
            padding: 10px 14px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .employee-payroll-total:hover {
            transform: translateY(-1px);
        }

        .employee-payroll-total-icon {
            display: grid;
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            place-items: center;
            border-radius: 10px;
            margin: 0 !important;
        }
        .employee-payroll-total-icon svg {
            width: 20px;
            height: 20px;
        }

        /* Gross Card */
        .employee-payroll-total.gross {
            background: linear-gradient(135deg, rgba(236, 253, 245, 0.7) 0%, rgba(255, 255, 255, 0.9) 100%);
            border: 1px solid rgba(16, 185, 129, 0.25);
            box-shadow: 0 1px 2px rgba(16, 185, 129, 0.05);
        }
        .employee-payroll-total.gross .employee-payroll-total-icon {
            color: rgb(5, 150, 105);
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .dark .employee-payroll-total.gross {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.06) 0%, rgba(15, 23, 42, 0.65) 100%);
            border-color: rgba(16, 185, 129, 0.22);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        .dark .employee-payroll-total.gross .employee-payroll-total-icon {
            color: rgb(52, 211, 153);
            background: rgba(16, 185, 129, 0.16);
            border-color: rgba(16, 185, 129, 0.25);
        }

        /* Deductions Card */
        .employee-payroll-total.deductions {
            background: linear-gradient(135deg, rgba(254, 242, 242, 0.7) 0%, rgba(255, 255, 255, 0.9) 100%);
            border: 1px solid rgba(244, 63, 94, 0.25);
            box-shadow: 0 1px 2px rgba(244, 63, 94, 0.05);
        }
        .employee-payroll-total.deductions .employee-payroll-total-icon {
            color: rgb(225, 29, 72);
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.2);
        }
        .dark .employee-payroll-total.deductions {
            background: linear-gradient(135deg, rgba(244, 63, 94, 0.06) 0%, rgba(15, 23, 42, 0.65) 100%);
            border-color: rgba(244, 63, 94, 0.22);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        .dark .employee-payroll-total.deductions .employee-payroll-total-icon {
            color: rgb(251, 113, 133);
            background: rgba(244, 63, 94, 0.16);
            border-color: rgba(244, 63, 94, 0.25);
        }

        /* Net Pay Card (Hero) */
        .employee-payroll-total.net {
            background: linear-gradient(135deg, rgba(239, 246, 255, 0.85) 0%, rgba(219, 234, 254, 0.6) 100%);
            border: 1px solid rgba(59, 130, 246, 0.4);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
        }
        .employee-payroll-total.net .employee-payroll-total-icon {
            color: rgb(37, 99, 235);
            background: rgba(59, 130, 246, 0.18);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .dark .employee-payroll-total.net {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.16) 0%, rgba(30, 58, 138, 0.3) 100%);
            border-color: rgba(96, 165, 250, 0.4);
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.2);
        }
        .dark .employee-payroll-total.net .employee-payroll-total-icon {
            color: rgb(147, 197, 253);
            background: rgba(59, 130, 246, 0.25);
            border-color: rgba(96, 165, 250, 0.4);
        }

        .employee-payroll-total-copy {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }

        .employee-payroll-total-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(100, 116, 139);
        }
        .dark .employee-payroll-total-label {
            color: rgb(148, 163, 184);
        }

        .employee-payroll-total-amount {
            display: flex;
            align-items: baseline;
            gap: 3px;
            font-size: 1.15rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: rgb(15, 23, 42);
            line-height: 1.2;
        }
        .dark .employee-payroll-total-amount {
            color: rgb(248, 250, 252);
        }

        .employee-payroll-total.gross .employee-payroll-total-amount {
            color: rgb(5, 150, 105);
        }
        .dark .employee-payroll-total.gross .employee-payroll-total-amount {
            color: rgb(52, 211, 153);
        }

        .employee-payroll-total.deductions .employee-payroll-total-amount {
            color: rgb(225, 29, 72);
        }
        .dark .employee-payroll-total.deductions .employee-payroll-total-amount {
            color: rgb(251, 113, 133);
        }

        .employee-payroll-total.net .employee-payroll-total-amount {
            color: rgb(29, 78, 216);
            font-size: 1.25rem;
        }
        .dark .employee-payroll-total.net .employee-payroll-total-amount {
            color: rgb(147, 197, 253);
        }

        .employee-payroll-currency {
            font-size: 0.8rem;
            font-weight: 600;
            opacity: 0.75;
        }

        /* ── Compact Adjustments Form ── */
        .employee-payroll-adjustments {
            min-width: 0;
        }
        .employee-payroll-adjustments .fi-section {
            border-radius: 10px !important;
            overflow: hidden;
            border-color: rgb(226, 232, 240) !important;
        }
        .dark .employee-payroll-adjustments .fi-section {
            border-color: rgba(255, 255, 255, 0.08) !important;
            background: rgb(17, 24, 39) !important;
        }
        .employee-payroll-adjustments .fi-section-header {
            padding: 8px 14px !important;
        }
        .employee-payroll-adjustments .fi-section-header-heading {
            font-size: 0.82rem !important;
            font-weight: 700 !important;
        }
        .employee-payroll-adjustments .fi-section-content {
            padding: 10px 14px !important;
        }

        /* ── Statement Container & 4 Columns ── */
        .employee-payroll-statement {
            background: rgb(255, 255, 255);
            border: 1px solid rgb(226, 232, 240);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .dark .employee-payroll-statement {
            background: rgb(17, 24, 39);
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }

        .employee-payroll-groups {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .employee-payroll-group {
            min-width: 0;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
        }

        .employee-payroll-group + .employee-payroll-group {
            border-left: 1px solid rgb(226, 232, 240);
        }
        .dark .employee-payroll-group + .employee-payroll-group {
            border-left-color: rgba(255, 255, 255, 0.08);
        }

        .employee-payroll-group-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgb(226, 232, 240);
        }
        .dark .employee-payroll-group-header {
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }

        .employee-payroll-group-icon {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .employee-payroll-group-icon svg {
            width: 15px;
            height: 15px;
        }

        .employee-payroll-group-icon.rate {
            color: rgb(2, 132, 199);
            background: rgba(2, 132, 199, 0.12);
        }
        .dark .employee-payroll-group-icon.rate {
            color: rgb(56, 189, 248);
            background: rgba(2, 132, 199, 0.18);
        }

        .employee-payroll-group-icon.earnings {
            color: rgb(5, 150, 105);
            background: rgba(16, 185, 129, 0.12);
        }
        .dark .employee-payroll-group-icon.earnings {
            color: rgb(52, 211, 153);
            background: rgba(16, 185, 129, 0.18);
        }

        .employee-payroll-group-icon.deductions {
            color: rgb(225, 29, 72);
            background: rgba(244, 63, 94, 0.12);
        }
        .dark .employee-payroll-group-icon.deductions {
            color: rgb(251, 113, 133);
            background: rgba(244, 63, 94, 0.18);
        }

        .employee-payroll-group-icon.remittances {
            color: rgb(124, 58, 237);
            background: rgba(139, 92, 246, 0.12);
        }
        .dark .employee-payroll-group-icon.remittances {
            color: rgb(167, 139, 250);
            background: rgba(139, 92, 246, 0.18);
        }

        .employee-payroll-group h3 {
            color: rgb(15, 23, 42);
            font-size: 0.84rem;
            font-weight: 750;
            margin: 0;
            letter-spacing: -0.01em;
        }
        .dark .employee-payroll-group h3 {
            color: rgb(248, 250, 252);
        }

        .employee-payroll-metrics {
            display: flex;
            flex-direction: column;
            gap: 1px;
            margin: 0;
            flex: 1 1 auto;
        }

        .employee-payroll-metric {
            display: flex;
            gap: 8px;
            justify-content: space-between;
            align-items: center;
            min-width: 0;
            padding: 4px 6px;
            border-radius: 5px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            transition: background 0.12s ease;
        }
        .employee-payroll-metric:last-child {
            border-bottom: 0;
        }
        .dark .employee-payroll-metric {
            border-bottom-color: rgba(255, 255, 255, 0.04);
        }

        .employee-payroll-metric:hover {
            background: rgba(0, 0, 0, 0.03);
        }
        .dark .employee-payroll-metric:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .employee-payroll-metric dt {
            color: rgb(100, 116, 139);
            font-size: 0.74rem;
            font-weight: 500;
            line-height: 1.35;
        }
        .dark .employee-payroll-metric dt {
            color: rgb(148, 163, 184);
        }

        .employee-payroll-metric dd {
            color: rgb(15, 23, 42);
            font-size: 0.79rem;
            font-variant-numeric: tabular-nums;
            font-weight: 650;
            margin: 0;
            text-align: right;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .dark .employee-payroll-metric dd {
            color: rgb(241, 245, 249);
        }

        /* Dim zero-value rows so non-zero amounts pop! */
        .employee-payroll-metric.is-zero {
            opacity: 0.55;
        }
        .employee-payroll-metric.is-zero dd {
            color: rgb(148, 163, 184);
            font-weight: 500;
        }
        .dark .employee-payroll-metric.is-zero dd {
            color: rgb(100, 116, 139);
            font-weight: 500;
        }

        /* Column Footers / Subtotals */
        .employee-payroll-col-footer {
            margin-top: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.74rem;
            background: rgba(241, 245, 249, 0.8);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .dark .employee-payroll-col-footer {
            background: rgba(30, 41, 59, 0.5);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .employee-payroll-col-footer span {
            color: rgb(100, 116, 139);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            font-size: 0.68rem;
        }
        .dark .employee-payroll-col-footer span {
            color: rgb(148, 163, 184);
        }

        .employee-payroll-col-footer strong {
            font-size: 0.82rem;
            font-weight: 750;
            font-variant-numeric: tabular-nums;
            color: rgb(15, 23, 42);
        }
        .dark .employee-payroll-col-footer strong {
            color: rgb(248, 250, 252);
        }

        .employee-payroll-col-footer.positive strong {
            color: rgb(5, 150, 105);
        }
        .dark .employee-payroll-col-footer.positive strong {
            color: rgb(52, 211, 153);
        }

        .employee-payroll-col-footer.negative strong {
            color: rgb(225, 29, 72);
        }
        .dark .employee-payroll-col-footer.negative strong {
            color: rgb(251, 113, 133);
        }

        .employee-payroll-col-footer.remittances strong {
            color: rgb(124, 58, 237);
        }
        .dark .employee-payroll-col-footer.remittances strong {
            color: rgb(167, 139, 250);
        }

        .employee-payroll-empty {
            border: 1px dashed rgb(203, 213, 225);
            border-radius: 10px;
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
            .employee-payroll-group:nth-child(odd) {
                border-left: 0;
            }
            .employee-payroll-group:nth-child(n + 3) {
                border-top: 1px solid rgb(226, 232, 240);
            }
            .dark .employee-payroll-group:nth-child(n + 3) {
                border-top-color: rgba(255, 255, 255, 0.08);
            }
        }

        @media (max-width: 768px) {
            .employee-payroll-groups {
                grid-template-columns: 1fr;
            }
            .employee-payroll-group + .employee-payroll-group {
                border-left: 0;
                border-top: 1px solid rgb(226, 232, 240);
            }
            .dark .employee-payroll-group + .employee-payroll-group {
                border-top-color: rgba(255, 255, 255, 0.08);
            }
            .employee-payroll-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .employee-payroll-total.net {
                grid-column: 1 / -1;
            }
            .employee-payroll-meta {
                width: 100%;
            }
        }

        @media (max-width: 560px) {
            .employee-payroll-overview {
                grid-template-columns: 1fr;
            }
            .employee-payroll-adjustments,
            .employee-payroll-total.net {
                grid-column: auto;
            }
            .employee-payroll-context {
                padding: 10px;
            }
            .employee-payroll-meta {
                gap: 6px;
            }
        }

        .payroll-print-table {
            display: none;
        }

        @media print {
            .employee-payroll-overview,
            .employee-payroll-adjustments,
            .employee-payroll-statement {
                display: none !important;
            }

            .payroll-print-table {
                display: block !important;
            }
        }
    </style>

    @php
        $payrollRow = $this->payrollRow;
        $money = static fn (mixed $value): string => blank($value) && $value !== 0 && $value !== '0'
            ? '-'
            : number_format((float) $value, 2);
        $number = static fn (mixed $value): string => blank($value) && $value !== 0 && $value !== '0'
            ? '-'
            : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        $isZero = static fn (mixed $value): bool => blank($value) || (float) $value == 0;

        $remittancesTotal = 0;
        $deductionsSubtotal = 0;
        if ($payrollRow) {
            $remittancesTotal = (float) ($payrollRow['sss_loan'] ?? 0)
                + (float) ($payrollRow['sss_ee'] ?? 0)
                + (float) ($payrollRow['hdmf_loan'] ?? 0)
                + (float) ($payrollRow['hdmf_ee'] ?? 0)
                + (float) ($payrollRow['phic_ee'] ?? 0);

            $deductionsSubtotal = (float) ($payrollRow['total_deductions'] ?? 0) - $remittancesTotal;
        }

        $initials = '';
        if ($this->employee) {
            $first = mb_substr((string) $this->employee->firstname, 0, 1);
            $last = mb_substr((string) $this->employee->lastname, 0, 1);
            $initials = strtoupper($first . $last);
        }
    @endphp

    <div class="employee-payroll-workspace">
        <div class="employee-payroll-context">
            <div class="employee-payroll-identity">
                @if ($initials)
                    <div class="employee-payroll-avatar" aria-hidden="true">{{ $initials }}</div>
                @endif
                <div class="employee-payroll-identity-copy">
                    <span class="employee-payroll-period-badge">
                        <x-filament::icon icon="heroicon-m-calendar" class="w-3.5 h-3.5" />
                        {{ $this->selectedPeriod?->title ?: 'No payroll period selected' }}
                    </span>
                    @if ($this->employee)
                        <strong class="employee-payroll-name">{{ trim($this->employee->lastname . ', ' . (filled($this->employee->middlename) ? $this->employee->middlename . '. ' : '') . $this->employee->firstname) }}</strong>
                    @endif
                </div>
            </div>
            @if ($this->employee)
                <dl class="employee-payroll-meta">
                    <div class="employee-payroll-meta-item">
                        <x-filament::icon icon="heroicon-m-briefcase" class="w-3.5 h-3.5 opacity-60 flex-shrink-0" />
                        <div><dt>Designation</dt><dd>{{ $this->employee->designation?->title ?: '-' }}</dd></div>
                    </div>
                    <div class="employee-payroll-meta-item">
                        <x-filament::icon icon="heroicon-m-building-office-2" class="w-3.5 h-3.5 opacity-60 flex-shrink-0" />
                        <div><dt>Department</dt><dd>{{ $this->employee->department?->name ?: '-' }}</dd></div>
                    </div>
                    <div class="employee-payroll-meta-item">
                        <x-filament::icon icon="heroicon-m-map-pin" class="w-3.5 h-3.5 opacity-60 flex-shrink-0" />
                        <div><dt>Branch</dt><dd>{{ $this->employee->branch?->branch_name ?: '-' }}</dd></div>
                    </div>
                </dl>
            @endif
        </div>

        @if ($payrollRow)
            <div class="employee-payroll-overview">
                <div class="employee-payroll-total gross">
                    <span class="employee-payroll-total-icon"><x-filament::icon icon="heroicon-m-banknotes" /></span>
                    <div class="employee-payroll-total-copy">
                        <span class="employee-payroll-total-label">Gross Pay</span>
                        <strong class="employee-payroll-total-amount">
                            <span class="employee-payroll-currency">₱</span>{{ $money($payrollRow['gross_pay'] ?? null) }}
                        </strong>
                    </div>
                </div>
                <div class="employee-payroll-total deductions">
                    <span class="employee-payroll-total-icon"><x-filament::icon icon="heroicon-m-arrow-trending-down" /></span>
                    <div class="employee-payroll-total-copy">
                        <span class="employee-payroll-total-label">Total Deductions</span>
                        <strong class="employee-payroll-total-amount">
                            <span class="employee-payroll-currency">₱</span>{{ $money($payrollRow['total_deductions'] ?? null) }}
                        </strong>
                    </div>
                </div>
                <div class="employee-payroll-total net">
                    <span class="employee-payroll-total-icon"><x-filament::icon icon="heroicon-m-wallet" /></span>
                    <div class="employee-payroll-total-copy">
                        <span class="employee-payroll-total-label">Net Pay</span>
                        <strong class="employee-payroll-total-amount">
                            <span class="employee-payroll-currency">₱</span>{{ $money($payrollRow['net_pay'] ?? null) }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="employee-payroll-adjustments">
                {{ $this->form }}
            </div>

            <div class="employee-payroll-statement">
                <div class="employee-payroll-groups">
                    {{-- 1. Rate & Attendance --}}
                    <section class="employee-payroll-group">
                        <div class="employee-payroll-group-header">
                            <span class="employee-payroll-group-icon rate"><x-filament::icon icon="heroicon-m-clock" /></span>
                            <h3>Rate & Attendance</h3>
                        </div>
                        <dl class="employee-payroll-metrics">
                            <div class="employee-payroll-metric"><dt>Rate Type</dt><dd>{{ $payrollRow['rate'] ?? '-' }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['monthly_rate'] ?? null)) is-zero @endif"><dt>Monthly Rate</dt><dd>{{ $money($payrollRow['monthly_rate'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['half_month_pay'] ?? null)) is-zero @endif"><dt>Half Month Pay</dt><dd>{{ $money($payrollRow['half_month_pay'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['rate_per_day'] ?? null)) is-zero @endif"><dt>Rate Per Day</dt><dd>{{ $money($payrollRow['rate_per_day'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['rate_per_hour'] ?? null)) is-zero @endif"><dt>Rate Per Hour</dt><dd>{{ $money($payrollRow['rate_per_hour'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['days_worked'] ?? null)) is-zero @endif"><dt>Days Worked</dt><dd>{{ $number($payrollRow['days_worked'] ?? null) }}</dd></div>
                        </dl>
                        <div class="employee-payroll-col-footer">
                            <span>Payable Period</span>
                            <strong>{{ $number($payrollRow['days_worked'] ?? null) }} Days</strong>
                        </div>
                    </section>

                    {{-- 2. Earnings --}}
                    <section class="employee-payroll-group">
                        <div class="employee-payroll-group-header">
                            <span class="employee-payroll-group-icon earnings"><x-filament::icon icon="heroicon-m-plus-circle" /></span>
                            <h3>Earnings</h3>
                        </div>
                        <dl class="employee-payroll-metrics">
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['salary_adjustment'] ?? null)) is-zero @endif"><dt>Salary Adjustment</dt><dd>{{ $money($payrollRow['salary_adjustment'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['allowance'] ?? null)) is-zero @endif"><dt>Allowance</dt><dd>{{ $money($payrollRow['allowance'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['overtime_hours'] ?? null)) is-zero @endif"><dt>Overtime Hours</dt><dd>{{ $number($payrollRow['overtime_hours'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['overtime_amount'] ?? null)) is-zero @endif"><dt>Overtime Amount</dt><dd>{{ $money($payrollRow['overtime_amount'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['shift3_premium'] ?? null)) is-zero @endif"><dt>Shift 3 (10%)</dt><dd>{{ $money($payrollRow['shift3_premium'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['regular_holiday'] ?? null)) is-zero @endif"><dt>Regular Holiday</dt><dd>{{ $money($payrollRow['regular_holiday'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['special_holiday'] ?? null)) is-zero @endif"><dt>Special Holiday</dt><dd>{{ $money($payrollRow['special_holiday'] ?? null) }}</dd></div>
                        </dl>
                        <div class="employee-payroll-col-footer positive">
                            <span>Total Earnings</span>
                            <strong>₱ {{ $money($payrollRow['gross_pay'] ?? null) }}</strong>
                        </div>
                    </section>

                    {{-- 3. Deductions --}}
                    <section class="employee-payroll-group">
                        <div class="employee-payroll-group-header">
                            <span class="employee-payroll-group-icon deductions"><x-filament::icon icon="heroicon-m-minus-circle" /></span>
                            <h3>Deductions</h3>
                        </div>
                        <dl class="employee-payroll-metrics">
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['undertime_minutes'] ?? null)) is-zero @endif"><dt>Undertime Minutes</dt><dd>{{ $number($payrollRow['undertime_minutes'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['undertime_amount'] ?? null)) is-zero @endif"><dt>Undertime Amount</dt><dd>{{ $money($payrollRow['undertime_amount'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['halfday'] ?? null)) is-zero @endif"><dt>Half Day</dt><dd>{{ $money($payrollRow['halfday'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['absent'] ?? null)) is-zero @endif"><dt>Absent</dt><dd>{{ $money($payrollRow['absent'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['unpaid_leave'] ?? null)) is-zero @endif"><dt>Unpaid Leave</dt><dd>{{ $money($payrollRow['unpaid_leave'] ?? 0) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['late'] ?? null)) is-zero @endif"><dt>Late</dt><dd>{{ $money($payrollRow['late'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['shortages'] ?? null)) is-zero @endif"><dt>Shortages</dt><dd>{{ $money($payrollRow['shortages'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['uniform'] ?? null)) is-zero @endif"><dt>Uniform</dt><dd>{{ $money($payrollRow['uniform'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['other_deductions'] ?? null)) is-zero @endif"><dt>Other Deductions</dt><dd>{{ $money($payrollRow['other_deductions'] ?? null) }}</dd></div>
                            @foreach ($this->loanTypeColumns() as $loanTypeId => $loanTypeName)
                                <div class="employee-payroll-metric @if ($isZero($payrollRow['loan_type_'.$loanTypeId] ?? null)) is-zero @endif"><dt>{{ $loanTypeName }}</dt><dd>{{ $money($payrollRow['loan_type_'.$loanTypeId] ?? 0) }}</dd></div>
                            @endforeach
                        </dl>
                        <div class="employee-payroll-col-footer negative">
                            <span>Deductions Subtotal</span>
                            <strong>₱ {{ $money($deductionsSubtotal) }}</strong>
                        </div>
                    </section>

                    {{-- 4. Remittances --}}
                    <section class="employee-payroll-group">
                        <div class="employee-payroll-group-header">
                            <span class="employee-payroll-group-icon remittances"><x-filament::icon icon="heroicon-m-building-library" /></span>
                            <h3>Remittances</h3>
                        </div>
                        <dl class="employee-payroll-metrics">
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['sss_loan'] ?? null)) is-zero @endif"><dt>SSS Loan</dt><dd>{{ $money($payrollRow['sss_loan'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['sss_ee'] ?? null)) is-zero @endif"><dt>SSS EE</dt><dd>{{ $money($payrollRow['sss_ee'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['hdmf_loan'] ?? null)) is-zero @endif"><dt>HDMF Loan</dt><dd>{{ $money($payrollRow['hdmf_loan'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['hdmf_ee'] ?? null)) is-zero @endif"><dt>HDMF EE</dt><dd>{{ $money($payrollRow['hdmf_ee'] ?? null) }}</dd></div>
                            <div class="employee-payroll-metric @if ($isZero($payrollRow['phic_ee'] ?? null)) is-zero @endif"><dt>PHIC EE</dt><dd>{{ $money($payrollRow['phic_ee'] ?? null) }}</dd></div>
                        </dl>
                        <div class="employee-payroll-col-footer remittances">
                            <span>Total Remittances</span>
                            <strong>₱ {{ $money($remittancesTotal) }}</strong>
                        </div>
                    </section>
                </div>
            </div>
        @else
            <div class="employee-payroll-empty">No payroll data available.</div>
        @endif

        <div class="payroll-print-table">
            @include('filament.pages.partials.payroll-detail-table', [
                'rows' => $payrollRow ? collect([$payrollRow]) : collect(),
                'loanTypeColumns' => $this->loanTypeColumns(),
            ])
        </div>
    </div>
</x-filament-panels::page>
