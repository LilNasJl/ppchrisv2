<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')
    <style>
        /* ==========================================================================
           Inactive Employees Page: Refined Dark Theme & Border Softening
           ========================================================================== */

        .master-data-page {
            width: 100%;
            min-width: 0;
        }

        /* --- Workforce Records Header Banner --- */
        .master-data-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            width: 100%;
            padding: 0.95rem 1.35rem;
            border: 1px solid rgba(147, 197, 253, 0.28) !important;
            border-radius: 12px !important;
            background: linear-gradient(110deg, #1257b8 0%, #173c70 65%, #102a50 100%) !important;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.45), 0 0 25px -5px rgba(18, 87, 184, 0.2) !important;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .master-data-header-copy {
            min-width: 0;
        }

        .master-data-eyebrow {
            color: #93c5fd !important;
            font-size: 0.65rem !important;
            font-weight: 750 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
        }

        .master-data-title {
            margin: 0.15rem 0 0;
            font-size: 1.45rem;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .master-data-subtitle {
            margin: 0.25rem 0 0;
            font-size: 0.8rem;
            line-height: 1.35;
            color: #dbeafe;
        }

        .master-data-header-controls {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .master-data-header-controls .fi-btn {
            min-height: 2.25rem !important;
            gap: 0.45rem !important;
            padding: 0.42rem 0.85rem !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.14) !important;
            border: 1px solid rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
            backdrop-filter: blur(4px) !important;
            transition: all 0.2s ease !important;
        }

        .master-data-header-controls .fi-btn:hover {
            background: rgba(255, 255, 255, 0.22) !important;
            border-color: rgba(255, 255, 255, 0.45) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
        }

        /* --- Lessen Table Container Outline & Eliminate Double Borders --- */
        .master-data-page .fi-ta,
        .dark .master-data-page .fi-ta {
            max-width: 100% !important;
            min-width: 0 !important;
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            outline: none !important;
            padding: 0 !important;
        }

        .master-data-page .fi-ta-ctn,
        .dark .master-data-page .fi-ta-ctn {
            max-width: 100% !important;
            min-width: 0 !important;
            background: #000000 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 24px -2px rgba(0, 0, 0, 0.5) !important;
            overflow: hidden !important;
            overflow-x: auto !important;
            --tw-ring-color: transparent !important;
            --tw-ring-offset-width: 0px !important;
            --tw-ring-shadow: none !important;
            --tw-ring-offset-shadow: none !important;
            outline: none !important;
        }

        /* --- Table Header Toolbar (Search & Filter) --- */
        .master-data-page .fi-ta-header,
        .master-data-page .fi-ta-header-toolbar,
        .dark .master-data-page .fi-ta-header,
        .dark .master-data-page .fi-ta-header-toolbar {
            background: #080c14 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
            padding: 0.75rem 1.15rem !important;
            gap: 0.75rem !important;
        }

        .master-data-page .fi-ta-header-toolbar > div:last-child {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 0.75rem;
            min-width: 0;
            margin-left: auto;
        }

        .master-data-page .fi-ta-search-field {
            width: clamp(14rem, 24vw, 22rem);
            max-width: 100%;
        }

        .master-data-page .fi-ta-search-field .fi-input-wrp,
        .dark .master-data-page .fi-ta-search-field .fi-input-wrp {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-radius: 8px !important;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.3) !important;
            transition: all 0.15s ease !important;
        }

        .master-data-page .fi-ta-search-field .fi-input-wrp:focus-within,
        .dark .master-data-page .fi-ta-search-field .fi-input-wrp:focus-within {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
        }

        .master-data-page .fi-ta-search-field input {
            background: transparent !important;
            color: #f1f5f9 !important;
            font-size: 0.82rem !important;
        }

        .master-data-page .fi-ta-search-field input::placeholder {
            color: #64748b !important;
        }

        .master-data-page .fi-ta-search-field .fi-icon-btn,
        .master-data-page .fi-ta-search-field svg {
            color: #60a5fa !important;
        }

        .master-data-page .fi-ta-filters-trigger-action-ctn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.5rem;
        }

        .master-data-page .fi-ta-filters-trigger-action,
        .dark .master-data-page .fi-ta-filters-trigger-action {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-radius: 8px !important;
            color: #94a3b8 !important;
            padding: 0.42rem 0.65rem !important;
            transition: all 0.15s ease !important;
        }

        .master-data-page .fi-ta-filters-trigger-action:hover,
        .dark .master-data-page .fi-ta-filters-trigger-action:hover {
            color: #f1f5f9 !important;
            border-color: #334155 !important;
            background: #0c1017 !important;
        }

        .master-data-page .fi-ta-filters-trigger-action .fi-badge {
            background: #2563eb !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }

        /* --- Table Layout & Cell Rules --- */
        .master-data-page .fi-ta table {
            width: 100%;
            min-width: 1040px;
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }

        /* --- Column Headers --- */
        .master-data-page .fi-ta-table thead,
        .master-data-page .fi-ta table thead,
        .dark .master-data-page .fi-ta-table thead,
        .dark .master-data-page .fi-ta table thead {
            background: #080c14 !important;
        }

        .master-data-page .fi-ta-header-cell,
        .dark .master-data-page .fi-ta-header-cell {
            background: #080c14 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 0.7rem 1rem !important;
            white-space: nowrap !important;
        }

        .master-data-page .fi-ta-header-cell-label,
        .dark .master-data-page .fi-ta-header-cell-label {
            color: #93c5fd !important;
            font-size: 0.72rem !important;
            font-weight: 750 !important;
            letter-spacing: 0.04em !important;
            text-transform: uppercase !important;
        }

        .master-data-page .fi-ta-header-cell svg,
        .master-data-page .fi-ta-header-cell .fi-ta-header-cell-sort-icon {
            color: #60a5fa !important;
        }

        /* --- Rows & Alternating Strips --- */
        .master-data-page .fi-ta-table tbody,
        .master-data-page .fi-ta table tbody {
            background: #000000 !important;
        }

        .master-data-page .fi-ta-table tbody tr,
        .master-data-page .fi-ta table tbody tr,
        .dark .master-data-page .fi-ta-table tbody tr,
        .dark .master-data-page .fi-ta table tbody tr {
            background: #000000 !important;
            transition: background-color 0.15s ease !important;
        }

        .master-data-page .fi-ta-table tbody tr:nth-child(even),
        .master-data-page .fi-ta table tbody tr:nth-child(even),
        .dark .master-data-page .fi-ta-table tbody tr:nth-child(even),
        .dark .master-data-page .fi-ta table tbody tr:nth-child(even) {
            background: #060910 !important;
        }

        .master-data-page .fi-ta-table tbody tr:hover,
        .master-data-page .fi-ta table tbody tr:hover,
        .dark .master-data-page .fi-ta-table tbody tr:hover,
        .dark .master-data-page .fi-ta table tbody tr:hover {
            background: #0e1726 !important;
        }

        .master-data-page .fi-ta-cell,
        .dark .master-data-page .fi-ta-cell {
            padding: 0.5rem 1rem !important;
            vertical-align: middle !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04) !important;
        }

        .master-data-page .fi-ta-text {
            min-height: 2rem;
        }

        .master-data-page .fi-ta-text-item {
            font-size: 0.82rem !important;
            line-height: 1.4 !important;
            color: #f1f5f9 !important;
        }

        .master-data-page .fi-ta-image img {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
        }

        .master-data-page .fi-ta-actions {
            white-space: nowrap;
        }

        /* --- Badges: ID No., Designation, Employment Status --- */
        .master-data-page .fi-ta-cell-employee-uid .fi-badge {
            background: rgba(30, 58, 138, 0.3) !important;
            border: 1px solid rgba(96, 165, 250, 0.35) !important;
            color: #93c5fd !important;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-size: 0.74rem !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            padding: 2px 7px !important;
        }

        .master-data-page .fi-ta-cell-employee-designation-title .fi-badge {
            background: rgba(59, 130, 246, 0.14) !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
            color: #60a5fa !important;
            font-size: 0.74rem !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
        }

        .master-data-page .fi-ta-cell-employee-employment-type .fi-badge {
            font-size: 0.74rem !important;
            font-weight: 600 !important;
            border-radius: 9999px !important;
            padding: 2px 9px !important;
        }

        .master-data-page .fi-ta-cell-employee-employment-type .fi-badge.fi-color-success,
        .master-data-page .fi-ta-cell-employee-employment-type .fi-badge:not(.fi-color-danger) {
            background: rgba(34, 197, 94, 0.14) !important;
            border: 1px solid rgba(34, 197, 94, 0.32) !important;
            color: #4ade80 !important;
        }

        /* Employment ended rows */
        .master-data-page .fi-ta-table tbody tr.employee-employment-ended > td {
            background: #fee2e2 !important;
            color: #7f1d1d !important;
        }
        .master-data-page .fi-ta-table tbody tr.employee-employment-ended:hover > td {
            background: #fecaca !important;
        }
        .dark .master-data-page .fi-ta-table tbody tr.employee-employment-ended > td {
            background: rgba(127, 29, 29, 0.35) !important;
            color: #fee2e2 !important;
        }
        .dark .master-data-page .fi-ta-table tbody tr.employee-employment-ended:hover > td {
            background: rgba(153, 27, 27, 0.5) !important;
        }

        /* --- Pagination Controls --- */
        .master-data-page .fi-ta-pagination,
        .master-data-page .fi-pagination,
        .dark .master-data-page .fi-ta-pagination,
        .dark .master-data-page .fi-pagination {
            background: #080c14 !important;
            border-top: 1px solid rgba(255, 255, 255, 0.06) !important;
            padding: 0.65rem 1.15rem !important;
        }

        .master-data-page .fi-pagination-record-select .fi-select-input-wrp,
        .master-data-page .fi-pagination select,
        .dark .master-data-page .fi-pagination-record-select .fi-select-input-wrp,
        .dark .master-data-page .fi-pagination select {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            color: #cbd5e1 !important;
            border-radius: 6px !important;
            font-size: 0.8rem !important;
        }

        .master-data-page .fi-pagination-nav-btn,
        .master-data-page .fi-pagination .fi-btn,
        .dark .master-data-page .fi-pagination-nav-btn,
        .dark .master-data-page .fi-pagination .fi-btn {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            color: #cbd5e1 !important;
            border-radius: 6px !important;
            font-size: 0.8rem !important;
            padding: 0.35rem 0.75rem !important;
            transition: all 0.15s ease !important;
        }

        .master-data-page .fi-pagination-nav-btn:hover,
        .master-data-page .fi-pagination .fi-btn:hover,
        .dark .master-data-page .fi-pagination-nav-btn:hover,
        .dark .master-data-page .fi-pagination .fi-btn:hover {
            background: #0e1726 !important;
            border-color: #334155 !important;
            color: #ffffff !important;
        }

        /* --- Custom Sleek Horizontal Scrollbar --- */
        .master-data-page .fi-ta-ctn::-webkit-scrollbar {
            height: 7px;
        }

        .master-data-page .fi-ta-ctn::-webkit-scrollbar-track {
            background: #05070c;
        }

        .master-data-page .fi-ta-ctn::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 4px;
        }

        .master-data-page .fi-ta-ctn::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }

        /* --- Responsive Viewport Adjustments --- */
        @media (max-width: 640px) {
            .master-data-toolbar {
                align-items: stretch;
                gap: 0.75rem;
                padding: 0.8rem 0.9rem;
            }
            .master-data-header-controls {
                width: 100%;
                align-items: flex-start;
            }
            .master-data-title {
                font-size: 1.25rem;
            }
            .master-data-page .fi-ta-header-toolbar {
                padding: 0.75rem !important;
            }
            .master-data-page .fi-ta-header-toolbar > div:last-child {
                width: 100%;
            }
            .master-data-page .fi-ta-search-field {
                flex: 1 1 12rem;
                width: auto;
            }
            .master-data-page .fi-ta table {
                min-width: 980px;
            }
        }
    </style>

    <div class="master-data-page">
        @livewire(\App\Filament\Widgets\EmployeeDetailsTable::class, [
            'inactiveOnly' => true,
            'returnPageUrl' => \App\Filament\Pages\InactiveEmployeeDetails::getUrl(),
        ], key('inactive-employees-table'))
    </div>
</x-filament-panels::page>