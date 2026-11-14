<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getLeaveStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "EMPLOYEE LEAVE MANAGEMENT";
        }

        .emp-leave-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        /* KPI Strip */
        .emp-leave-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .emp-leave-kpi {
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

        .emp-leave-kpi:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .dark .emp-leave-kpi {
            background: #000000;
            border-color: #1e293b;
        }

        .dark .emp-leave-kpi:hover {
            border-color: #3b82f6;
        }

        .emp-leave-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .emp-leave-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(30, 58, 138, 0.08));
            border-color: rgba(59, 130, 246, 0.35);
        }

        .emp-leave-icon {
            padding: 0.45rem;
            border-radius: 0.45rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .emp-leave-icon {
            background: #070b12;
            border-color: #1e293b;
        }

        .emp-leave-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.12rem;
        }

        .emp-leave-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .emp-leave-label {
            color: #94a3b8;
        }

        .emp-leave-val {
            font-size: 1.15rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .emp-leave-val {
            color: #f8fafc;
        }

        .emp-leave-subtext {
            font-size: 0.67rem;
            color: #64748b;
        }

        .dark .emp-leave-subtext {
            color: #94a3b8;
        }

        .emp-leave-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.08rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.64rem;
            font-weight: 750;
            width: fit-content;
            margin-top: 0.1rem;
        }

        .emp-leave-badge.amber {
            background: #fef3c7;
            color: #b45309;
        }

        .dark .emp-leave-badge.amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .emp-leave-badge.green {
            background: #dcfce7;
            color: #15803d;
        }

        .dark .emp-leave-badge.green {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .emp-leave-badge.blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .dark .emp-leave-badge.blue {
            background: rgba(37, 99, 235, 0.2);
            color: #93c5fd;
        }

        /* Modern Tabs Pill Styling */
        .emp-leave-page .fi-tabs {
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

        .dark .emp-leave-page .fi-tabs {
            background: #070b12;
            border-color: #1e293b;
        }

        .emp-leave-page .fi-tabs-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
            gap: 0.45rem;
            padding: 0.35rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            line-height: 1.25;
            border-radius: 0.45rem;
            color: #64748b;
            background: transparent;
            transition: all 120ms ease;
            text-decoration: none;
            white-space: nowrap;
            min-height: 2.15rem;
            height: 2.15rem;
            border: 1px solid transparent;
            box-sizing: border-box;
        }

        .emp-leave-page .fi-tabs-item svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
            display: inline-block;
            vertical-align: middle;
        }

        .dark .emp-leave-page .fi-tabs-item {
            color: #94a3b8;
        }

        .emp-leave-page .fi-tabs-item:hover {
            color: #0f172a;
            background: #f1f5f9;
        }

        .dark .emp-leave-page .fi-tabs-item:hover {
            color: #f8fafc;
            background: #1e293b;
        }

        .emp-leave-page .fi-tabs-item[aria-selected="true"],
        .emp-leave-page .fi-tabs-item.fi-active {
            color: #ffffff !important;
            background: #2563eb !important;
            border-color: #2563eb !important;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.28);
        }

        .emp-leave-page .fi-tabs-item[aria-selected="true"] :is(svg, span),
        .emp-leave-page .fi-tabs-item.fi-active :is(svg, span) {
            color: #ffffff !important;
        }

        .emp-leave-page .fi-tabs-item .fi-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
            border-radius: 9999px;
            transition: all 120ms ease;
        }

        .emp-leave-page .fi-tabs-item[aria-selected="true"] .fi-badge,
        .emp-leave-page .fi-tabs-item.fi-active .fi-badge {
            background: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
        }

        /* Compact Modern Table Enhancements */
        .emp-leave-page .fi-ta-ctn {
            border-radius: 0.55rem;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #2563eb;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .emp-leave-page .fi-ta-ctn {
            background: #000000;
            border-color: #1e293b;
            border-top-color: #3b82f6;
        }

        .emp-leave-page .fi-ta-header-cell {
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            padding: 0.55rem 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .dark .emp-leave-page .fi-ta-header-cell {
            background: #070b12;
            border-bottom-color: #1e293b;
            color: #94a3b8;
        }

        .emp-leave-page .fi-ta-cell {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        .emp-leave-page .fi-ta-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 100ms ease;
        }

        .dark .emp-leave-page .fi-ta-table tbody tr {
            border-bottom-color: #161f2e;
        }

        .emp-leave-page .fi-ta-table tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .dark .emp-leave-page .fi-ta-table tbody tr:hover {
            background-color: #0c1424 !important;
        }

        /* Direct Action Buttons Styling */
        .emp-leave-page .fi-ta-actions {
            gap: 0.35rem;
        }

        .emp-leave-page .fi-ta-actions .fi-btn {
            padding: 0.25rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }

        @media (max-width: 640px) {
            .emp-leave-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.5rem !important;
            }

            .emp-leave-kpi {
                padding: 0.55rem 0.65rem !important;
                gap: 0.45rem !important;
            }

            .emp-leave-icon {
                padding: 0.35rem !important;
            }

            .emp-leave-icon svg {
                width: 1.15rem !important;
                height: 1.15rem !important;
            }

            .emp-leave-label {
                font-size: 0.58rem !important;
                line-height: 1.15 !important;
            }

            .emp-leave-val {
                font-size: 1.05rem !important;
                line-height: 1.15 !important;
            }

            .emp-leave-subtext,
            .emp-leave-badge {
                font-size: 0.6rem !important;
            }
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

        @media (max-width: 640px) {
            /* ── Modal Full Width & Transparency Fix for Mobile View ─── */
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
                gap: 0.5rem !important;
            }

            .fi-modal-footer-actions .fi-btn {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                justify-content: center !important;
            }
        }

    </style>

    <div class="emp-leave-page employee-blue-page">
        {{-- Content: Tabs + Compact Table --}}
        <div>
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
