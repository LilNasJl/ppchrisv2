<x-filament-panels::page>
    @php
        $periods = $this->payrollPeriods();
        $setting = $this->currentSettings();
        $daysPerMonth = $setting->divisor('regular_work_days_per_month');
        $halfMonthDays = $setting->value('regular_half_month_days');
        $workHours = $setting->divisor('work_hours_per_day');
        $lateGrace = (int) round($setting->value('late_grace_minutes'));
        $halfDayVal = $setting->value('half_day_work_day_value');
        $otMult = $setting->value('overtime_rate_multiplier');
        $regHolRate = $setting->value('regular_holiday_rate');
        $specHolRate = $setting->value('special_holiday_rate');
        $holOtPremium = $setting->value('holiday_overtime_premium_rate');
        $unworkedHol = $setting->enabled('unworked_regular_holiday_pay_enabled');
        $isLocked = (bool) ($this->period?->is_locked);
    @endphp

    <style>
        .payroll-calc-workspace {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            width: 100%;
        }

        /* ── Top Header Banner ── */
        .payroll-calc-topbar {
            position: relative;
            z-index: 20;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.25rem 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid #3b82f6;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(18, 87, 184, 0.15);
        }

        .payroll-calc-topbar-copy {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }

        .payroll-calc-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            line-height: 1.2;
        }

        .payroll-calc-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .payroll-calc-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .payroll-calc-actions-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .payroll-calc-period-select {
            height: 2.25rem;
            padding: 0.3rem 0.75rem;
            border-radius: 0.45rem;
            border: 1px solid rgba(147, 197, 253, 0.4);
            background: rgba(10, 31, 68, 0.85);
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            transition: all 120ms ease;
        }
        .payroll-calc-period-select:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 2px rgba(147, 197, 253, 0.25);
        }
        .payroll-calc-period-select option {
            background: #0b1528;
            color: #ffffff;
        }

        .payroll-calc-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            height: 2.25rem;
            padding: 0.35rem 0.85rem;
            border-radius: 0.45rem;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
            transition: all 120ms ease;
        }
        .payroll-calc-btn.outline {
            color: #ffffff;
            background: rgba(10, 31, 68, 0.65);
            border: 1px solid rgba(147, 197, 253, 0.35);
        }
        .payroll-calc-btn.outline:hover {
            background: rgba(10, 31, 68, 0.95);
            border-color: rgba(191, 219, 254, 0.7);
        }

        /* ── Period Status & Dates Context Card ── */
        .payroll-calc-context-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.85rem 1.25rem;
            padding: 0.85rem 1.25rem;
            border-radius: 0.65rem;
            background: #000000;
            border: 1px solid #27272a;
        }

        .payroll-calc-context-left {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .payroll-calc-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .payroll-calc-pill.success {
            background: rgba(22, 163, 74, 0.18);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.35);
        }
        .payroll-calc-pill.warning {
            background: rgba(245, 158, 11, 0.18);
            color: #fcd34d;
            border: 1px solid rgba(251, 191, 36, 0.35);
        }
        .payroll-calc-pill.neutral {
            background: #18181b;
            color: #cbd5e1;
            border: 1px solid #27272a;
        }

        .payroll-calc-context-right {
            font-size: 0.75rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* ── KPI Quick-Stats Strip ── */
        .payroll-calc-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .payroll-calc-kpi {
            background: #000000;
            border: 1px solid #27272a;
            border-radius: 0.65rem;
            padding: 0.85rem 1.1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            transition: all 120ms ease;
        }
        .payroll-calc-kpi:hover {
            border-color: #3b82f6;
            background: #090d16;
        }

        .payroll-calc-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 0.5rem;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .payroll-calc-kpi-icon svg {
            width: 18px;
            height: 18px;
        }

        .payroll-calc-kpi-icon.sky {
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.25);
        }
        .payroll-calc-kpi-icon.indigo {
            color: #818cf8;
            background: rgba(129, 140, 248, 0.12);
            border: 1px solid rgba(129, 140, 248, 0.25);
        }
        .payroll-calc-kpi-icon.emerald {
            color: #34d399;
            background: rgba(52, 211, 153, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.25);
        }
        .payroll-calc-kpi-icon.amber {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.12);
            border: 1px solid rgba(251, 191, 36, 0.25);
        }

        .payroll-calc-kpi-body {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }

        .payroll-calc-kpi-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #94a3b8;
        }

        .payroll-calc-kpi-val {
            font-size: 1.12rem;
            font-weight: 800;
            color: #f8fafc;
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
        }

        .payroll-calc-kpi-sub {
            font-size: 0.72rem;
            color: #94a3b8;
        }

        /* ── Filament Section & Form Card Styling ── */
        .payroll-calc-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .payroll-calc-form .fi-section {
            background: #000000 !important;
            border: 1px solid #27272a !important;
            border-radius: 0.75rem !important;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .payroll-calc-form .fi-section-header {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.005) 100%) !important;
            border-bottom: 1px solid #27272a !important;
            padding: 0.85rem 1.25rem !important;
        }

        .payroll-calc-form .fi-section-header-heading {
            font-size: 0.95rem !important;
            font-weight: 750 !important;
            color: #f8fafc !important;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .payroll-calc-form .fi-section-header-description {
            font-size: 0.75rem !important;
            color: #94a3b8 !important;
            margin-top: 2px;
        }

        .payroll-calc-form .fi-section-content {
            padding: 1.15rem 1.25rem !important;
            background: #000000 !important;
        }

        .payroll-calc-form .fi-input-wrp {
            background: #000000 !important;
            border-color: #3f3f46 !important;
            border-radius: 0.5rem !important;
            color-scheme: dark;
        }
        .payroll-calc-form .fi-input-wrp:focus-within {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
        }

        .payroll-calc-form .fi-input {
            background: transparent !important;
            color: #f8fafc !important;
            font-weight: 600 !important;
            font-size: 0.88rem !important;
        }

        .payroll-calc-form label.fi-fo-field-wrp-label span {
            font-size: 0.74rem !important;
            font-weight: 750 !important;
            color: #e2e8f0 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.03em !important;
        }

        .payroll-calc-form .fi-fo-field-wrp-helper-text {
            font-size: 0.71rem !important;
            color: #94a3b8 !important;
            line-height: 1.35 !important;
            margin-top: 0.25rem !important;
        }

        /* Input suffix badges */
        .payroll-calc-form .fi-input-wrp-suffix {
            color: #cbd5e1 !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            padding: 0 0.5rem !important;
        }

        /* ── Action Footer Card ── */
        .payroll-calc-actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            background: #000000;
            border: 1px solid #303030;
        }

        .payroll-calc-action-note {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.76rem;
            color: #94a3b8;
            max-width: 650px;
        }
        .payroll-calc-action-note.warning {
            color: #fcd34d;
        }

        .payroll-calc-action-buttons {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        /* ── Formula Reference Section ── */
        .payroll-calc-ref-card {
            background: #000000;
            border: 1px solid #27272a;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        }

        .payroll-calc-ref-header {
            padding: 1.15rem 1.25rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.005) 100%);
            border-bottom: 1px solid #27272a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.85rem 1.25rem;
        }

        .payroll-calc-ref-copy {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .payroll-calc-ref-eyebrow {
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: #38bdf8;
            text-transform: uppercase;
        }

        .payroll-calc-ref-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #f8fafc;
        }

        .payroll-calc-ref-desc {
            margin: 0;
            font-size: 0.78rem;
            color: #94a3b8;
            max-width: 800px;
        }

        .payroll-calc-ref-badges {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .payroll-calc-ref-toolbar {
            padding: 0.75rem 1.25rem;
            background: #0a0e17;
            border-bottom: 1px solid #1e293b;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .payroll-calc-tabs {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        .payroll-calc-tab {
            padding: 0.3rem 0.65rem;
            border-radius: 0.45rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: #94a3b8;
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 120ms ease;
        }
        .payroll-calc-tab:hover {
            color: #f8fafc;
            background: #18181b;
        }
        .payroll-calc-tab.active {
            color: #ffffff;
            background: #1e293b;
            border-color: #38bdf8;
        }

        .payroll-calc-search-input {
            height: 2rem;
            padding: 0.25rem 0.65rem;
            border-radius: 0.4rem;
            border: 1px solid #3f3f46;
            background: #000000;
            color: #f8fafc;
            font-size: 0.75rem;
            outline: none;
            width: 220px;
        }
        .payroll-calc-search-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
        }

        /* ── Formula Cards Grid ── */
        .payroll-calc-formula-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
            padding: 1.25rem;
        }

        .payroll-formula-card {
            background: #090d16;
            border: 1px solid #1e293b;
            border-radius: 0.65rem;
            padding: 0.95rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            transition: all 120ms ease;
        }
        .payroll-formula-card:hover {
            border-color: #38bdf8;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
        }

        .payroll-formula-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .payroll-formula-card-name {
            font-size: 0.85rem;
            font-weight: 750;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .payroll-formula-cat-badge {
            font-size: 0.62rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
        }
        .payroll-formula-cat-badge.sky {
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.25);
        }
        .payroll-formula-cat-badge.indigo {
            color: #818cf8;
            background: rgba(129, 140, 248, 0.12);
            border: 1px solid rgba(129, 140, 248, 0.25);
        }
        .payroll-formula-cat-badge.emerald {
            color: #34d399;
            background: rgba(52, 211, 153, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.25);
        }
        .payroll-formula-cat-badge.amber {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.12);
            border: 1px solid rgba(251, 191, 36, 0.25);
        }
        .payroll-formula-cat-badge.rose {
            color: #fb7185;
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.25);
        }

        .payroll-formula-lock-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.62rem;
            font-weight: 700;
            color: #94a3b8;
            background: #18181b;
            padding: 0.12rem 0.45rem;
            border-radius: 4px;
            border: 1px solid #27272a;
        }

        .payroll-formula-expr-box {
            background: #000000;
            border: 1px solid #27272a;
            border-radius: 0.45rem;
            padding: 0.45rem 0.65rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.76rem;
            color: #60a5fa;
            font-weight: 600;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .payroll-formula-explanation {
            font-size: 0.74rem;
            color: #94a3b8;
            line-height: 1.4;
        }

        /* ── Responsive Media Queries ── */
        @media (max-width: 1100px) {
            .payroll-calc-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .payroll-calc-formula-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .payroll-calc-topbar {
                align-items: stretch;
                padding: 1rem;
            }
            .payroll-calc-title {
                font-size: 1.35rem;
            }
            .payroll-calc-actions-row {
                justify-content: flex-start;
                width: 100%;
            }
            .payroll-calc-kpis {
                grid-template-columns: 1fr;
            }
            .payroll-calc-actions-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .payroll-calc-action-buttons {
                width: 100%;
                justify-content: stretch;
            }
            .payroll-calc-action-buttons > * {
                width: 100%;
            }
            .payroll-calc-search-input {
                width: 100%;
            }
        }
    </style>

    <div class="payroll-calc-workspace">
        {{-- Top Gradient Header --}}
        <header class="payroll-calc-topbar">
            <div class="payroll-calc-topbar-copy">
                <span class="payroll-calc-eyebrow">WORKFORCE RULES & POLICIES</span>
                <h2 class="payroll-calc-title">Period Calculation Rules</h2>
                <p class="payroll-calc-subtitle">
                    Configure cutoff workdays, daily rate divisors, attendance grace, and holiday percentages for this period.
                </p>
            </div>

            <div class="payroll-calc-actions-row">
                <select wire:model.live="periodId" class="payroll-calc-period-select" title="Switch Payroll Period">
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->title }} ({{ $p->is_locked ? 'Locked' : 'Open' }})
                        </option>
                    @endforeach
                </select>

                <a href="{{ \App\Filament\Pages\Payroll::getUrl(['periodId' => $this->period?->publicKey()]) }}" class="payroll-calc-btn outline">
                    <x-filament::icon icon="heroicon-o-arrow-left" class="w-4 h-4" />
                    Return to Payroll
                </a>
            </div>
        </header>

        {{-- Period Status & Dates Context Bar --}}
        <section class="payroll-calc-context-bar" aria-label="Selected Payroll Period Status">
            <div class="payroll-calc-context-left">
                @if ($isLocked)
                    <span class="payroll-calc-pill warning">
                        <x-filament::icon icon="heroicon-m-lock-closed" class="w-3.5 h-3.5" />
                        Locked Period (Read-Only)
                    </span>
                @else
                    <span class="payroll-calc-pill success">
                        <x-filament::icon icon="heroicon-m-lock-open" class="w-3.5 h-3.5" />
                        Open Period (Active & Editable)
                    </span>
                @endif

                <span class="payroll-calc-pill neutral">
                    <x-filament::icon icon="heroicon-m-calendar" class="w-3.5 h-3.5 opacity-60" />
                    <strong>{{ $this->period_display }}</strong>
                    @if ($this->period?->date_start && $this->period?->date_end)
                        <span>({{ Carbon\Carbon::parse($this->period->date_start)->format('M d') }} – {{ Carbon\Carbon::parse($this->period->date_end)->format('M d, Y') }})</span>
                    @endif
                </span>

                @if ($this->period?->date_payout)
                    <span class="payroll-calc-pill neutral">
                        <x-filament::icon icon="heroicon-m-banknotes" class="w-3.5 h-3.5 opacity-60" />
                        Payout: {{ Carbon\Carbon::parse($this->period->date_payout)->format('M d, Y') }}
                    </span>
                @endif
            </div>

            <div class="payroll-calc-context-right">
                <x-filament::icon icon="heroicon-m-shield-check" class="w-4 h-4 text-emerald-400" />
                <span>Formulas below are protected &bull; Rules apply exclusively to this period</span>
            </div>
        </section>

        {{-- KPI Quick-Stats Strip --}}
        <section class="payroll-calc-kpis" aria-label="Rule Baselines Summary">
            <div class="payroll-calc-kpi">
                <div class="payroll-calc-kpi-icon sky">
                    <x-filament::icon icon="heroicon-o-calendar-days" />
                </div>
                <div class="payroll-calc-kpi-body">
                    <span class="payroll-calc-kpi-label">Workdays Baseline</span>
                    <strong class="payroll-calc-kpi-val">{{ $daysPerMonth }} Days / mo</strong>
                    <span class="payroll-calc-kpi-sub">Cutoff: <strong>{{ $halfMonthDays }} Days</strong></span>
                </div>
            </div>

            <div class="payroll-calc-kpi">
                <div class="payroll-calc-kpi-icon indigo">
                    <x-filament::icon icon="heroicon-o-clock" />
                </div>
                <div class="payroll-calc-kpi-body">
                    <span class="payroll-calc-kpi-label">Standard Day & Grace</span>
                    <strong class="payroll-calc-kpi-val">{{ $workHours }} Hours / day</strong>
                    <span class="payroll-calc-kpi-sub">Late Grace: <strong>{{ $lateGrace }} mins</strong></span>
                </div>
            </div>

            <div class="payroll-calc-kpi">
                <div class="payroll-calc-kpi-icon emerald">
                    <x-filament::icon icon="heroicon-o-bolt" />
                </div>
                <div class="payroll-calc-kpi-body">
                    <span class="payroll-calc-kpi-label">Overtime Multiplier</span>
                    <strong class="payroll-calc-kpi-val">{{ number_format($otMult, 2) }}x Rate</strong>
                    <span class="payroll-calc-kpi-sub">Holiday OT: <strong>+{{ $holOtPremium }}% premium</strong></span>
                </div>
            </div>

            <div class="payroll-calc-kpi">
                <div class="payroll-calc-kpi-icon amber">
                    <x-filament::icon icon="heroicon-o-sparkles" />
                </div>
                <div class="payroll-calc-kpi-body">
                    <span class="payroll-calc-kpi-label">Holiday Compensation</span>
                    <strong class="payroll-calc-kpi-val">Reg: {{ $regHolRate }}% | Spec: {{ $specHolRate }}%</strong>
                    <span class="payroll-calc-kpi-sub">Unworked Daily: <strong>{{ $unworkedHol ? 'Paid (1 Day)' : 'Unpaid' }}</strong></span>
                </div>
            </div>
        </section>

        {{-- Form Section Cards --}}
        <form wire:submit.prevent="mountAction('saveSettings')" class="payroll-calc-form">
            {{ $this->form }}

            {{-- Action Footer Card --}}
            <div class="payroll-calc-actions-bar">
                @if ($isLocked)
                    <div class="payroll-calc-action-note warning">
                        <x-filament::icon icon="heroicon-m-lock-closed" class="w-5 h-5 flex-shrink-0" />
                        <span><strong>Locked Payroll Period:</strong> This period is locked to preserve payroll audit history. Calculation rules cannot be modified.</span>
                    </div>
                @else
                    <div class="payroll-calc-action-note">
                        <x-filament::icon icon="heroicon-m-arrow-path" class="w-5 h-5 text-blue-400 flex-shrink-0" />
                        <span><strong>Automatic Recalculation:</strong> Saving updates this period's rules and automatically re-aggregates attendance records for {{ $this->period_display }}.</span>
                    </div>
                @endif

                <div class="payroll-calc-action-buttons">
                    @if (! $isLocked)
                        {{ $this->resetToDefaultsAction }}
                    @endif
                    {{ $this->saveSettingsAction }}
                </div>
            </div>
        </form>

        {{-- Formula Reference Section --}}
        <section
            class="payroll-calc-ref-card"
            x-data="{
                search: '',
                activeTab: 'all',
                matches(item) {
                    const matchesCategory = this.activeTab === 'all' || item.category === this.activeTab;
                    if (!matchesCategory) return false;
                    if (!this.search.trim()) return true;
                    const q = this.search.toLowerCase();
                    return item.name.toLowerCase().includes(q) ||
                           item.formula.toLowerCase().includes(q) ||
                           item.expression.toLowerCase().includes(q);
                }
            }"
        >
            <div class="payroll-calc-ref-header">
                <div class="payroll-calc-ref-copy">
                    <span class="payroll-calc-ref-eyebrow">CALCULATION ENGINE SPECIFICATION</span>
                    <h3 class="payroll-calc-ref-title">Locked Mathematical Formulas</h3>
                    <p class="payroll-calc-ref-desc">
                        These mathematical formulas are locked in the calculation engine and cannot be altered, guaranteeing deterministic and audit-compliant payroll results across all periods.
                    </p>
                </div>
                <div class="payroll-calc-ref-badges">
                    <span class="payroll-calc-pill success">
                        <x-filament::icon icon="heroicon-m-lock-closed" class="w-3.5 h-3.5" />
                        15 Protected Formulas
                    </span>
                    <span class="payroll-calc-pill neutral">
                        <x-filament::icon icon="heroicon-m-check-badge" class="w-3.5 h-3.5 text-blue-400" />
                        Deterministic Arithmetic
                    </span>
                </div>
            </div>

            <div class="payroll-calc-ref-toolbar">
                <div class="payroll-calc-tabs">
                    <button
                        type="button"
                        class="payroll-calc-tab"
                        :class="{ 'active': activeTab === 'all' }"
                        @click="activeTab = 'all'"
                    >
                        All Formulas (15)
                    </button>
                    <button
                        type="button"
                        class="payroll-calc-tab"
                        :class="{ 'active': activeTab === 'Rates & Divisors' }"
                        @click="activeTab = 'Rates & Divisors'"
                    >
                        Rates (2)
                    </button>
                    <button
                        type="button"
                        class="payroll-calc-tab"
                        :class="{ 'active': activeTab === 'Attendance & Hours' }"
                        @click="activeTab = 'Attendance & Hours'"
                    >
                        Attendance (4)
                    </button>
                    <button
                        type="button"
                        class="payroll-calc-tab"
                        :class="{ 'active': activeTab === 'Earnings & Premiums' }"
                        @click="activeTab = 'Earnings & Premiums'"
                    >
                        Earnings (5)
                    </button>
                    <button
                        type="button"
                        class="payroll-calc-tab"
                        :class="{ 'active': activeTab === 'Deductions & Statutory' }"
                        @click="activeTab = 'Deductions & Statutory'"
                    >
                        Deductions (1)
                    </button>
                    <button
                        type="button"
                        class="payroll-calc-tab"
                        :class="{ 'active': activeTab === 'Totals & Summaries' }"
                        @click="activeTab = 'Totals & Summaries'"
                    >
                        Totals (3)
                    </button>
                </div>

                <div>
                    <input
                        type="search"
                        x-model="search"
                        placeholder="Search formulas or rules..."
                        class="payroll-calc-search-input"
                    />
                </div>
            </div>

            <div class="payroll-calc-formula-grid">
                @foreach ($this->formulaRows as $row)
                    @php
                        $cat = $row['category'] ?? 'General';
                        $badgeColor = $row['badge_color'] ?? 'sky';
                        $expr = $row['expression'] ?? '';
                    @endphp
                    <div
                        class="payroll-formula-card"
                        x-show="matches({
                            name: @js($row['name']),
                            category: @js($cat),
                            expression: @js($expr),
                            formula: @js($row['formula'])
                        })"
                    >
                        <div class="payroll-formula-card-top">
                            <span class="payroll-formula-card-name">
                                <span class="payroll-formula-cat-badge {{ $badgeColor }}">{{ $cat }}</span>
                                <span>{{ $row['name'] }}</span>
                            </span>
                            <span class="payroll-formula-lock-tag" title="Protected formula">
                                <x-filament::icon icon="heroicon-m-lock-closed" class="w-3 h-3 text-amber-400" />
                                Locked
                            </span>
                        </div>

                        @if (filled($expr))
                            <div class="payroll-formula-expr-box">{{ $expr }}</div>
                        @endif

                        <div class="payroll-formula-explanation">
                            {{ $row['formula'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
