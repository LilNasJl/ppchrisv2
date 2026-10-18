<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getStationOverviewStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "STATION MANAGEMENT";
        }

        .station-overview-page {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        /* KPI Stat Strip */
        .station-overview-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .station-kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            padding: 0.75rem 0.95rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            transition: all 120ms ease;
            text-decoration: none;
            color: inherit;
        }

        .station-kpi-card:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
            transform: translateY(-1px);
        }

        .dark .station-kpi-card {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .station-kpi-card:hover {
            border-color: #3b82f6;
        }

        .station-kpi-card.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .station-kpi-card.primary {
            background: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .station-kpi-icon {
            padding: 0.5rem;
            border-radius: 0.5rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .station-kpi-icon {
            background: #1e293b;
            border-color: #334155;
        }

        .station-kpi-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.15rem;
            flex: 1;
        }

        .station-kpi-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .station-kpi-label {
            color: #94a3b8;
        }

        .station-kpi-val {
            font-size: 1.25rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .station-kpi-val {
            color: #f8fafc;
        }

        .station-kpi-subtext {
            font-size: 0.68rem;
            color: #64748b;
        }

        .dark .station-kpi-subtext {
            color: #94a3b8;
        }

        .station-kpi-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.08rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.64rem;
            font-weight: 750;
            width: fit-content;
            margin-top: 0.15rem;
        }

        .station-kpi-badge.amber {
            background: #fef3c7;
            color: #b45309;
        }

        .dark .station-kpi-badge.amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .station-kpi-badge.green {
            background: #dcfce7;
            color: #15803d;
        }

        .dark .station-kpi-badge.green {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .station-kpi-badge.blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .dark .station-kpi-badge.blue {
            background: rgba(37, 99, 235, 0.2);
            color: #93c5fd;
        }

        /* Quick Navigation Action Grid */
        .station-quick-nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .station-quick-nav-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            transition: all 120ms ease;
        }

        .station-quick-nav-card:hover {
            border-color: #2563eb;
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .dark .station-quick-nav-card {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .station-quick-nav-card:hover {
            background: #1e293b;
            border-color: #3b82f6;
        }

        .station-quick-nav-icon {
            padding: 0.6rem;
            border-radius: 0.5rem;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .station-quick-nav-icon {
            background: rgba(37, 99, 235, 0.2);
            color: #60a5fa;
        }

        .station-quick-nav-content {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.1rem;
            flex: 1;
        }

        .station-quick-nav-title {
            font-size: 0.82rem;
            font-weight: 750;
            color: #0f172a;
        }

        .dark .station-quick-nav-title {
            color: #f8fafc;
        }

        .station-quick-nav-desc {
            font-size: 0.68rem;
            color: #64748b;
        }

        .dark .station-quick-nav-desc {
            color: #94a3b8;
        }

        .station-quick-nav-arrow {
            color: #94a3b8;
            flex-shrink: 0;
            transition: transform 120ms ease;
        }

        .station-quick-nav-card:hover .station-quick-nav-arrow {
            color: #2563eb;
            transform: translateX(2px);
        }

        .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-filters-header {
            display: none;
        }

        @media (min-width: 64rem) {
            .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-filters-above-content-ctn .fi-ta-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-filters .fi-sc-form {
                grid-column: 1 / -1;
                min-width: 0;
            }
        }

        @media (max-width: 640px) {
            .station-overview-kpis,
            .station-quick-nav-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="station-overview-page employee-blue-page">
        {{-- KPI Stat Strip --}}
        <div class="station-overview-kpis" aria-label="Station management overview">
            <a href="{{ \App\Filament\Employee\Pages\Station\ManageStationDtr::getUrl() }}" class="station-kpi-card primary">
                <div class="station-kpi-icon">
                    <x-filament::icon icon="heroicon-m-building-storefront" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="station-kpi-body">
                    <span class="station-kpi-label">Assigned Stations</span>
                    <div class="station-kpi-val text-blue-700 dark:text-blue-300">{{ number_format($stats['branches']) }}</div>
                    <span class="station-kpi-subtext">Managed branch locations</span>
                </div>
            </a>

            <div class="station-kpi-card">
                <div class="station-kpi-icon">
                    <x-filament::icon icon="heroicon-m-users" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="station-kpi-body">
                    <span class="station-kpi-label">Station Staff</span>
                    <div class="station-kpi-val text-emerald-700 dark:text-emerald-300">{{ number_format($stats['employees']) }}</div>
                    <span class="station-kpi-subtext">Active personnel under scope</span>
                </div>
            </div>

            <a href="{{ \App\Filament\Employee\Pages\Station\ReviewDtrChangeRequests::getUrl() }}" class="station-kpi-card">
                <div class="station-kpi-icon">
                    <x-filament::icon icon="heroicon-m-document-text" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="station-kpi-body">
                    <span class="station-kpi-label">Pending Change Requests</span>
                    <div class="station-kpi-val {{ $stats['pending_cr'] > 0 ? 'text-amber-700 dark:text-amber-300' : '' }}">
                        {{ number_format($stats['pending_cr']) }}
                    </div>
                    <span class="station-kpi-badge {{ $stats['pending_cr'] > 0 ? 'amber' : 'green' }}">
                        {{ $stats['pending_cr'] > 0 ? 'Needs review' : 'Inbox clear' }}
                    </span>
                </div>
            </a>

            <a href="{{ \App\Filament\Employee\Pages\LeaveApprovals::getUrl() }}" class="station-kpi-card">
                <div class="station-kpi-icon">
                    <x-filament::icon icon="heroicon-m-clipboard-document-check" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div class="station-kpi-body">
                    <span class="station-kpi-label">Pending Leave Queue</span>
                    <div class="station-kpi-val {{ $stats['pending_leaves'] > 0 ? 'text-purple-700 dark:text-purple-300' : '' }}">
                        {{ number_format($stats['pending_leaves']) }}
                    </div>
                    <span class="station-kpi-badge {{ $stats['pending_leaves'] > 0 ? 'amber' : 'blue' }}">
                        {{ $stats['pending_leaves'] > 0 ? 'Action required' : 'No pending leaves' }}
                    </span>
                </div>
            </a>
        </div>

        {{-- Quick Station Hub Navigation --}}
        <div class="station-quick-nav-grid" aria-label="Station Management quick links">
            <a href="{{ \App\Filament\Employee\Pages\Station\ManageStationDtr::getUrl() }}" class="station-quick-nav-card">
                <div class="station-quick-nav-icon">
                    <x-filament::icon icon="heroicon-m-clock" class="w-5 h-5" />
                </div>
                <div class="station-quick-nav-content">
                    <span class="station-quick-nav-title">Manage Station D.T.R</span>
                    <span class="station-quick-nav-desc">View employee punch logs, attendance, and biometrics</span>
                </div>
                <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 station-quick-nav-arrow" />
            </a>

            <a href="{{ \App\Filament\Employee\Pages\Station\ReviewDtrChangeRequests::getUrl() }}" class="station-quick-nav-card">
                <div class="station-quick-nav-icon">
                    <x-filament::icon icon="heroicon-m-arrow-path" class="w-5 h-5" />
                </div>
                <div class="station-quick-nav-content">
                    <span class="station-quick-nav-title">Station Change Requests</span>
                    <span class="station-quick-nav-desc">Review and decide pending time adjustments</span>
                </div>
                <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 station-quick-nav-arrow" />
            </a>

            <a href="{{ \App\Filament\Employee\Pages\LeaveApprovals::getUrl() }}" class="station-quick-nav-card">
                <div class="station-quick-nav-icon">
                    <x-filament::icon icon="heroicon-m-calendar-days" class="w-5 h-5" />
                </div>
                <div class="station-quick-nav-content">
                    <span class="station-quick-nav-title">Station Leave Approvals</span>
                    <span class="station-quick-nav-desc">Approve or reject leave applications</span>
                </div>
                <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 station-quick-nav-arrow" />
            </a>
        </div>
    </div>
</x-filament-panels::page>
