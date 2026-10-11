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
            --ep-surface: #ffffff;
            --ep-surface-muted: #f8fafc;
            --ep-text: #0f172a;
            --ep-muted: #64748b;
            --ep-border: rgba(226, 232, 240, 0.9);
            --ep-blue: #2563eb;
            --ep-blue-soft: #eff6ff;

            color: var(--ep-text);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-width: 0;
            width: 100%;
        }

        .dark .employee-payroll-page {
            --ep-surface: #0f172a;
            --ep-surface-muted: #1e293b;
            --ep-text: #f8fafc;
            --ep-muted: #94a3b8;
            --ep-border: rgba(51, 65, 85, 0.9);
            --ep-blue: #60a5fa;
            --ep-blue-soft: rgba(37, 99, 235, 0.16);
        }

        /* Filter / Toolbar Section Card (Exact Match to Employee DTR) */
        .employee-payroll-toolbar {
            position: relative;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            padding: 0.75rem 1.15rem;
            border-radius: 0.75rem;
            border: 1px solid #3b82f6;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            box-shadow: none;
            overflow: visible;
        }

        .employee-payroll-toolbar::before {
            display: none;
        }

        .dark .employee-payroll-toolbar {
            border-color: #3b82f6;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            box-shadow: none;
        }

        .employee-payroll-heading {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-shrink: 0;
        }

        .employee-payroll-heading-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(219, 234, 254, .35);
            color: #ffffff;
            flex-shrink: 0;
        }

        .dark .employee-payroll-heading-icon {
            background: rgba(255, 255, 255, .12);
            color: #ffffff;
        }

        .employee-payroll-heading-copy {
            display: flex;
            flex-direction: column;
        }

        .employee-payroll-eyebrow {
            color: #d9f722;
            font-size: .65rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: .2rem;
            text-transform: uppercase;
        }

        .employee-payroll-heading h3 {
            font-size: 0.95rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .dark .employee-payroll-heading h3 {
            color: #ffffff;
        }

        .employee-payroll-heading p {
            font-size: 0.75rem;
            color: #e0ecff;
            margin: 0.15rem 0 0;
        }

        .dark .employee-payroll-heading p {
            color: #e0ecff;
        }

        .employee-payroll-controls {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .employee-payroll-period-select-wrap {
            width: 270px;
            min-width: 230px;
            max-width: 320px;
            flex-shrink: 0;
        }

        .employee-payroll-select-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .employee-payroll-select-icon {
            position: absolute;
            left: 0.65rem;
            width: 0.95rem;
            height: 0.95rem;
            color: #bfdbfe;
            pointer-events: none;
            z-index: 1;
        }

        .dark .employee-payroll-select-icon {
            color: #bfdbfe;
        }

        .employee-payroll-select-chevron {
            position: absolute;
            right: 0.65rem;
            width: 0.85rem;
            height: 0.85rem;
            color: #bfdbfe;
            pointer-events: none;
            z-index: 1;
        }

        .dark .employee-payroll-select-chevron {
            color: #bfdbfe;
        }

        .employee-payroll-native-select {
            width: 100%;
            height: 2.15rem;
            min-height: 2.15rem;
            box-sizing: border-box;
            padding: 0.25rem 1.85rem 0.25rem 2rem;
            border-radius: 0.45rem;
            border: 1px solid rgba(191, 219, 254, .45);
            background-color: rgba(10, 31, 68, .55);
            color: #ffffff;
            font-size: 0.775rem;
            font-weight: 600;
            line-height: 1.4;
            transition: all 120ms ease;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            box-shadow: none;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .employee-payroll-native-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.18);
        }

        .dark .employee-payroll-native-select {
            border-color: rgba(191, 219, 254, .45);
            background-color: rgba(10, 31, 68, .55);
            color: #ffffff;
            box-shadow: none;
        }

        .dark .employee-payroll-native-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25);
        }

        .dark .employee-payroll-native-select option {
            background-color: #173c70;
            color: #ffffff;
        }

        .employee-payroll-print-btn {
            position: relative;
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.8rem;
            min-height: 2.15rem;
            height: 2.15rem;
            box-sizing: border-box;
            border-radius: 0.45rem;
            font-size: 0.775rem;
            font-weight: 600;
            text-decoration: none;
            color: #ffffff;
            background: rgba(10, 31, 68, .55);
            border: 1px solid rgba(191, 219, 254, .45);
            box-shadow: none;
            transition: all 130ms ease;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .employee-payroll-print-btn:hover {
            background: rgba(10, 31, 68, .85);
            border-color: rgba(191, 219, 254, .75);
            color: #ffffff;
        }

        .employee-payroll-print-btn svg {
            width: 0.9rem;
            height: 0.9rem;
            flex-shrink: 0;
            color: #bfdbfe;
            transition: color 130ms ease;
        }

        .employee-payroll-print-btn:hover svg {
            color: #ffffff;
        }

        .dark .employee-payroll-print-btn {
            color: #ffffff;
            background: rgba(10, 31, 68, .55);
            border-color: rgba(191, 219, 254, .45);
            box-shadow: none;
        }

        .dark .employee-payroll-print-btn:hover {
            background: rgba(10, 31, 68, .85);
            border-color: rgba(191, 219, 254, .75);
            color: #ffffff;
        }

        .dark .employee-payroll-print-btn svg {
            color: #bfdbfe;
        }

        .dark .employee-payroll-print-btn:hover svg {
            color: #ffffff;
        }

        /* Thematic Stat & Information Cards (Exact Match to /hr/dtr) */
        .employee-payroll-card {
            display: grid;
            gap: 0.65rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .employee-payroll-item {
            position: relative;
            background: var(--ep-surface);
            border: 1px solid var(--ep-border);
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
        }

        .employee-payroll-item:hover {
            transform: translateY(-1px);
            border-color: rgba(37, 99, 235, 0.4);
            box-shadow: 0 2px 5px -1px rgba(37, 99, 235, 0.08);
        }

        .dark .employee-payroll-item {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .employee-payroll-item::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 3px;
        }

        .employee-payroll-item.theme-blue::before {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        }
        .employee-payroll-item.theme-purple::before {
            background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);
        }
        .employee-payroll-item.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        .employee-payroll-item.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }

        .employee-payroll-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .employee-payroll-label {
            color: rgb(100, 116, 139);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .dark .employee-payroll-label {
            color: rgb(148, 163, 184);
        }

        .employee-payroll-item-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 0.4rem;
            flex-shrink: 0;
        }

        .theme-blue .employee-payroll-item-icon-wrapper {
            background: rgb(239, 246, 255);
            color: #2563eb;
        }
        .dark .theme-blue .employee-payroll-item-icon-wrapper {
            background: rgba(37, 99, 235, 0.18);
            color: #60a5fa;
        }

        .theme-purple .employee-payroll-item-icon-wrapper {
            background: rgb(245, 243, 255);
            color: #7c3aed;
        }
        .dark .theme-purple .employee-payroll-item-icon-wrapper {
            background: rgba(124, 58, 237, 0.18);
            color: #a78bfa;
        }

        .theme-emerald .employee-payroll-item-icon-wrapper {
            background: rgb(236, 253, 245);
            color: #059669;
        }
        .dark .theme-emerald .employee-payroll-item-icon-wrapper {
            background: rgba(5, 150, 105, 0.18);
            color: #34d399;
        }

        .theme-amber .employee-payroll-item-icon-wrapper {
            background: rgb(254, 243, 199);
            color: #d97706;
        }
        .dark .theme-amber .employee-payroll-item-icon-wrapper {
            background: rgba(217, 119, 6, 0.18);
            color: #fbbf24;
        }

        .employee-payroll-value {
            color: rgb(15, 23, 42);
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.01em;
            overflow-wrap: anywhere;
        }

        .dark .employee-payroll-value {
            color: #ffffff;
        }

        .employee-payroll-empty {
            background: var(--ep-surface);
            border: 1px dashed var(--ep-border);
            border-radius: 0.75rem;
            color: var(--ep-muted);
            font-size: 0.875rem;
            font-weight: 600;
            padding: 2.5rem;
            text-align: center;
        }

        /* Enhanced Scroll Container & Enlarged Table Rows */
        .employee-payroll-page .payroll-scroll {
            background: var(--ep-surface);
            border: 1px solid var(--ep-border);
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            scrollbar-color: var(--ep-blue) var(--ep-surface-muted);
            scrollbar-width: thin;
        }

        .employee-payroll-page .payroll-table {
            font-size: 0.8125rem; /* ~13px for readable, comfortably sized rows */
            line-height: 1.35;
            font-variant-numeric: tabular-nums;
            min-width: 2280px;
        }

        .employee-payroll-page .payroll-table th,
        .employee-payroll-page .payroll-table td {
            height: auto;
            min-height: 2.25rem;
            padding: 0.55rem 0.65rem; /* Generous, professional cell padding */
            letter-spacing: -0.005em;
        }

        .employee-payroll-page .payroll-table th {
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.025em;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .employee-payroll-page .payroll-table td.payroll-col-name {
            font-weight: 700;
            color: rgb(30, 64, 175);
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

        @media (max-width: 900px) {
            .employee-payroll-card {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {
            .employee-payroll-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .employee-payroll-controls {
                flex-wrap: wrap;
                justify-content: flex-start;
            }

            .employee-payroll-period-select-wrap {
                max-width: 100%;
                width: 100%;
                min-width: 0;
            }
        }

        @media (max-width: 560px) {
            .employee-payroll-card {
                grid-template-columns: 1fr;
            }

            .employee-payroll-page .payroll-table {
                font-size: 0.75rem;
            }
        }
    </style>

    <div class="employee-payroll-page">
        {{-- Toolbar / Filter Bar with Horizontal Dropdown and Print Button --}}
        <section class="employee-payroll-toolbar">
            <div class="employee-payroll-heading">
                <div class="employee-payroll-heading-icon">
                    <x-filament::icon icon="heroicon-o-banknotes" class="w-4 h-4" />
                </div>
                <div class="employee-payroll-heading-copy">
                    <span class="employee-payroll-eyebrow">Employee Self Service</span>
                    <h3>Payroll Summary</h3>
                    <p>View your finalized payroll and compensation breakdown by period.</p>
                </div>
            </div>

            <div class="employee-payroll-controls">
                <div class="employee-payroll-period-select-wrap">
                    <div class="employee-payroll-select-container">
                        <x-filament::icon icon="heroicon-m-calendar" class="employee-payroll-select-icon" />
                        <select wire:model.live="period_id" class="employee-payroll-native-select" aria-label="Payroll Period">
                            <option value="">Select Payroll Period...</option>
                            @foreach ($this->availablePayrollPeriods() as $periodOption)
                                <option value="{{ $periodOption->id }}" @selected((string) $periodOption->id === (string) $this->period_id)>
                                    {{ $periodOption->title }}
                                </option>
                            @endforeach
                        </select>
                        <x-filament::icon icon="heroicon-m-chevron-down" class="employee-payroll-select-chevron" />
                    </div>
                </div>

                @if ($this->selectedPeriod && (bool) $this->selectedPeriod->is_locked && filled($this->payrollRow))
                    <a href="{{ route('payroll.payslip.print', ['period' => $this->selectedPeriod->publicKey()]) }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="employee-payroll-print-btn"
                       title="Print / PDF Payslip">
                        <x-filament::icon icon="heroicon-m-printer" />
                        <span>Print / PDF Payslip</span>
                    </a>
                @endif
            </div>
        </section>

        @if (! $this->selectedPeriod)
            <div class="employee-payroll-empty">No payroll summary available yet.</div>
        @elseif ($this->employee)
            <div class="employee-payroll-card">
                {{-- Card 1: Employee (Blue Theme) --}}
                <div class="employee-payroll-item theme-blue">
                    <div class="employee-payroll-item-header">
                        <span class="employee-payroll-label">Employee</span>
                        <div class="employee-payroll-item-icon-wrapper">
                            <x-filament::icon icon="heroicon-o-user" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="employee-payroll-value">{{ $this->employee->full_name }}</div>
                </div>

                {{-- Card 2: Designation (Purple Theme) --}}
                <div class="employee-payroll-item theme-purple">
                    <div class="employee-payroll-item-header">
                        <span class="employee-payroll-label">Designation</span>
                        <div class="employee-payroll-item-icon-wrapper">
                            <x-filament::icon icon="heroicon-o-identification" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="employee-payroll-value">{{ $this->employee->designation?->title ?: '-' }}</div>
                </div>

                {{-- Card 3: Department (Emerald Theme) --}}
                <div class="employee-payroll-item theme-emerald">
                    <div class="employee-payroll-item-header">
                        <span class="employee-payroll-label">Department</span>
                        <div class="employee-payroll-item-icon-wrapper">
                            <x-filament::icon icon="heroicon-o-briefcase" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="employee-payroll-value">{{ $this->employee->department?->name ?: '-' }}</div>
                </div>

                {{-- Card 4: Branch (Amber Theme) --}}
                <div class="employee-payroll-item theme-amber">
                    <div class="employee-payroll-item-header">
                        <span class="employee-payroll-label">Branch</span>
                        <div class="employee-payroll-item-icon-wrapper">
                            <x-filament::icon icon="heroicon-o-building-storefront" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="employee-payroll-value">{{ $this->employee->branch?->branch_name ?: '-' }}</div>
                </div>
            </div>

            @include('filament.pages.partials.payroll-detail-table', [
                'rows' => $this->payrollRow ? collect([$this->payrollRow]) : collect(),
            ])
        @endif
    </div>
</x-filament-panels::page>
