<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getChangeRequestStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "STATION CHANGE REQUESTS";
        }

        .emp-change-requests-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        /* KPI Strip */
        .emp-cr-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .emp-cr-kpi {
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

        .emp-cr-kpi:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .dark .emp-cr-kpi {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .emp-cr-kpi:hover {
            border-color: #3b82f6;
        }

        .emp-cr-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .emp-cr-kpi.primary {
            background: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .emp-cr-icon {
            padding: 0.45rem;
            border-radius: 0.45rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .emp-cr-icon {
            background: #1e293b;
            border-color: #334155;
        }

        .emp-cr-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.12rem;
        }

        .emp-cr-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .emp-cr-label {
            color: #94a3b8;
        }

        .emp-cr-val {
            font-size: 1.15rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .emp-cr-val {
            color: #f8fafc;
        }

        .emp-cr-subtext {
            font-size: 0.67rem;
            color: #64748b;
        }

        .dark .emp-cr-subtext {
            color: #94a3b8;
        }

        .emp-cr-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.08rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.64rem;
            font-weight: 750;
            width: fit-content;
            margin-top: 0.1rem;
        }

        .emp-cr-badge.amber {
            background: #fef3c7;
            color: #b45309;
        }

        .dark .emp-cr-badge.amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .emp-cr-badge.green {
            background: #dcfce7;
            color: #15803d;
        }

        .dark .emp-cr-badge.green {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        /* Table Header Cleanup */
        .emp-change-requests-page .fi-ta-header-heading,
        .emp-change-requests-page .fi-ta-header-description,
        .emp-change-requests-page .fi-ta-description {
            display: none !important;
        }

        /* Compact Modern Table */
        .emp-change-requests-page .fi-ta-ctn {
            border-radius: 0.55rem;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #2563eb;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: visible !important;
        }

        .dark .emp-change-requests-page .fi-ta-ctn {
            background: #000000 !important;
            border-color: #1e293b !important;
            border-top-color: #3b82f6 !important;
        }

        .emp-change-requests-page .fi-ta-content {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .emp-change-requests-page .fi-ta-header-cell {
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            padding: 0.55rem 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            white-space: nowrap;
        }

        .dark .emp-change-requests-page .fi-ta-header-cell {
            background: #070b12 !important;
            border-bottom-color: #1e293b !important;
            color: #94a3b8 !important;
        }

        .emp-change-requests-page .fi-ta-cell {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .emp-change-requests-page .fi-ta-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 100ms ease;
        }

        .dark .emp-change-requests-page .fi-ta-table tbody tr {
            border-bottom-color: #161f2e !important;
        }

        .dark .emp-change-requests-page .fi-ta-table tbody tr:nth-child(even) {
            background-color: #070b12 !important;
        }

        .emp-change-requests-page .fi-ta-table tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .dark .emp-change-requests-page .fi-ta-table tbody tr:hover {
            background-color: #0f172a !important;
        }

        .emp-change-requests-page .fi-ta-actions .fi-btn {
            padding: 0.25rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }

        /* ── Modal Window & Transparency Fix ────────────────────────── */
        .fi-modal-window,
        .dark .fi-modal-window {
            background-color: #0f172a !important;
        }

        .fi-modal-header,
        .fi-modal-content,
        .fi-modal-footer {
            background-color: #0f172a !important;
        }

        /* Uniform, balanced modal footer buttons across desktop and mobile */
        .fi-modal-footer {
            padding: 0.75rem 1rem !important;
        }

        .fi-modal-footer-actions {
            display: flex !important;
            align-items: stretch !important;
            gap: 0.5rem !important;
        }

        .fi-modal-footer-actions .fi-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 2.5rem !important;
            height: auto !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            line-height: 1.25 !important;
            text-align: center !important;
            box-sizing: border-box !important;
            white-space: normal !important;
            border-radius: 0.5rem !important;
        }

        .fi-modal-footer-actions .fi-btn svg {
            flex-shrink: 0 !important;
            width: 1.1rem !important;
            height: 1.1rem !important;
        }

        /* 2x2 Grid for Mobile View */
        @media (max-width: 640px) {
            .emp-change-requests-page {
                gap: 0.6rem !important;
            }

            .emp-cr-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.45rem !important;
            }

            .emp-cr-kpi {
                padding: 0.55rem 0.65rem !important;
                gap: 0.45rem !important;
                border-radius: 0.5rem !important;
            }

            .emp-cr-icon {
                padding: 0.35rem !important;
                border-radius: 0.4rem !important;
            }

            .emp-cr-icon svg {
                width: 1rem !important;
                height: 1rem !important;
            }

            .emp-cr-body {
                gap: 0.1rem !important;
            }

            .emp-cr-label {
                font-size: 0.58rem !important;
                line-height: 1.15 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .emp-cr-val {
                font-size: 1.15rem !important;
                line-height: 1.15 !important;
            }

            .emp-cr-subtext {
                font-size: 0.6rem !important;
                line-height: 1.1 !important;
                display: -webkit-box !important;
                -webkit-line-clamp: 1 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
            }

            .emp-cr-badge {
                font-size: 0.58rem !important;
                padding: 0.05rem 0.35rem !important;
                margin-top: 0.05rem !important;
            }

            /* ── Filter Popover Alignment Fix on Mobile ── */
            .emp-change-requests-page .fi-ta-filters-dropdown,
            .emp-change-requests-page .fi-dropdown-panel {
                max-width: calc(100vw - 1.5rem) !important;
                width: calc(100vw - 1.5rem) !important;
                right: 0.75rem !important;
                left: auto !important;
                box-sizing: border-box !important;
            }

            /* ── Modal Full Width Bottom Sheet for Mobile View ─── */
            .fi-modal-window-ctn {
                padding: 0 !important;
                display: flex !important;
                align-items: flex-end !important;
                justify-content: center !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .fi-modal-window {
                width: 100vw !important;
                max-width: 100vw !important;
                min-width: 100vw !important;
                margin: 0 !important;
                border-radius: 1rem 1rem 0 0 !important;
                border: 1px solid rgb(51, 65, 85) !important;
                border-bottom: 0 !important;
                background-color: #0f172a !important;
                box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.75) !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                max-height: 92dvh !important;
                overflow: hidden !important;
            }

            .fi-modal-header {
                flex-shrink: 0 !important;
                background-color: #0f172a !important;
                padding: 0.85rem 1rem 0.5rem !important;
                border-bottom: 1px solid rgb(51, 65, 85) !important;
            }

            .fi-modal-content {
                flex: 1 1 auto !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                background-color: #0f172a !important;
                padding: 0.65rem 0.85rem 0.75rem !important;
                gap: 0.75rem !important;
            }

            .fi-modal-footer {
                flex-shrink: 0 !important;
                background-color: #0f172a !important;
                padding: 0.65rem 0.85rem !important;
                border-top: 1px solid rgb(51, 65, 85) !important;
            }

            .fi-modal-footer-actions {
                width: 100% !important;
                display: flex !important;
                align-items: stretch !important;
                gap: 0.5rem !important;
            }

            .fi-modal-footer-actions .fi-btn {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                min-height: 2.65rem !important;
                padding: 0.45rem 0.5rem !important;
                font-size: 0.775rem !important;
                justify-content: center !important;
                text-align: center !important;
                gap: 0.35rem !important;
            }

            .fi-modal-footer-actions .fi-btn span {
                white-space: normal !important;
                line-height: 1.15 !important;
                text-align: center !important;
            }
        }
    </style>

    <div class="emp-change-requests-page employee-blue-page">
        {{-- KPI Strip (2x2 on mobile) --}}
        <div class="emp-cr-kpis">
            <div class="emp-cr-kpi primary">
                <div class="emp-cr-icon">
                    <x-filament::icon icon="heroicon-m-clock" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="emp-cr-body">
                    <span class="emp-cr-label">PENDING APPROVAL</span>
                    <div class="emp-cr-val text-blue-700 dark:text-blue-300">{{ $stats['pending'] }}</div>
                    <span class="emp-cr-badge {{ $stats['pending'] > 0 ? 'amber' : 'green' }}">
                        {{ $stats['pending'] > 0 ? 'Action required' : 'Inbox clear' }}
                    </span>
                </div>
            </div>

            <div class="emp-cr-kpi">
                <div class="emp-cr-icon">
                    <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="emp-cr-body">
                    <span class="emp-cr-label">DECIDED TODAY</span>
                    <div class="emp-cr-val text-emerald-700 dark:text-emerald-300">{{ $stats['resolved_today'] }}</div>
                    <span class="emp-cr-subtext">Requests processed today</span>
                </div>
            </div>

            <div class="emp-cr-kpi">
                <div class="emp-cr-icon">
                    <x-filament::icon icon="heroicon-m-check-badge" class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                </div>
                <div class="emp-cr-body">
                    <span class="emp-cr-label">TOTAL APPROVED</span>
                    <div class="emp-cr-val text-teal-700 dark:text-teal-300">{{ $stats['approved_count'] ?? 0 }}</div>
                    <span class="emp-cr-subtext">Approved in history</span>
                </div>
            </div>

            <div class="emp-cr-kpi">
                <div class="emp-cr-icon">
                    <x-filament::icon icon="heroicon-m-archive-box" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="emp-cr-body">
                    <span class="emp-cr-label">TOTAL RESOLVED</span>
                    <div class="emp-cr-val text-indigo-700 dark:text-indigo-300">{{ $stats['history_count'] }}</div>
                    <span class="emp-cr-subtext">Decided attendance changes</span>
                </div>
            </div>
        </div>

        {{-- Table Content --}}
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
