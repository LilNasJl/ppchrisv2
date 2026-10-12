<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getLeaveApprovalStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "STATION LEAVE APPROVALS";
        }

        .emp-leave-approvals-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        /* KPI Strip */
        .emp-leave-appr-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .emp-leave-appr-kpi {
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

        .emp-leave-appr-kpi:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .dark .emp-leave-appr-kpi {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .emp-leave-appr-kpi:hover {
            border-color: #3b82f6;
        }

        .emp-leave-appr-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .emp-leave-appr-kpi.primary {
            background: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .emp-leave-appr-icon {
            padding: 0.45rem;
            border-radius: 0.45rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .emp-leave-appr-icon {
            background: #1e293b;
            border-color: #334155;
        }

        .emp-leave-appr-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.12rem;
        }

        .emp-leave-appr-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .emp-leave-appr-label {
            color: #94a3b8;
        }

        .emp-leave-appr-val {
            font-size: 1.15rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .emp-leave-appr-val {
            color: #f8fafc;
        }

        .emp-leave-appr-subtext {
            font-size: 0.67rem;
            color: #64748b;
        }

        .dark .emp-leave-appr-subtext {
            color: #94a3b8;
        }

        .emp-leave-appr-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.08rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.64rem;
            font-weight: 750;
            width: fit-content;
            margin-top: 0.1rem;
        }

        .emp-leave-appr-badge.amber {
            background: #fef3c7;
            color: #b45309;
        }

        .dark .emp-leave-appr-badge.amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .emp-leave-appr-badge.green {
            background: #dcfce7;
            color: #15803d;
        }

        .dark .emp-leave-appr-badge.green {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        /* Modern Tabs Pill Styling */
        .emp-leave-approvals-page .fi-tabs {
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

        .dark .emp-leave-approvals-page .fi-tabs {
            background: #0f172a;
            border-color: #334155;
        }

        .emp-leave-approvals-page .fi-tabs-item {
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
        }

        .dark .emp-leave-approvals-page .fi-tabs-item {
            color: #94a3b8;
        }

        .emp-leave-approvals-page .fi-tabs-item:hover {
            color: #0f172a;
            background: #f1f5f9;
        }

        .dark .emp-leave-approvals-page .fi-tabs-item:hover {
            color: #f8fafc;
            background: #1e293b;
        }

        .emp-leave-approvals-page .fi-tabs-item[aria-selected="true"],
        .emp-leave-approvals-page .fi-tabs-item.fi-active {
            color: #ffffff !important;
            background: #2563eb !important;
            border-color: #2563eb !important;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.28);
        }

        .emp-leave-approvals-page .fi-tabs-item[aria-selected="true"] :is(svg, span),
        .emp-leave-approvals-page .fi-tabs-item.fi-active :is(svg, span) {
            color: #ffffff !important;
        }

        .emp-leave-approvals-page .fi-tabs-item .fi-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
            border-radius: 9999px;
            transition: all 120ms ease;
        }

        .emp-leave-approvals-page .fi-tabs-item[aria-selected="true"] .fi-badge,
        .emp-leave-approvals-page .fi-tabs-item.fi-active .fi-badge {
            background: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
        }

        /* Compact Modern Table */
        .emp-leave-approvals-page .fi-ta-ctn {
            border-radius: 0.55rem;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #2563eb;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .emp-leave-approvals-page .fi-ta-ctn {
            background: #0f172a;
            border-color: #334155;
            border-top-color: #3b82f6;
        }

        .emp-leave-approvals-page .fi-ta-header-cell {
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            padding: 0.55rem 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .dark .emp-leave-approvals-page .fi-ta-header-cell {
            background: #1e293b;
            border-bottom-color: #334155;
            color: #94a3b8;
        }

        .emp-leave-approvals-page .fi-ta-cell {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        .emp-leave-approvals-page .fi-ta-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 100ms ease;
        }

        .dark .emp-leave-approvals-page .fi-ta-table tbody tr {
            border-bottom-color: #1e293b;
        }

        .emp-leave-approvals-page .fi-ta-table tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .dark .emp-leave-approvals-page .fi-ta-table tbody tr:hover {
            background-color: #1e293b !important;
        }

        .emp-leave-approvals-page .fi-ta-actions .fi-btn {
            padding: 0.25rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }

        @media (max-width: 640px) {
            .emp-leave-appr-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="emp-leave-approvals-page employee-blue-page">
        {{-- KPI Strip --}}
        <div class="emp-leave-appr-kpis">
            <div class="emp-leave-appr-kpi primary">
                <div class="emp-leave-appr-icon">
                    <x-filament::icon icon="heroicon-m-clipboard-document-check" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="emp-leave-appr-body">
                    <span class="emp-leave-appr-label">TOTAL LEAVE QUEUE</span>
                    <div class="emp-leave-appr-val text-blue-700 dark:text-blue-300">{{ $stats['total'] }}</div>
                    <span class="emp-leave-appr-subtext">All submitted station leaves</span>
                </div>
            </div>

            <div class="emp-leave-appr-kpi">
                <div class="emp-leave-appr-icon">
                    <x-filament::icon icon="heroicon-m-clock" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="emp-leave-appr-body">
                    <span class="emp-leave-appr-label">PENDING YOUR DECISION</span>
                    <div class="emp-leave-appr-val {{ $stats['pending'] > 0 ? 'text-amber-700 dark:text-amber-300' : '' }}">
                        {{ $stats['pending'] }}
                    </div>
                    <span class="emp-leave-appr-badge {{ $stats['pending'] > 0 ? 'amber' : 'green' }}">
                        {{ $stats['pending'] > 0 ? 'Action required' : 'Queue cleared' }}
                    </span>
                </div>
            </div>

            <div class="emp-leave-appr-kpi">
                <div class="emp-leave-appr-icon">
                    <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="emp-leave-appr-body">
                    <span class="emp-leave-appr-label">APPROVED LEAVES</span>
                    <div class="emp-leave-appr-val text-emerald-700 dark:text-emerald-300">{{ $stats['approved'] }}</div>
                    <span class="emp-leave-appr-badge green">Approved by you</span>
                </div>
            </div>

            <div class="emp-leave-appr-kpi">
                <div class="emp-leave-appr-icon">
                    <x-filament::icon icon="heroicon-m-x-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                </div>
                <div class="emp-leave-appr-body">
                    <span class="emp-leave-appr-label">REJECTED LEAVES</span>
                    <div class="emp-leave-appr-val text-rose-700 dark:text-rose-300">{{ $stats['rejected'] }}</div>
                    <span class="emp-leave-appr-subtext">Declined applications</span>
                </div>
            </div>
        </div>

        {{-- Content: Tabs + Table --}}
        <div>
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
