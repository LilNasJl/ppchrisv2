<x-filament-panels::page>
    @include('filament.pages.partials.dtr-black-table-surfaces')
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        /* Dark Gradient Page Header */
        .fi-page:has(.hr-dtr-change-requests-page) .fi-header {
            background: linear-gradient(110deg, #0f274a, #08152b 68%, #030814) !important;
            border: 1px solid rgba(147, 197, 253, 0.22) !important;
            border-radius: 0.75rem !important;
            padding: 1.1rem 1.35rem !important;
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 0.85rem 1.25rem !important;
        }

        .fi-page:has(.hr-dtr-change-requests-page) .fi-header-heading {
            color: #ffffff !important;
            font-size: 1.5rem !important;
            font-weight: 800 !important;
            line-height: 1.2 !important;
        }

        .fi-page:has(.hr-dtr-change-requests-page) .fi-header .fi-ac .fi-btn {
            background: rgba(255, 255, 255, 0.12) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
            border-radius: 0.5rem !important;
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            padding: 0.42rem 0.85rem !important;
        }

        .fi-page:has(.hr-dtr-change-requests-page) .fi-header .fi-ac .fi-btn:hover {
            background: rgba(255, 255, 255, 0.2) !important;
        }

        /* Tabs Bar */
        .hr-dtr-change-requests-page .fi-sc-tabs > .fi-tabs,
        .fi-page:has(.hr-dtr-change-requests-page) .fi-tabs {
            background: #000000 !important;
            border: 1px solid #303030 !important;
            border-radius: 0.65rem !important;
            padding: 0.3rem !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            flex-wrap: nowrap !important;
        }

        /* Modal Window & Footer Button Styling */
        .fi-modal-window,
        .dark .fi-modal-window,
        .fi-modal-header,
        .fi-modal-content,
        .fi-modal-footer {
            background-color: #0f172a !important;
        }

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

        /* Mobile View Enhancements */
        @media (max-width: 640px) {
            .fi-page:has(.hr-dtr-change-requests-page) .fi-page-content {
                padding-inline: 0.5rem !important;
            }

            .fi-page:has(.hr-dtr-change-requests-page) .fi-header {
                padding: 0.9rem 1rem !important;
                gap: 0.65rem !important;
            }

            .fi-page:has(.hr-dtr-change-requests-page) .fi-header-heading {
                font-size: 1.3rem !important;
            }

            .fi-page:has(.hr-dtr-change-requests-page) .fi-header .fi-ac,
            .fi-page:has(.hr-dtr-change-requests-page) .fi-header .fi-ac .fi-btn {
                width: 100% !important;
                justify-content: center !important;
            }

            /* Toolbar Search & Filter Row */
            .hr-dtr-change-requests-page .fi-ta-header-toolbar {
                padding: 0.65rem 0.75rem !important;
            }

            .hr-dtr-change-requests-page .fi-ta-header-toolbar > div:last-child {
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
                gap: 0.45rem !important;
            }

            .hr-dtr-change-requests-page .fi-ta-search-field {
                flex: 1 1 auto !important;
                width: auto !important;
            }

            /* Compact Mobile Table */
            .hr-dtr-change-requests-page .fi-ta-content {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .hr-dtr-change-requests-page .fi-ta table {
                min-width: 100% !important;
                width: 100% !important;
                font-size: 0.7rem !important;
            }

            .hr-dtr-change-requests-page .fi-ta-header-cell,
            .dark .hr-dtr-change-requests-page .fi-ta-header-cell {
                padding: 0.4rem 0.45rem !important;
                min-width: 0 !important;
            }

            .hr-dtr-change-requests-page .fi-ta-header-cell-label,
            .dark .hr-dtr-change-requests-page .fi-ta-header-cell-label {
                font-size: 0.64rem !important;
            }

            .hr-dtr-change-requests-page .fi-ta-cell,
            .dark .hr-dtr-change-requests-page .fi-ta-cell {
                padding: 0.3rem 0.45rem !important;
                min-width: 0 !important;
            }

            .hr-dtr-change-requests-page .fi-ta-text {
                padding: 0.15rem 0.25rem !important;
                min-height: 1.4rem !important;
            }

            .hr-dtr-change-requests-page .fi-ta-text-item,
            .dark .hr-dtr-change-requests-page .fi-ta-text-item {
                font-size: 0.7rem !important;
                line-height: 1.25 !important;
            }

            .hr-dtr-change-requests-page .fi-ta .fi-badge {
                font-size: 0.62rem !important;
                padding: 1px 5px !important;
            }

            /* Filter Popover Alignment Fix on Mobile */
            .hr-dtr-change-requests-page .fi-ta-filters-dropdown,
            .hr-dtr-change-requests-page .fi-dropdown-panel {
                max-width: calc(100vw - 1.5rem) !important;
                width: calc(100vw - 1.5rem) !important;
                right: 0.75rem !important;
                left: auto !important;
                box-sizing: border-box !important;
            }

            /* Modal Full Width Bottom Sheet for Mobile View */
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

    <div class="hr-dtr-change-requests-page">
        {{ $this->content }}
    </div>
</x-filament-panels::page>
