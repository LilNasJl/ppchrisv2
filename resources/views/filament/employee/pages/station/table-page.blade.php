<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "STATION MANAGEMENT";
        }

        .station-management-page {
            min-width: 0;
        }

        .station-management-page .fi-ta-ctn {
            overflow-x: auto;
            overscroll-behavior-inline: contain;
        }

        .station-management-page .fi-ta table {
            min-width: 720px;
        }

        .station-management-page .fi-tabs {
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

        .dark .station-management-page .fi-tabs {
            background: #0f172a;
            border-color: #334155;
        }

        .station-management-page .fi-tabs-item {
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

        .dark .station-management-page .fi-tabs-item {
            color: #94a3b8;
        }

        .station-management-page .fi-tabs-item:hover {
            color: #0f172a;
            background: #f1f5f9;
        }

        .dark .station-management-page .fi-tabs-item:hover {
            color: #f8fafc;
            background: #1e293b;
        }

        .station-management-page .fi-tabs-item[aria-selected="true"],
        .station-management-page .fi-tabs-item.fi-active {
            color: #ffffff !important;
            background: #2563eb !important;
            border-color: #2563eb !important;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.28);
        }

        .station-management-page .fi-tabs-item[aria-selected="true"] :is(svg, span),
        .station-management-page .fi-tabs-item.fi-active :is(svg, span) {
            color: #ffffff !important;
        }

        .station-management-page .fi-tabs-item .fi-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
            border-radius: 9999px;
            transition: all 120ms ease;
        }

        .station-management-page .fi-tabs-item[aria-selected="true"] .fi-badge,
        .station-management-page .fi-tabs-item.fi-active .fi-badge {
            background: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
        }

        .station-management-page .fi-ta-ctn {
            border-radius: 0.55rem;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #2563eb;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .station-management-page .fi-ta-ctn {
            background: #0f172a;
            border-color: #334155;
            border-top-color: #3b82f6;
        }

        .station-management-page .fi-ta-header-cell {
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            padding: 0.55rem 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .dark .station-management-page .fi-ta-header-cell {
            background: #1e293b;
            border-bottom-color: #334155;
            color: #94a3b8;
        }

        .station-management-page .fi-ta-cell {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        .station-management-page .fi-ta-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 100ms ease;
        }

        .dark .station-management-page .fi-ta-table tbody tr {
            border-bottom-color: #1e293b;
        }

        .station-management-page .fi-ta-table tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .dark .station-management-page .fi-ta-table tbody tr:hover {
            background-color: #1e293b !important;
        }

        .station-management-page .fi-ta-actions .fi-btn {
            padding: 0.25rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }

        @media (max-width: 640px) {
            .station-management-page .fi-ta-header-toolbar {
                align-items: stretch;
                flex-wrap: wrap;
            }

            .station-management-page .fi-ta-header-toolbar .fi-input-wrp {
                min-width: 0;
                width: 100%;
            }

            .station-management-page .fi-tabs {
                max-width: 100%;
            }
        }
    </style>

    <div class="employee-blue-page station-management-page">
        {{ $this->content }}
    </div>
</x-filament-panels::page>
