<x-filament-panels::page>
    @php
        $employee = $this->getEmployee();
        $branch = $this->getBranch();
        $period = $this->getSelectedPayrollPeriod();
        $metrics = $this->getSummaryMetrics();
        $returnUrl = $this->getReturnUrl();
        $printUrl = $this->getPrintUrl();
        $exportUrl = $this->getExportUrl();

        $initials = $employee
            ? strtoupper(substr((string) ($employee->firstname ?? 'E'), 0, 1) . substr((string) ($employee->lastname ?? 'M'), 0, 1))
            : 'EM';
    @endphp

    <style>
        /* Suppress default Filament header if present */
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .dtr-manage-workspace {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            width: 100%;
            margin-bottom: 0.5rem;
        }

        /* Topbar Header */
        .dtr-topbar {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem 1rem;
            padding: 0.85rem 1.15rem;
            border-radius: 0.75rem;
            border: 1px solid rgb(226, 232, 240);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .dtr-topbar::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inline-size: 100%;
            block-size: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);
        }

        .dark .dtr-topbar {
            border-color: rgb(51, 65, 85);
            background: linear-gradient(180deg, rgb(30, 41, 59) 0%, rgb(15, 23, 42) 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .dtr-emp-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dtr-emp-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 800;
            flex-shrink: 0;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }

        .dtr-emp-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dtr-emp-details {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .dtr-emp-name-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem 0.6rem;
        }

        .dtr-title {
            color: rgb(15, 23, 42);
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .dark .dtr-title {
            color: #ffffff;
        }

        .dtr-emp-subtitle {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem 0.5rem;
            font-size: 0.765rem;
            color: rgb(100, 116, 139);
            line-height: 1.3;
        }

        .dark .dtr-emp-subtitle {
            color: rgb(148, 163, 184);
        }

        .dtr-meta-dot {
            color: rgb(203, 213, 225);
            font-size: 0.75rem;
        }

        .dark .dtr-meta-dot {
            color: rgb(71, 85, 105);
        }

        .dtr-badge-code {
            display: inline-flex;
            align-items: center;
            padding: 0.1rem 0.35rem;
            border-radius: 0.25rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.7rem;
            font-weight: 600;
            background: transparent;
            color: rgb(100, 116, 139);
            border: 1px solid rgb(226, 232, 240);
            white-space: nowrap;
        }

        .dark .dtr-badge-code {
            background: transparent;
            border-color: rgb(51, 65, 85);
            color: rgb(148, 163, 184);
        }

        .dtr-actions-row {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.4rem;
        }

        .dtr-btn {
            position: relative;
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.7rem;
            border-radius: 0.45rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            color: #ffffff;
            background: #2563eb;
            border: 1px solid transparent;
            box-shadow: 0 1px 2px 0 rgba(37, 99, 235, 0.15);
            transition: all 130ms ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .dtr-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .dtr-btn.outline {
            color: rgb(30, 41, 59);
            background: #ffffff;
            border-color: rgb(203, 213, 225);
            box-shadow: none;
        }

        .dtr-btn.outline:hover {
            background: rgb(248, 250, 252);
            border-color: rgb(148, 163, 184);
            color: rgb(15, 23, 42);
        }

        .dark .dtr-btn.outline {
            color: rgb(226, 232, 240);
            background: rgb(30, 41, 59);
            border-color: rgb(71, 85, 105);
        }

        .dark .dtr-btn.outline:hover {
            background: rgb(51, 65, 85);
            border-color: rgb(100, 116, 139);
        }

        .dtr-btn.secondary {
            background: rgb(15, 23, 42);
        }

        .dtr-btn.secondary:hover {
            background: rgb(30, 41, 59);
        }

        .dark .dtr-btn.secondary {
            background: rgb(51, 65, 85);
            color: #f8fafc;
        }

        .dark .dtr-btn.secondary:hover {
            background: rgb(71, 85, 105);
        }

        .dtr-btn-icon {
            width: 0.85rem;
            height: 0.85rem;
            flex-shrink: 0;
        }

        /* Thematic Stat Summary Cards */
        .dtr-summary-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.65rem;
        }

        .dtr-stat-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.35rem;
            padding: 0.75rem 0.95rem;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
            transition: transform 130ms ease;
        }

        .dtr-stat-card:hover {
            transform: translateY(-1px);
        }

        .dark .dtr-stat-card {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
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
        .dtr-stat-card.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        .dtr-stat-card.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }
        .dtr-stat-card.theme-purple::before {
            background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);
        }

        .dtr-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .dtr-stat-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(100, 116, 139);
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

        .theme-purple .dtr-stat-icon-wrapper {
            background: rgb(245, 243, 255);
            color: #7c3aed;
        }
        .dark .theme-purple .dtr-stat-icon-wrapper {
            background: rgba(124, 58, 237, 0.18);
            color: #a78bfa;
        }

        .dtr-stat-value {
            font-size: 1.1rem;
            font-weight: 800;
            color: rgb(15, 23, 42);
            line-height: 1.15;
            letter-spacing: -0.01em;
        }

        .dark .dtr-stat-value {
            color: #ffffff;
        }

        .dtr-stat-meta {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.7rem;
            color: rgb(100, 116, 139);
            font-weight: 500;
        }

        .dark .dtr-stat-meta {
            color: rgb(148, 163, 184);
        }

        /* Modernized Filament Table Widget Styling */
        .fi-page .fi-wi-table {
            max-width: 100%;
            min-width: 0;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .fi-page .fi-wi-table {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .fi-page .fi-ta-header {
            gap: 0.5rem;
            padding: 0.55rem 0.85rem !important;
            background: rgb(248, 250, 252);
            border-bottom: 1px solid rgb(226, 232, 240);
        }

        .dark .fi-page .fi-ta-header {
            background: rgb(30, 41, 59);
            border-bottom-color: rgb(51, 65, 85);
        }

        .fi-page .fi-ta-heading {
            font-size: 0.775rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(51, 65, 85);
        }

        .dark .fi-page .fi-ta-heading {
            color: rgb(203, 213, 225);
        }

        .fi-page .fi-ta-header-toolbar {
            gap: 0.35rem;
        }

        .fi-page .fi-ta-header-toolbar .fi-btn {
            min-height: 1.95rem;
            padding: 0.25rem 0.55rem;
            font-size: 0.725rem;
            border-radius: 0.4rem;
        }

        .fi-page .fi-ta table {
            font-size: 0.765rem !important;
            line-height: 1.25;
            border-collapse: collapse;
        }

        .fi-page .fi-ta-header-cell {
            padding: 0.4rem 0.55rem !important;
            font-size: 0.675rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(71, 85, 105) !important;
            background: rgb(241, 245, 249) !important;
            border-bottom: 1px solid rgb(203, 213, 225) !important;
            white-space: nowrap;
        }

        .dark .fi-page .fi-ta-header-cell {
            color: rgb(203, 213, 225) !important;
            background: rgb(30, 41, 59) !important;
            border-bottom-color: rgb(71, 85, 105) !important;
        }

        .fi-page .fi-ta-cell {
            padding: 0.32rem 0.5rem !important;
            border-bottom: 1px solid rgb(226, 232, 240);
            background: transparent !important;
            vertical-align: middle;
        }

        .dark .fi-page .fi-ta-cell {
            border-bottom-color: rgb(51, 65, 85);
        }

        .fi-page .fi-ta-text-item {
            font-size: 0.765rem !important;
            line-height: 1.2 !important;
        }

        .fi-page .fi-ta-cell-index {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.7rem;
            font-weight: 700;
            color: rgb(148, 163, 184);
            text-align: center !important;
            width: 2.25rem;
            min-width: 2.25rem;
            max-width: 2.25rem;
        }

        .dark .fi-page .fi-ta-cell-index {
            color: rgb(100, 116, 139);
        }

        .fi-page .fi-ta-cell-index .fi-ta-text-item {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
        }

        .fi-page .fi-ta table tbody tr:nth-child(even) {
            background-color: rgba(248, 250, 252, 0.6) !important;
        }

        .dark .fi-page .fi-ta table tbody tr:nth-child(even) {
            background-color: rgba(30, 41, 59, 0.3) !important;
        }

        .fi-page .fi-ta table tbody tr:hover {
            background-color: rgba(239, 246, 255, 0.75) !important;
        }

        .dark .fi-page .fi-ta table tbody tr:hover {
            background-color: rgba(51, 65, 85, 0.45) !important;
        }

        .fi-page .fi-ta .fi-badge {
            padding: 0.12rem 0.42rem;
            font-size: 0.675rem;
            font-weight: 700;
            border-radius: 9999px;
            white-space: nowrap;
        }

        .fi-page .fi-ta-actions {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.25rem;
            padding: 0.2rem 0.45rem !important;
            white-space: nowrap;
        }

        .fi-page .fi-ta-actions .fi-btn,
        .fi-page .fi-ta-actions .fi-icon-btn {
            min-height: 1.65rem;
            height: 1.65rem;
            padding: 0.15rem 0.4rem;
            font-size: 0.685rem;
            border-radius: 0.35rem;
        }

        .fi-page .fi-pagination {
            padding: 0.45rem 0.75rem !important;
        }

        @media (max-width: 1024px) {
            .dtr-summary-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .dtr-summary-strip {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="dtr-manage-workspace">
        {{-- Topbar Header --}}
        <header class="dtr-topbar">
            <div class="dtr-emp-profile">
                <div class="dtr-emp-avatar">
                    @if ($employee?->user?->profile_photo_url)
                        <img src="{{ $employee->user->profile_photo_url }}" alt="{{ $employee->full_name }}" />
                    @else
                        <span>{{ $initials }}</span>
                    @endif
                </div>

                <div class="dtr-emp-details">
                    <div class="dtr-emp-name-row">
                        <h2 class="dtr-title">{{ $employee?->full_name ?? 'Employee DTR' }}</h2>
                        <span class="dtr-badge-code">{{ $employee?->company_id ?? $employee?->uid ?? 'N/A' }}</span>
                    </div>

                    <div class="dtr-emp-subtitle">
                        @if ($branch)
                            <span>{{ $branch->branch_name }}</span>
                        @endif

                        @if ($employee?->designation)
                            <span class="dtr-meta-dot">&bull;</span>
                            <span>{{ $employee->designation->title }}</span>
                        @endif

                        @if ($employee?->department)
                            <span class="dtr-meta-dot">&bull;</span>
                            <span>{{ $employee->department->name }}</span>
                        @endif

                        @if ($period)
                            <span class="dtr-meta-dot">&bull;</span>
                            <span>{{ $period->title }} ({{ $period->is_locked ? 'Locked' : 'Open' }})</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="dtr-actions-row">
                @if ($printUrl)
                    <a href="{{ $printUrl }}" target="_blank" class="dtr-btn outline" title="Print D.T.R">
                        <x-filament::icon icon="heroicon-m-printer" class="dtr-btn-icon" />
                        <span>Print</span>
                    </a>
                @endif

                @if ($exportUrl)
                    <a href="{{ $exportUrl }}" target="_blank" class="dtr-btn outline" title="Export D.T.R to Excel">
                        <x-filament::icon icon="heroicon-m-arrow-down-tray" class="dtr-btn-icon" />
                        <span>Export</span>
                    </a>
                @endif

                <a href="{{ $returnUrl }}" class="dtr-btn secondary" title="Return to HR Daily Time Record">
                    <x-filament::icon icon="heroicon-m-arrow-left" class="dtr-btn-icon" />
                    <span>Return</span>
                </a>
            </div>
        </header>

        {{-- Thematic Stat Cards --}}
        <section class="dtr-summary-strip" aria-label="Employee DTR Period Summary">
            {{-- Card 1: Total Days & Entries (Blue Theme) --}}
            <div class="dtr-stat-card theme-blue">
                <div class="dtr-stat-header">
                    <span class="dtr-stat-label">Days &amp; Entries</span>
                    <div class="dtr-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div>
                    <div class="dtr-stat-value">{{ number_format($metrics['total_days_work'], 1) }} Days</div>
                    <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                        <x-filament::icon icon="heroicon-m-check-circle" class="w-3 h-3 text-blue-600 dark:text-blue-400" />
                        <span>{{ number_format($metrics['total_entries']) }} total DTR entries</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Credited Work Hours (Emerald Theme) --}}
            <div class="dtr-stat-card theme-emerald">
                <div class="dtr-stat-header">
                    <span class="dtr-stat-label">Credited Work Hours</span>
                    <div class="dtr-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-clock" class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div>
                    <div class="dtr-stat-value">{{ number_format($metrics['credited_work_hrs'] / 60, 1) }} hrs</div>
                    <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                        <x-filament::icon icon="heroicon-m-clock" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                        <span>{{ number_format($metrics['credited_work_hrs']) }} total minutes</span>
                    </div>
                </div>
            </div>

            {{-- Card 3: Credited Overtime (Amber Theme) --}}
            <div class="dtr-stat-card theme-amber">
                <div class="dtr-stat-header">
                    <span class="dtr-stat-label">Credited Overtime</span>
                    <div class="dtr-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-bolt" class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div>
                    <div class="dtr-stat-value">{{ number_format($metrics['credited_overtime'] / 60, 1) }} hrs</div>
                    <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                        @if ($metrics['pending_ot_count'] > 0)
                            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-3 h-3 text-amber-600 dark:text-amber-400" />
                            <span style="color: #d97706; font-weight: 700;">{{ $metrics['pending_ot_count'] }} pending OT</span>
                        @else
                            <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 text-slate-400" />
                            <span>{{ number_format($metrics['credited_overtime']) }} mins credited</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card 4: Late & Undertime (Purple Theme) --}}
            <div class="dtr-stat-card theme-purple">
                <div class="dtr-stat-header">
                    <span class="dtr-stat-label">Late &amp; Undertime</span>
                    <div class="dtr-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-exclamation-circle" class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div>
                    <div class="dtr-stat-value">
                        @if ($metrics['late'] > 0 || $metrics['undertime'] > 0)
                            {{ $metrics['late'] }}m / {{ $metrics['undertime'] }}m
                        @else
                            0 mins
                        @endif
                    </div>
                    <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                        <span>{{ $metrics['late'] }}m Late &bull; {{ $metrics['undertime'] }}m Undertime</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
