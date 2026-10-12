<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getStationDtrStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "STATION MANAGEMENT";
        }

        .emp-station-dtr-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        /* KPI Strip */
        .emp-station-dtr-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .emp-station-dtr-kpi {
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

        .emp-station-dtr-kpi:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .dark .emp-station-dtr-kpi {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .emp-station-dtr-kpi:hover {
            border-color: #3b82f6;
        }

        .emp-station-dtr-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .emp-station-dtr-kpi.primary {
            background: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .emp-station-dtr-icon {
            padding: 0.45rem;
            border-radius: 0.45rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .emp-station-dtr-icon {
            background: #1e293b;
            border-color: #334155;
        }

        .emp-station-dtr-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.12rem;
        }

        .emp-station-dtr-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .emp-station-dtr-label {
            color: #94a3b8;
        }

        .emp-station-dtr-val {
            font-size: 1.15rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .emp-station-dtr-val {
            color: #f8fafc;
        }

        .emp-station-dtr-subtext {
            font-size: 0.67rem;
            color: #64748b;
        }

        .dark .emp-station-dtr-subtext {
            color: #94a3b8;
        }

        /* Compact Modern Table */
        .emp-station-dtr-page .fi-ta-ctn {
            border-radius: 0.55rem;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #2563eb;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .emp-station-dtr-page .fi-ta-ctn {
            background: #0f172a;
            border-color: #334155;
            border-top-color: #3b82f6;
        }

        .emp-station-dtr-page .fi-ta-header-cell {
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            padding: 0.55rem 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .dark .emp-station-dtr-page .fi-ta-header-cell {
            background: #1e293b;
            border-bottom-color: #334155;
            color: #94a3b8;
        }

        .emp-station-dtr-page .fi-ta-cell {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        .emp-station-dtr-page .fi-ta-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 100ms ease;
        }

        .dark .emp-station-dtr-page .fi-ta-table tbody tr {
            border-bottom-color: #1e293b;
        }

        .emp-station-dtr-page .fi-ta-table tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .dark .emp-station-dtr-page .fi-ta-table tbody tr:hover {
            background-color: #1e293b !important;
        }

        .emp-station-dtr-page .fi-ta-actions .fi-btn {
            padding: 0.25rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }

        @media (max-width: 640px) {
            .emp-station-dtr-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="emp-station-dtr-page employee-blue-page">
        {{-- KPI Strip --}}
        <div class="emp-station-dtr-kpis">
            <div class="emp-station-dtr-kpi primary">
                <div class="emp-station-dtr-icon">
                    <x-filament::icon icon="heroicon-m-building-storefront" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="emp-station-dtr-body">
                    <span class="emp-station-dtr-label">ASSIGNED BRANCHES</span>
                    <div class="emp-station-dtr-val text-blue-700 dark:text-blue-300">{{ $stats['branches_count'] }}</div>
                    <span class="emp-station-dtr-subtext">Stations under your scope</span>
                </div>
            </div>

            <div class="emp-station-dtr-kpi">
                <div class="emp-station-dtr-icon">
                    <x-filament::icon icon="heroicon-m-users" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="emp-station-dtr-body">
                    <span class="emp-station-dtr-label">ACTIVE EMPLOYEES</span>
                    <div class="emp-station-dtr-val text-emerald-700 dark:text-emerald-300">{{ $stats['active_employees_count'] }}</div>
                    <span class="emp-station-dtr-subtext">Managed branch personnel</span>
                </div>
            </div>

            <div class="emp-station-dtr-kpi">
                <div class="emp-station-dtr-icon">
                    <x-filament::icon icon="heroicon-m-calendar" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="emp-station-dtr-body">
                    <span class="emp-station-dtr-label">OPEN PAYROLL PERIODS</span>
                    <div class="emp-station-dtr-val text-indigo-700 dark:text-indigo-300">{{ $stats['open_periods_count'] }}</div>
                    <span class="emp-station-dtr-subtext">Periods eligible for review</span>
                </div>
            </div>
        </div>

        {{-- Table Content --}}
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
