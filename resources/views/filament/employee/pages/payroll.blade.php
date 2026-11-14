<x-filament-panels::page>
    <style>
        /* Completely Hide Page Header (Title, Subheading, Breadcrumbs) */
        .fi-page-header-main-ctn > header.fi-header,
        .fi-header-heading,
        .fi-header-subheading,
        .fi-breadcrumbs {
            display: none !important;
        }

        .employee-payroll-page {
            color: #0f172a;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-width: 0;
            width: 100%;
        }

        .dark .employee-payroll-page {
            color: #f8fafc;
        }

        /* ── Top Header Bar (Unified Workforce Records Header) ───── */

        .dark .dtr-topbar {
            border-color: #1e293b !important;
            background: linear-gradient(110deg, #0f274a, #08152b 68%, #030814) !important;
        }
        .dtr-topbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            padding: 0.95rem 1.25rem;
            border: 1px solid rgba(59, 130, 246, 0.6);
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8 0%, #173c70 65%, #102a50 100%);
            box-shadow: 0 4px 16px -2px rgba(2, 6, 23, 0.4);
            box-sizing: border-box;
            width: 100%;
        }

        .dtr-topbar-copy {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .dtr-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dtr-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        /* ── Thematic Summary Cards (Unified 4-Card Strip) ───────── */
        .dtr-summary-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.65rem;
            width: 100%;
            box-sizing: border-box;
        }

        .dtr-stat-card {
            position: relative;
            background: #ffffff;
            border: 1px solid rgb(226, 232, 240);
            border-radius: 0.65rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            min-width: 0;
            padding: 0.75rem 0.95rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.35rem;
            overflow: hidden;
            transition: transform 130ms ease, border-color 150ms ease, box-shadow 150ms ease;
            box-sizing: border-box;
        }

        .dtr-stat-card:hover {
            transform: translateY(-1px);
            border-color: rgba(37, 99, 235, 0.4);
            box-shadow: 0 2px 5px -1px rgba(37, 99, 235, 0.08);
        }

        .dark .dtr-stat-card {
            border-color: #1e293b;
            background: #000000;
        }

        .dtr-stat-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 3px;
        }

        .dtr-stat-card.theme-blue::before {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        }
        .dtr-stat-card.theme-purple::before {
            background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);
        }
        .dtr-stat-card.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        .dtr-stat-card.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }

        .dtr-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .dtr-stat-label {
            color: rgb(100, 116, 139);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .dark .dtr-stat-label {
            color: rgb(148, 163, 184);
        }

        .dtr-stat-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 0.4rem;
            flex-shrink: 0;
        }

        .theme-blue .dtr-stat-icon-wrapper {
            background: rgb(239, 246, 255);
            color: #2563eb;
        }
        .dark .theme-blue .dtr-stat-icon-wrapper {
            background: rgba(37, 99, 235, 0.18);
            color: #60a5fa;
        }

        .theme-purple .dtr-stat-icon-wrapper {
            background: rgb(245, 243, 255);
            color: #7c3aed;
        }
        .dark .theme-purple .dtr-stat-icon-wrapper {
            background: rgba(124, 58, 237, 0.18);
            color: #a78bfa;
        }

        .theme-emerald .dtr-stat-icon-wrapper {
            background: rgb(236, 253, 245);
            color: #059669;
        }
        .dark .theme-emerald .dtr-stat-icon-wrapper {
            background: rgba(5, 150, 105, 0.18);
            color: #34d399;
        }

        .theme-amber .dtr-stat-icon-wrapper {
            background: rgb(254, 243, 199);
            color: #d97706;
        }
        .dark .theme-amber .dtr-stat-icon-wrapper {
            background: rgba(217, 119, 6, 0.18);
            color: #fbbf24;
        }

        .dtr-stat-value {
            color: rgb(15, 23, 42);
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.01em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .dtr-stat-value {
            color: #ffffff;
        }

        /* ── Table Container & Header ────────────────────────────── */
        .employee-payroll-table-ctn {
            max-width: 100%;
            min-width: 0;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
            box-sizing: border-box;
            width: 100%;
        }

        .dark .employee-payroll-table-ctn {
            border-color: #1e293b;
            background: #000000;
        }

        .employee-payroll-table-header {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            padding: 0.75rem 1rem;
            background: rgb(248, 250, 252);
            border-bottom: 1px solid rgb(226, 232, 240);
            box-sizing: border-box;
            width: 100%;
        }

        .dark .employee-payroll-table-header {
            background: #0b1120;
            border-bottom-color: #1e293b;
        }

        .employee-payroll-table-heading-wrap {
            flex: 1 1 auto;
            min-width: 0;
        }

        .employee-payroll-table-heading {
            font-size: 0.85rem;
            font-weight: 700;
            line-height: 1.25rem;
            letter-spacing: 0.015em;
            color: #0f172a;
            margin: 0;
        }

        .dark .employee-payroll-table-heading {
            color: #f1f5f9;
        }

        .employee-payroll-table-actions {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            margin: 0;
            padding: 0;
            flex-shrink: 0;
            flex-wrap: nowrap;
        }

        /* ── Dropdown Selection ──────────────────────────────────── */
        .dtr-period-select-wrap {
            width: auto;
            min-width: 200px;
            max-width: 280px;
            flex: 0 1 auto;
            margin: 0;
        }

        .dtr-select-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .dtr-select-icon {
            position: absolute;
            left: 0.65rem;
            width: 0.95rem;
            height: 0.95rem;
            color: rgb(100, 116, 139);
            pointer-events: none;
            z-index: 1;
        }

        .dark .dtr-select-icon {
            color: rgb(148, 163, 184);
        }

        .dtr-select-chevron {
            position: absolute;
            right: 0.65rem;
            width: 0.85rem;
            height: 0.85rem;
            color: rgb(100, 116, 139);
            pointer-events: none;
            z-index: 1;
        }

        .dark .dtr-select-chevron {
            color: rgb(148, 163, 184);
        }

        .dtr-native-select {
            width: 100%;
            height: 2.15rem;
            min-height: 2.15rem;
            box-sizing: border-box;
            padding: 0.25rem 1.75rem 0.25rem 2rem;
            border-radius: 0.45rem;
            border: 1px solid rgb(203, 213, 225);
            background-color: #ffffff;
            color: rgb(15, 23, 42);
            font-size: 0.76rem;
            font-weight: 600;
            line-height: 1.4;
            transition: all 120ms ease;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .dtr-native-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.18);
        }

        .dark .dtr-native-select {
            border-color: #1e293b;
            background-color: #070b12;
            color: #ffffff;
            box-shadow: none;
        }

        .dark .dtr-native-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25);
        }

        .dark .dtr-native-select option {
            background-color: #070b12;
            color: #ffffff;
        }

        /* ── Print Payslip Button ────────────────────────────────── */
        .dtr-print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            min-width: fit-content;
            max-width: none;
            min-height: 2.15rem;
            height: 2.15rem;
            padding: 0.35rem 0.85rem;
            font-size: 0.785rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            border-radius: 0.45rem;
            gap: 0.4rem;
            white-space: nowrap;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            cursor: pointer;
            background-color: #2563eb;
            border: 1px solid #3b82f6;
            color: #ffffff;
            box-sizing: border-box;
            flex-shrink: 0;
        }

        .dtr-print-btn:hover {
            background-color: #1d4ed8;
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .dtr-print-btn svg {
            width: 1.05rem;
            height: 1.05rem;
            min-width: 1.05rem;
            min-height: 1.05rem;
            max-width: 1.05rem;
            max-height: 1.05rem;
            display: inline-block;
            flex-shrink: 0;
            color: #ffffff;
            margin: 0;
            fill: currentColor;
        }

        /* ── Empty State ─────────────────────────────────────────── */
        .employee-payroll-empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            background: #ffffff;
            color: rgb(100, 116, 139);
        }

        .dark .employee-payroll-empty-state {
            background: #000000;
            color: rgb(148, 163, 184);
        }

        .employee-payroll-empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3.25rem;
            height: 3.25rem;
            margin: 0 auto 0.75rem;
            border-radius: 9999px;
            background: rgb(241, 245, 249);
            color: rgb(148, 163, 184);
        }

        .dark .employee-payroll-empty-icon {
            background: rgb(15, 23, 42);
            color: rgb(100, 116, 139);
        }

        .employee-payroll-empty-state h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: rgb(15, 23, 42);
            margin: 0 0 0.25rem;
        }

        .dark .employee-payroll-empty-state h4 {
            color: #f1f5f9;
        }

        .employee-payroll-empty-state p {
            font-size: 0.8rem;
            margin: 0;
            color: inherit;
        }

        /* ── Enhanced Scroll Container & Enlarged Table Rows ─────── */
        .employee-payroll-page .payroll-scroll {
            background: #ffffff;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            scrollbar-color: #2563eb rgb(248, 250, 252);
            scrollbar-width: thin;
        }

        .dark .employee-payroll-page .payroll-scroll {
            background: #000000;
            scrollbar-color: #60a5fa #0f172a;
        }

        .employee-payroll-page .payroll-table {
            font-size: 0.8125rem;
            line-height: 1.35;
            font-variant-numeric: tabular-nums;
            min-width: 2280px;
        }

        .employee-payroll-page .payroll-table th,
        .employee-payroll-page .payroll-table td {
            height: auto;
            min-height: 2.25rem;
            padding: 0.55rem 0.65rem;
            letter-spacing: -0.005em;
        }

        .employee-payroll-page .payroll-table th {
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.025em;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .employee-payroll-page .payroll-table .payroll-col-index,
        .employee-payroll-page .payroll-table th.payroll-col-index,
        .employee-payroll-page .payroll-table td.payroll-col-index {
            width: auto !important;
            min-width: 4.25rem !important;
            max-width: none !important;
            white-space: nowrap !important;
            overflow: visible !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            text-align: center !important;
        }

        .employee-payroll-page .payroll-table tfoot td.payroll-col-index {
            letter-spacing: 0.03em !important;
            font-weight: 800 !important;
        }

        .employee-payroll-page .payroll-table td.payroll-col-name {
            font-weight: 700;
            color: rgb(30, 64, 175);
            white-space: nowrap !important;
            overflow: visible !important;
            min-width: 16rem !important;
            max-width: none !important;
        }

        .dark .employee-payroll-page .payroll-table td.payroll-col-name {
            color: rgb(147, 197, 253);
        }

        .employee-payroll-page .payroll-table td:nth-last-child(2) {
            font-weight: 750;
            color: rgb(21, 128, 61);
        }

        .dark .employee-payroll-page .payroll-table td:nth-last-child(2) {
            color: rgb(74, 222, 128);
        }

        /* Dark theme comprehensive overrides for payroll table */
        .dark .employee-payroll-page .payroll-table th {
            background-color: #070b12 !important;
            color: #93c5fd !important;
            border-color: #1e293b !important;
        }

        .dark .employee-payroll-page .payroll-table td {
            background-color: #000000 !important;
            color: #f8fafc !important;
            border-color: #161f2e !important;
        }

        .dark .employee-payroll-page .payroll-table tbody tr:nth-child(even) td {
            background-color: #050811 !important;
        }

        .dark .employee-payroll-page .payroll-table tbody tr:hover td {
            background-color: #0c1424 !important;
        }

        .dark .employee-payroll-page .payroll-table tfoot td {
            background-color: #0f172a !important;
            border-top: 2px solid #2563eb !important;
            border-color: #1e293b !important;
            color: #60a5fa !important;
        }

        .dark .employee-payroll-page .payroll-table td.payroll-col-name {
            color: #60a5fa !important;
        }



        /* ── Mobile Responsive Layout ────────────────────────────── */
        @media (max-width: 640px) {
            .dtr-topbar {
                align-items: stretch;
                padding: 0.85rem 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .dtr-title {
                font-size: 1.3rem;
            }

            .dtr-summary-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.5rem !important;
            }

            .dtr-stat-card {
                padding: 0.6rem 0.75rem !important;
            }

            .dtr-stat-label {
                font-size: 0.65rem !important;
            }

            .dtr-stat-value {
                font-size: 0.95rem !important;
            }

            .employee-payroll-table-header {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.65rem !important;
                padding: 0.75rem 0.85rem !important;
            }

            .employee-payroll-table-heading-wrap {
                width: 100% !important;
            }

            .employee-payroll-table-heading {
                font-size: 0.825rem !important;
            }

            .employee-payroll-table-actions {
                width: 100% !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 0.45rem !important;
                margin: 0 !important;
                flex-wrap: nowrap !important;
            }

            .employee-payroll-table-actions .dtr-period-select-wrap {
                flex: 1 1 0 !important;
                width: auto !important;
                min-width: 0 !important;
                max-width: none !important;
            }

            .employee-payroll-table-actions .dtr-native-select {
                min-height: 2.15rem !important;
                height: 2.15rem !important;
                padding: 0.25rem 1.45rem 0.25rem 1.85rem !important;
                font-size: 0.735rem !important;
            }

            .employee-payroll-table-actions .dtr-select-icon {
                left: 0.5rem !important;
                width: 0.85rem !important;
                height: 0.85rem !important;
            }

            .employee-payroll-table-actions .dtr-select-chevron {
                right: 0.45rem !important;
                width: 0.75rem !important;
                height: 0.75rem !important;
            }

            .employee-payroll-table-actions .dtr-print-btn {
                flex: 0 0 auto !important;
                min-height: 2.15rem !important;
                height: 2.15rem !important;
                padding: 0.35rem 0.65rem !important;
                font-size: 0.735rem !important;
                gap: 0.3rem !important;
            }

            .employee-payroll-table-actions .dtr-print-btn svg {
                width: 0.95rem !important;
                height: 0.95rem !important;
                min-width: 0.95rem !important;
                min-height: 0.95rem !important;
            }

            .employee-payroll-page .payroll-table {
                font-size: 0.75rem;
            }


        }
    </style>

    <div class="employee-payroll-page">
        {{-- Unified Workforce Records Top Header --}}
        <header class="dtr-topbar">
            <div class="dtr-topbar-copy">
                <p class="dtr-eyebrow">WORKFORCE RECORDS</p>
                <h2 class="dtr-title">Payroll Summary</h2>
            </div>
        </header>

        {{-- Unified Table Card Container with Header, Controls, and Scroll Table --}}
        <section class="employee-payroll-table-ctn">
            <div class="employee-payroll-table-header">
                <div class="employee-payroll-table-heading-wrap">
                    <h3 class="employee-payroll-table-heading">
                        {{ $this->selectedPeriod ? $this->selectedPeriod->title : 'Payroll Summary' }}
                    </h3>
                </div>

                <div class="employee-payroll-table-actions">
                    <div class="dtr-period-select-wrap">
                        <div class="dtr-select-container">
                            <x-filament::icon icon="heroicon-m-calendar" class="dtr-select-icon" />
                            <select wire:model.live="period_id" class="dtr-native-select" aria-label="Payroll Period">
                                <option value="">Select Payroll Period...</option>
                                @foreach ($this->availablePayrollPeriods() as $periodOption)
                                    <option value="{{ $periodOption->id }}" @selected((string) $periodOption->id === (string) $this->period_id)>
                                        {{ $periodOption->title }}
                                    </option>
                                @endforeach
                            </select>
                            <x-filament::icon icon="heroicon-m-chevron-down" class="dtr-select-chevron" />
                        </div>
                    </div>

                    @if ($this->selectedPeriod && (bool) $this->selectedPeriod->is_locked && filled($this->payrollRow))
                        <a href="{{ route('payroll.payslip.print', ['period' => $this->selectedPeriod->publicKey()]) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="dtr-print-btn"
                           title="Print / PDF Payslip">
                            <x-filament::icon icon="heroicon-m-printer" />
                            <span>Print Payslip</span>
                        </a>
                    @endif
                </div>
            </div>

            @if (! $this->selectedPeriod || blank($this->payrollRow))
                <div class="employee-payroll-empty-state">
                    <div class="employee-payroll-empty-icon">
                        <x-filament::icon icon="heroicon-o-banknotes" class="w-8 h-8" />
                    </div>
                    <h4>No Payroll Summary Found</h4>
                    <p>There is no finalized payroll record available for the selected payroll period.</p>
                </div>
            @else
                <div class="payroll-scroll">
                    @include('filament.pages.partials.payroll-detail-table', [
                        'rows' => collect([$this->payrollRow]),
                    ])
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
