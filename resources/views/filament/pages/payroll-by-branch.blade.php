<x-filament-panels::page>
    @php
        $periods = $this->payrollPeriods();
        $branchOptions = $this->branchOptions();
        $selectedPeriod = $this->selectedPeriod;
        $selectedBranch = $this->selectedBranch();
        $metrics = $this->branchMetrics();
        $prevBranchId = $this->previousBranchId();
        $nextBranchId = $this->nextBranchId();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .pbb-workspace {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
        }

        /* Compact Topbar */
        .pbb-topbar {
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
            overflow: hidden;
        }

        .pbb-topbar::before {
            content: none;
        }

        .pbb-topbar-copy {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }

        .pbb-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .pbb-title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
        }

        .pbb-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .pbb-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .pbb-actions-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .pbb-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.38rem 0.72rem;
            border-radius: 0.45rem;
            font-size: 0.75rem;
            font-weight: 650;
            text-decoration: none;
            color: #ffffff;
            background: rgb(37, 99, 235);
            border: 1px solid transparent;
            transition: all 130ms ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .pbb-btn:hover {
            background: rgb(29, 78, 216);
        }

        .pbb-btn.outline {
            color: rgb(30, 41, 59);
            background: #ffffff;
            border-color: rgb(203, 213, 225);
        }

        .pbb-btn.outline:hover {
            background: rgb(248, 250, 252);
            border-color: rgb(148, 163, 184);
        }

        .dark .pbb-btn.outline {
            color: rgb(226, 232, 240);
            background: rgb(30, 41, 59);
            border-color: rgb(71, 85, 105);
        }

        .dark .pbb-btn.outline:hover {
            background: rgb(51, 65, 85);
        }

        .pbb-topbar .pbb-btn {
            min-height: 2.25rem;
            padding: 0.35rem 0.7rem;
            border-radius: 0.45rem;
            font-size: 0.75rem;
        }

        .pbb-topbar .pbb-btn.outline {
            color: #ffffff;
            background: rgb(10 31 68 / 0.6);
            border-color: rgb(147 197 253 / 0.35);
        }

        .pbb-topbar .pbb-btn.outline:hover {
            color: #ffffff;
            background: rgb(10 31 68 / 0.85);
            border-color: rgb(191 219 254 / 0.65);
        }

        .pbb-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .pbb-btn-icon {
            width: 0.9rem;
            height: 0.9rem;
            flex-shrink: 0;
        }

        /* Compact Control & Navigation Bar */
        .pbb-control-card {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            padding: 0.7rem 1rem;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }

        .dark .pbb-control-card {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .pbb-selectors-group {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 0.65rem;
            flex: 1 1 auto;
        }

        .pbb-field {
            display: flex;
            flex-direction: column;
            gap: 0.22rem;
            min-width: 13.5rem;
        }

        .pbb-label {
            font-size: 0.67rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(71, 85, 105);
        }

        .dark .pbb-label {
            color: rgb(203, 213, 225);
        }

        .pbb-select {
            width: 100%;
            height: 2.1rem;
            padding: 0.25rem 0.55rem;
            border-radius: 0.42rem;
            border: 1px solid rgb(203, 213, 225);
            background: #ffffff;
            color: rgb(15, 23, 42);
            font-size: 0.78rem;
            font-weight: 600;
            outline: none;
        }

        .pbb-select:focus {
            border-color: rgb(37, 99, 235);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.16);
        }

        .dark .pbb-select {
            border-color: rgb(71, 85, 105);
            background: rgb(30, 41, 59);
            color: #ffffff;
        }

        .pbb-branch-stepper {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .pbb-step-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.1rem;
            height: 2.1rem;
            border-radius: 0.42rem;
            border: 1px solid rgb(203, 213, 225);
            background: rgb(248, 250, 252);
            color: rgb(30, 41, 59);
            cursor: pointer;
            transition: all 120ms ease;
            flex-shrink: 0;
        }

        .pbb-step-btn:hover:not(:disabled) {
            background: rgb(226, 232, 240);
            border-color: rgb(148, 163, 184);
        }

        .pbb-step-btn:disabled {
            opacity: 0.38;
            cursor: not-allowed;
        }

        .dark .pbb-step-btn {
            border-color: rgb(71, 85, 105);
            background: rgb(30, 41, 59);
            color: rgb(226, 232, 240);
        }

        .dark .pbb-step-btn:hover:not(:disabled) {
            background: rgb(51, 65, 85);
        }

        /* Compact Live Branch Totals Strip */
        .pbb-totals-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .pbb-total-chip {
            display: flex;
            flex-direction: column;
            padding: 0.25rem 0.65rem;
            border-radius: 0.45rem;
            border: 1px solid rgb(226, 232, 240);
            background: rgb(248, 250, 252);
            min-width: 6.75rem;
        }

        .dark .pbb-total-chip {
            border-color: rgb(51, 65, 85);
            background: rgb(30, 41, 59);
        }

        .pbb-total-chip.net {
            border-color: rgba(22, 163, 74, 0.35);
            background: rgba(240, 253, 244, 0.85);
        }

        .dark .pbb-total-chip.net {
            border-color: rgba(34, 197, 94, 0.35);
            background: rgba(20, 83, 45, 0.25);
        }

        .pbb-total-chip-label {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(100, 116, 139);
        }

        .dark .pbb-total-chip-label {
            color: rgb(148, 163, 184);
        }

        .pbb-total-chip-value {
            font-size: 0.82rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: rgb(15, 23, 42);
            line-height: 1.2;
        }

        .dark .pbb-total-chip-value {
            color: #f8fafc;
        }

        .pbb-total-chip.net .pbb-total-chip-value {
            color: rgb(21, 128, 61);
        }

        .dark .pbb-total-chip.net .pbb-total-chip-value {
            color: rgb(74, 222, 128);
        }

        .export-title {
            display: none;
        }

        .payroll-print-table {
            display: none;
        }

        .payroll-screen-table {
            max-width: 100%;
            min-width: 0;
        }

        @media (max-width: 960px) {
            .pbb-control-card {
                flex-direction: column;
                align-items: stretch;
            }

            .pbb-field {
                flex: 1 1 12rem;
            }

            .pbb-totals-strip {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .pbb-topbar {
                align-items: stretch;
                padding: 1rem;
            }

            .pbb-title {
                font-size: 1.35rem;
            }

            .pbb-actions-row {
                justify-content: flex-start;
                width: 100%;
            }

            .pbb-totals-strip {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            @page {
                margin: 0;
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            body * {
                visibility: hidden !important;
            }

            .payroll-print-area,
            .payroll-print-area * {
                visibility: visible !important;
            }

            .payroll-print-area {
                background: #fff !important;
                color: #000 !important;
                position: absolute;
                inset: 0 auto auto 0;
                padding: 8mm;
                width: 100%;
            }

            .payroll-print-area * {
                color: #000 !important;
            }

            .export-title {
                display: block !important;
            }

            .export-title h2,
            .export-title p,
            .payroll-section-title {
                color: #000 !important;
            }

            .payroll-screen-table {
                display: none !important;
            }

            .payroll-print-table {
                display: block !important;
            }
        }
    </style>

    <div class="pbb-workspace">
        {{-- Topbar Header --}}
        <header class="pbb-topbar">
            <div class="pbb-topbar-copy">
                <span class="pbb-eyebrow">WORKFORCE RECORDS</span>
                <div class="pbb-title-row">
                    <h2 class="pbb-title">
                        {{ $this->selectedScopeName() }}
                    </h2>
                </div>
                <p class="pbb-subtitle">
                    Payroll Period: <strong>{{ $selectedPeriod?->title ?: 'None selected' }}</strong>
                    @if ($selectedPeriod?->date_payout)
                        • Payout: {{ \Carbon\Carbon::parse($selectedPeriod->date_payout)->format('M d, Y') }}
                    @endif
                </p>
            </div>

            <div class="pbb-actions-row">
                <a href="{{ $this->returnUrl() }}" class="pbb-btn outline">
                    <x-filament::icon icon="heroicon-o-arrow-left" class="pbb-btn-icon" />
                    Return
                </a>

                @if ($this->payrollSummaryUrl())
                    <a href="{{ $this->payrollSummaryUrl() }}" class="pbb-btn outline">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="pbb-btn-icon" />
                        Summary
                    </a>
                @endif

                <button type="button" wire:click="mountAction('signatories')" class="pbb-btn outline">
                    <x-filament::icon icon="heroicon-o-user-group" class="pbb-btn-icon" />
                    Signatories
                </button>

                <x-filament::dropdown placement="bottom-end" teleport>
                    <x-slot name="trigger">
                        <button type="button" class="pbb-btn">
                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="pbb-btn-icon" />
                            <span>Export / Print</span>
                            <x-filament::icon icon="heroicon-m-chevron-down" class="pbb-btn-icon" />
                        </button>
                    </x-slot>

                    <x-filament::dropdown.list>
                        <x-filament::dropdown.list.item
                            wire:click="exportExcel"
                            icon="heroicon-o-table-cells"
                        >
                            Export Excel
                        </x-filament::dropdown.list.item>

                        @if ($this->printUrl())
                            <x-filament::dropdown.list.item
                                :href="$this->printUrl()"
                                target="_blank"
                                tag="a"
                                icon="heroicon-o-printer"
                            >
                                Print / PDF
                            </x-filament::dropdown.list.item>
                        @endif
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>
        </header>

        {{-- Compact Filter, Branch Stepper, Column Preset & Totals Bar --}}
        <section class="pbb-control-card" aria-label="Payroll Generator Controls">
            <div class="pbb-selectors-group">
                <div class="pbb-field">
                    <label class="pbb-label" for="pbb-scope-select">Generate By</label>
                    <select id="pbb-scope-select" wire:model.live="scope_type" class="pbb-select">
                        <option value="branch">Branch</option>
                        <option value="department">Department</option>
                        <option value="all">All Branches</option>
                    </select>
                </div>
                <div class="pbb-field">
                    <label class="pbb-label" for="pbb-period-select">Payroll Period</label>
                    <select id="pbb-period-select" wire:model.live="period_id" class="pbb-select">
                        @foreach ($periods as $periodOption)
                            <option value="{{ $periodOption->id }}">
                                {{ $periodOption->title }} ({{ $periodOption->is_locked ? 'Locked' : 'Open' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($this->scope_type === 'branch')
                <div class="pbb-field" style="min-width: 15.5rem;">
                    <label class="pbb-label" for="pbb-branch-select">Branch / Station</label>
                    <div class="pbb-branch-stepper">
                        <button
                            type="button"
                            wire:click="selectPreviousBranch"
                            @disabled(blank($prevBranchId))
                            title="Previous Branch"
                            class="pbb-step-btn"
                        >
                            <x-filament::icon icon="heroicon-m-chevron-left" class="pbb-btn-icon" />
                        </button>

                        <select id="pbb-branch-select" wire:model.live="branch_id" class="pbb-select">
                            @forelse ($branchOptions as $bId => $bName)
                                <option value="{{ $bId }}">{{ $bName }}</option>
                            @empty
                                <option value="">No branches available</option>
                            @endforelse
                        </select>

                        <button
                            type="button"
                            wire:click="selectNextBranch"
                            @disabled(blank($nextBranchId))
                            title="Next Branch"
                            class="pbb-step-btn"
                        >
                            <x-filament::icon icon="heroicon-m-chevron-right" class="pbb-btn-icon" />
                        </button>
                    </div>
                </div>
                @elseif ($this->scope_type === 'department')
                <div class="pbb-field" style="min-width: 15.5rem;">
                    <label class="pbb-label" for="pbb-department-select">Department</label>
                    <select id="pbb-department-select" wire:model.live="department_id" class="pbb-select">
                        @foreach ($this->departmentOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="pbb-totals-strip" aria-label="Payroll Totals">
                <div class="pbb-total-chip">
                    <span class="pbb-total-chip-label">Gross Pay</span>
                    <span class="pbb-total-chip-value">₱{{ number_format($metrics['gross_pay'], 2) }}</span>
                </div>
                <div class="pbb-total-chip">
                    <span class="pbb-total-chip-label">Deductions</span>
                    <span class="pbb-total-chip-value">₱{{ number_format($metrics['total_deductions'], 2) }}</span>
                </div>
                <div class="pbb-total-chip net">
                    <span class="pbb-total-chip-label">Net Pay</span>
                    <span class="pbb-total-chip-value">₱{{ number_format($metrics['net_pay'], 2) }}</span>
                </div>
            </div>
        </section>

        {{-- Table & Print Area --}}
        <div class="payroll-print-area" style="display: grid; gap: 0.75rem;">
            @include('filament.pages.partials.company-export-header')

            <div class="export-title">
                <h2 style="font-size: 18px; margin-bottom: 4px;">
                    {{ $this->selectedScopeName() }}
                </h2>
                <p>
                    Payroll period: {{ $selectedPeriod?->title ?: '-' }}
                </p>
            </div>

            <div class="payroll-screen-table">
                <livewire:payroll-detail-table
                    :period-id="(int) $this->period_id"
                    :branch-id="$this->scope_type === 'branch' && filled($this->branch_id) ? (int) $this->branch_id : null"
                    :department-id="$this->scope_type === 'department' && filled($this->department_id) ? (int) $this->department_id : null"
                    :enable-search="true"
                    :initial-search="$this->payroll_search"
                    :initial-page="$this->payroll_page"
                    :initial-per-page="$this->payroll_per_page"
                    :initial-preset="$this->payroll_preset"
                    :show-column-presets="false"
                    :show-totals="true"
                    :key="'payroll-'.$this->period_id.'-'.$this->scope_type.'-'.$this->branch_id.'-'.$this->department_id.'-'.$this->payroll_page"
                />
            </div>
            <div class="payroll-print-table">
                @include('filament.pages.partials.payroll-detail-table', [
                    'rows' => $this->rows,
                ])
            </div>

            @include('filament.pages.partials.payroll-signatories', [
                'preparedBy' => $this->prepared_by,
                'checkedBy' => $this->checked_by,
                'approvedBy' => $this->approved_by,
            ])

            @include('filament.pages.partials.export-generated-at')
        </div>
    </div>
</x-filament-panels::page>
