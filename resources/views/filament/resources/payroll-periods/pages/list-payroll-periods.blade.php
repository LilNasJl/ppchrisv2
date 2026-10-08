<x-filament-panels::page>
    <style>
        /* Completely Hide Page Header (Title, Subheading, Breadcrumbs) */
        .fi-page-header-main-ctn > header.fi-header,
        .fi-header-heading,
        .fi-header-subheading,
        .fi-breadcrumbs {
            display: none !important;
        }

        .fi-page {
            gap: 0.5rem !important;
        }

        /* Compact Status Tabs */
        .fi-page .fi-sc-tabs,
        .fi-page .fi-tabs {
            padding: 0.2rem 0.3rem !important;
            gap: 0.25rem !important;
            margin-block: 0 !important;
        }

        .fi-page .fi-tabs-item {
            padding: 0.28rem 0.6rem !important;
            font-size: 0.74rem !important;
            font-weight: 650 !important;
            border-radius: 0.4rem !important;
        }

        .fi-page .fi-tabs-item .fi-badge {
            padding: 0.05rem 0.35rem !important;
            font-size: 0.66rem !important;
        }

        /* Table Container */
        .fi-page .fi-ta-ctn {
            border-radius: 0.6rem !important;
            border: 1px solid rgb(226, 232, 240) !important;
            background: #ffffff !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
            overflow: hidden !important;
        }

        .dark .fi-page .fi-ta-ctn {
            border-color: rgb(51, 65, 85) !important;
            background: rgb(15, 23, 42) !important;
        }

        /* Table Header Toolbar (Return Button Left + Search & Filters Right) */
        .fi-page .fi-ta-header-toolbar {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            padding: 0.65rem 1rem !important;
            gap: 0.75rem !important;
            border-bottom: 1px solid rgb(226, 232, 240) !important;
        }

        .dark .fi-page .fi-ta-header-toolbar {
            border-bottom-color: rgb(51, 65, 85) !important;
        }

        .fi-page .fi-ta-header-toolbar .fi-ta-actions {
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Return Button Styling with solid background */
        .fi-page .fi-ta-header-toolbar a.fi-btn,
        .fi-page .fi-ta-header-toolbar button.fi-btn {
            background: rgb(241, 245, 249) !important;
            border: 1px solid rgb(203, 213, 225) !important;
            color: rgb(30, 41, 59) !important;
            font-weight: 650 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.15s ease-in-out !important;
        }

        .fi-page .fi-ta-header-toolbar a.fi-btn:hover,
        .fi-page .fi-ta-header-toolbar button.fi-btn:hover {
            background: rgb(226, 232, 240) !important;
            border-color: rgb(148, 163, 184) !important;
            color: rgb(15, 23, 42) !important;
        }

        .dark .fi-page .fi-ta-header-toolbar a.fi-btn,
        .dark .fi-page .fi-ta-header-toolbar button.fi-btn {
            background: rgb(30, 41, 59) !important;
            border-color: rgb(51, 65, 85) !important;
            color: rgb(241, 245, 249) !important;
        }

        .dark .fi-page .fi-ta-header-toolbar a.fi-btn:hover,
        .dark .fi-page .fi-ta-header-toolbar button.fi-btn:hover {
            background: rgb(51, 65, 85) !important;
            border-color: rgb(71, 85, 105) !important;
            color: #ffffff !important;
        }

        /* Hide Column Manager if present */
        .fi-page .fi-ta-col-manager-dropdown {
            display: none !important;
        }

        /* Proper Spacing for Table Column Headers */
        .fi-page .fi-ta-header-cell {
            padding: 0.65rem 0.85rem !important;
            background: rgb(248, 250, 252) !important;
            border-bottom: 1px solid rgb(226, 232, 240) !important;
            white-space: nowrap !important;
        }

        .dark .fi-page .fi-ta-header-cell {
            background: rgb(30, 41, 59) !important;
            border-bottom-color: rgb(51, 65, 85) !important;
        }

        .fi-page .fi-ta-header-cell-label {
            font-size: 0.72rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.035em !important;
            color: rgb(71, 85, 105) !important;
        }

        .dark .fi-page .fi-ta-header-cell-label {
            color: rgb(203, 213, 225) !important;
        }

        /* Table Body Cells */
        .fi-page .fi-ta-cell {
            padding-block: 0 !important;
            padding-inline: 0 !important;
        }

        .fi-page .fi-ta-text {
            padding: 0.42rem 0.85rem !important;
            min-height: 2.15rem !important;
        }

        .fi-page .fi-ta-text-item {
            font-size: 0.78rem !important;
            line-height: 1.25 !important;
            white-space: nowrap !important;
        }

        .fi-page .fi-ta-table tbody tr:nth-child(even) {
            background-color: rgba(248, 250, 252, 0.75) !important;
        }

        .dark .fi-page .fi-ta-table tbody tr:nth-child(even) {
            background-color: rgba(30, 41, 59, 0.35) !important;
        }

        .fi-page .fi-ta-table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }

        .dark .fi-page .fi-ta-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        .fi-page .fi-ta-badge {
            padding: 0.12rem 0.45rem !important;
            font-size: 0.7rem !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
        }

        .fi-page .fi-ta-cell .fi-ta-actions {
            padding: 0.25rem 0.65rem !important;
        }

        .fi-page .fi-ta-pagination {
            padding: 0.45rem 0.85rem !important;
            border-top: 1px solid rgb(226, 232, 240) !important;
        }

        .dark .fi-page .fi-ta-pagination {
            border-top-color: rgb(51, 65, 85) !important;
        }
    </style>

    {{ $this->content }}
</x-filament-panels::page>
