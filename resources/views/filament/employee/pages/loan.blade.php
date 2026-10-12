<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getLoanStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "EMPLOYEE LOAN MANAGEMENT";
        }

        .emp-loan-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        /* KPI Summary Strip */
        .emp-loan-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .emp-loan-kpi {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            padding: 0.7rem 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            transition: all 120ms ease;
        }

        .emp-loan-kpi:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .dark .emp-loan-kpi {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .emp-loan-kpi:hover {
            border-color: #3b82f6;
        }

        .emp-loan-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .emp-loan-kpi.primary {
            background: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .emp-loan-icon {
            padding: 0.45rem;
            border-radius: 0.45rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .emp-loan-icon {
            background: #1e293b;
            border-color: #334155;
        }

        .emp-loan-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.12rem;
        }

        .emp-loan-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .emp-loan-label {
            color: #94a3b8;
        }

        .emp-loan-val {
            font-size: 1.15rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .emp-loan-val {
            color: #f8fafc;
        }

        .emp-loan-subtext {
            font-size: 0.67rem;
            color: #64748b;
        }

        .dark .emp-loan-subtext {
            color: #94a3b8;
        }

        .emp-loan-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.08rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.64rem;
            font-weight: 750;
            width: fit-content;
            margin-top: 0.1rem;
        }

        .emp-loan-badge.amber {
            background: #fef3c7;
            color: #b45309;
        }

        .dark .emp-loan-badge.amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .emp-loan-badge.green {
            background: #dcfce7;
            color: #15803d;
        }

        .dark .emp-loan-badge.green {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .emp-loan-badge.blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .dark .emp-loan-badge.blue {
            background: rgba(37, 99, 235, 0.2);
            color: #93c5fd;
        }

        /* Modern Tabs Pill Styling */
        .emp-loan-nav {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.45rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow-x: auto;
        }

        .dark .emp-loan-nav {
            background: #0f172a;
            border-color: #334155;
        }

        .emp-loan-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 0.45rem;
            color: #64748b;
            background: transparent;
            transition: all 120ms ease;
            text-decoration: none;
            white-space: nowrap;
            min-height: 2.15rem;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .dark .emp-loan-tab {
            color: #94a3b8;
        }

        .emp-loan-tab:hover {
            color: #0f172a;
            background: #f1f5f9;
        }

        .dark .emp-loan-tab:hover {
            color: #f8fafc;
            background: #1e293b;
        }

        .emp-loan-tab.is-active {
            color: #ffffff !important;
            background: #2563eb !important;
            border-color: #2563eb !important;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.28);
        }

        .emp-loan-tab.is-active :is(svg, span) {
            color: #ffffff !important;
        }

        .emp-loan-tab-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #64748b;
            transition: all 120ms ease;
        }

        .dark .emp-loan-tab-badge {
            background: #1e293b;
            color: #94a3b8;
        }

        .emp-loan-tab.is-active .emp-loan-tab-badge {
            background: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
        }

        /* Compact Table Overrides */
        .emp-loan-page .fi-ta-ctn {
            border-radius: 0.55rem;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #2563eb;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .emp-loan-page .fi-ta-ctn {
            background: #0f172a;
            border-color: #334155;
            border-top-color: #3b82f6;
        }

        .emp-loan-page .fi-ta-header-cell {
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            padding: 0.55rem 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .dark .emp-loan-page .fi-ta-header-cell {
            background: #1e293b;
            border-bottom-color: #334155;
            color: #94a3b8;
        }

        .emp-loan-page .fi-ta-cell {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        .emp-loan-page .fi-ta-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 100ms ease;
        }

        .dark .emp-loan-page .fi-ta-table tbody tr {
            border-bottom-color: #1e293b;
        }

        .emp-loan-page .fi-ta-table tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .dark .emp-loan-page .fi-ta-table tbody tr:hover {
            background-color: #1e293b !important;
        }

        .emp-loan-page .fi-ta-actions {
            gap: 0.35rem;
        }

        .emp-loan-page .fi-ta-actions .fi-btn {
            padding: 0.25rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }

        @media (max-width: 640px) {
            .emp-loan-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="emp-loan-page employee-blue-page">
        {{-- KPI Summary Strip --}}
        <div class="emp-loan-kpis">
            <div class="emp-loan-kpi primary">
                <div class="emp-loan-icon">
                    <x-filament::icon icon="heroicon-m-banknotes" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="emp-loan-body">
                    <span class="emp-loan-label">OUTSTANDING BALANCE</span>
                    <div class="emp-loan-val text-blue-700 dark:text-blue-300">₱{{ number_format($stats['total_balance'], 2) }}</div>
                    <span class="emp-loan-subtext">{{ $stats['active_count'] }} active {{ \Illuminate\Support\Str::plural('loan', $stats['active_count']) }}</span>
                </div>
            </div>

            <div class="emp-loan-kpi">
                <div class="emp-loan-icon">
                    <x-filament::icon icon="heroicon-m-check-badge" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="emp-loan-body">
                    <span class="emp-loan-label">TOTAL AMORTIZED</span>
                    <div class="emp-loan-val text-emerald-700 dark:text-emerald-300">₱{{ number_format($stats['total_paid'], 2) }}</div>
                    <span class="emp-loan-badge green">Paid to date</span>
                </div>
            </div>

            <div class="emp-loan-kpi">
                <div class="emp-loan-icon">
                    <x-filament::icon icon="heroicon-m-clock" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="emp-loan-body">
                    <span class="emp-loan-label">PENDING REQUESTS</span>
                    <div class="emp-loan-val {{ $stats['pending_requests'] > 0 ? 'text-amber-700 dark:text-amber-300' : '' }}">
                        {{ $stats['pending_requests'] }}
                    </div>
                    <span class="emp-loan-badge {{ $stats['pending_requests'] > 0 ? 'amber' : 'blue' }}">
                        {{ $stats['pending_requests'] > 0 ? 'Under HR review' : 'No pending' }}
                    </span>
                </div>
            </div>

            <div class="emp-loan-kpi">
                <div class="emp-loan-icon">
                    <x-filament::icon icon="heroicon-m-calculator" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div class="emp-loan-body">
                    <span class="emp-loan-label">LIFETIME BORROWED</span>
                    <div class="emp-loan-val text-purple-700 dark:text-purple-300">₱{{ number_format($stats['total_borrowing'], 2) }}</div>
                    <span class="emp-loan-subtext">{{ $stats['total_loans'] }} total recorded</span>
                </div>
            </div>
        </div>

        {{-- Navigation Pill Tabs --}}
        <nav class="emp-loan-nav" aria-label="Employee loan records">
            <button
                type="button"
                class="emp-loan-tab {{ $activeLoanSection === 'loans' ? 'is-active' : '' }}"
                wire:click="showLoanSection('loans')"
                wire:loading.attr="disabled"
                wire:target="showLoanSection"
                aria-selected="{{ $activeLoanSection === 'loans' ? 'true' : 'false' }}"
            >
                <x-filament::icon icon="heroicon-m-banknotes" class="w-4 h-4" />
                <span>My Loans</span>
                <span class="emp-loan-tab-badge">{{ $stats['active_count'] }}</span>
            </button>

            <button
                type="button"
                class="emp-loan-tab {{ $activeLoanSection === 'requests' ? 'is-active' : '' }}"
                wire:click="showLoanSection('requests')"
                wire:loading.attr="disabled"
                wire:target="showLoanSection"
                aria-selected="{{ $activeLoanSection === 'requests' ? 'true' : 'false' }}"
            >
                <x-filament::icon icon="heroicon-m-clock" class="w-4 h-4" />
                <span>Loan Request History</span>
                @if ($stats['pending_requests'] > 0)
                    <span class="emp-loan-tab-badge text-amber-700 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300">{{ $stats['pending_requests'] }}</span>
                @endif
            </button>
        </nav>

        {{-- Table Content --}}
        <section class="emp-loan-content">
            @if ($activeLoanSection === 'loans')
                <div wire:key="employee-my-loans">
                    {{ $this->table }}
                </div>
            @else
                <div wire:key="employee-loan-request-history">
                    <livewire:employee-loan-request-history-table />
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>

