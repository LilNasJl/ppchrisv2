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
            gap: 0.75rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        /* ── KPI Stat Strip ─────────────────────────────────────────── */
        .station-overview-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
            border-color: #1e293b;
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

        /* ── Quick Navigation Action Grid ───────────────────────────── */
        .station-quick-nav-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .station-quick-nav-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            padding: 0.75rem 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
            border-color: #1e293b;
        }

        .dark .station-quick-nav-card:hover {
            background: #1e293b;
            border-color: #3b82f6;
        }

        .station-quick-nav-icon {
            padding: 0.55rem;
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

        /* ── Filament Section & Widget Dark Styling ─────────────────── */
        .fi-page:has(.station-overview-page) .fi-wi-chart,
        .fi-page:has(.station-overview-page) .fi-wi-table {
            margin-top: 0.5rem;
        }

        .dark .fi-page:has(.station-overview-page) .fi-section {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-top: 3px solid #3b82f6 !important;
            border-radius: 0.55rem !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-section-header {
            border-bottom: 1px solid #1e293b !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-section-header-heading {
            color: #f8fafc !important;
            font-weight: 750 !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-section-header-description {
            color: #94a3b8 !important;
        }

        /* ── Ranking Table Dark & Compact Layout ────────────────────── */
        .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-filters-header {
            display: none !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-ctn {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-top: 3px solid #3b82f6 !important;
            border-radius: 0.55rem !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-header {
            background: #070b12 !important;
            border-bottom: 1px solid #1e293b !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-header-heading {
            color: #f8fafc !important;
            font-weight: 750 !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-header-description {
            color: #94a3b8 !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-header-cell {
            background: #070b12 !important;
            border-bottom: 1px solid #1e293b !important;
            color: #94a3b8 !important;
            font-size: 0.7rem !important;
            font-weight: 750 !important;
            letter-spacing: 0.03em !important;
            text-transform: uppercase !important;
            padding: 0.55rem 0.65rem !important;
            white-space: nowrap !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-cell {
            padding: 0.45rem 0.65rem !important;
            font-size: 0.78rem !important;
            white-space: nowrap !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-table tbody tr {
            border-bottom: 1px solid #161f2e !important;
            transition: background-color 100ms ease !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-table tbody tr:nth-child(even) {
            background: #070b12 !important;
        }

        .dark .fi-page:has(.station-overview-page) .fi-ta-table tbody tr:hover {
            background: #0f172a !important;
        }

        @media (min-width: 64rem) {
            .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-filters-above-content-ctn .fi-ta-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-filters .fi-sc-form {
                grid-column: 1 / -1 !important;
                min-width: 0 !important;
            }
        }

        /* ── Mobile Layout Optimizations ────────────────────────────── */
        @media (max-width: 640px) {
            .station-overview-page {
                gap: 0.6rem !important;
            }

            /* 2x2 Grid for Top KPI cards on mobile */
            .station-overview-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.45rem !important;
            }

            .station-kpi-card {
                padding: 0.55rem 0.65rem !important;
                gap: 0.45rem !important;
                border-radius: 0.5rem !important;
            }

            .station-kpi-icon {
                padding: 0.35rem !important;
                border-radius: 0.4rem !important;
            }

            .station-kpi-icon svg {
                width: 1rem !important;
                height: 1rem !important;
            }

            .station-kpi-label {
                font-size: 0.6rem !important;
                line-height: 1.15 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .station-kpi-val {
                font-size: 1.15rem !important;
                line-height: 1.15 !important;
            }

            .station-kpi-subtext {
                font-size: 0.6rem !important;
                line-height: 1.1 !important;
                display: -webkit-box !important;
                -webkit-line-clamp: 1 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
            }

            .station-kpi-badge {
                font-size: 0.58rem !important;
                padding: 0.05rem 0.35rem !important;
                margin-top: 0.1rem !important;
            }

            /* Quick Action List on Mobile */
            .station-quick-nav-grid {
                grid-template-columns: 1fr !important;
                gap: 0.35rem !important;
            }

            .station-quick-nav-card {
                padding: 0.5rem 0.75rem !important;
                gap: 0.6rem !important;
                border-radius: 0.5rem !important;
            }

            .station-quick-nav-icon {
                padding: 0.35rem !important;
                border-radius: 0.4rem !important;
            }

            .station-quick-nav-icon svg {
                width: 1rem !important;
                height: 1rem !important;
            }

            .station-quick-nav-title {
                font-size: 0.79rem !important;
                font-weight: 700 !important;
            }

            .station-quick-nav-desc {
                display: none !important;
            }

            /* Chart Header & Sizing on Mobile */
            .fi-page:has(.station-overview-page) .fi-wi-chart .fi-section-header {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.5rem !important;
                padding: 0.75rem 0.85rem !important;
            }

            .fi-page:has(.station-overview-page) .fi-wi-chart .fi-section-header-heading {
                font-size: 0.92rem !important;
                font-weight: 750 !important;
                line-height: 1.25 !important;
                white-space: normal !important;
                width: 100% !important;
            }

            .fi-page:has(.station-overview-page) .fi-wi-chart .fi-section-header-description {
                display: none !important;
            }

            .fi-page:has(.station-overview-page) .fi-wi-chart .fi-section-header-actions,
            .fi-page:has(.station-overview-page) .fi-wi-chart .fi-section-header-actions select {
                width: 100% !important;
            }

            .fi-page:has(.station-overview-page) .fi-wi-chart .fi-section-content {
                padding: 0.45rem 0.65rem 0.65rem !important;
            }

            .fi-page:has(.station-overview-page) .fi-wi-chart canvas {
                max-height: 250px !important;
            }

            /* Table Header & Sizing on Mobile */
            .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-header-description {
                display: none !important;
            }

            .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-header-heading {
                font-size: 0.92rem !important;
                line-height: 1.25 !important;
            }

            .fi-page:has(.station-overview-page) .fi-wi-table .fi-ta-filters-above-content-ctn {
                padding: 0.55rem 0.75rem !important;
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
                    <span class="station-kpi-label">Change Requests</span>
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
                    <span class="station-kpi-label">Leave Queue</span>
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
                @if($stats['pending_cr'] > 0)
                    <span class="station-kpi-badge amber" style="margin-left: auto; margin-right: 0.35rem; font-size: 0.65rem;">
                        {{ $stats['pending_cr'] }} pending
                    </span>
                @endif
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
                @if($stats['pending_leaves'] > 0)
                    <span class="station-kpi-badge amber" style="margin-left: auto; margin-right: 0.35rem; font-size: 0.65rem;">
                        {{ $stats['pending_leaves'] }} pending
                    </span>
                @endif
                <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 station-quick-nav-arrow" />
            </a>
        </div>
    </div>
</x-filament-panels::page>
