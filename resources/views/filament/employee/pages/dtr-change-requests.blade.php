<x-filament-panels::page>
    <style>
        /* Suppress default Filament page header if present */
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .dtr-employee-workspace {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
            margin-bottom: 0.5rem;
        }

        /* Period Select Styling inside Table Header */
        .dtr-period-select-wrap {
            width: 270px;
            min-width: 220px;
            max-width: 320px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
        }

        .dtr-select-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .dtr-select-icon {
            position: absolute;
            left: 0.65rem;
            width: 0.95rem;
            height: 0.95rem;
            color: rgb(100, 116, 139);
            pointer-events: none;
            z-index: 2;
        }

        .dark .dtr-select-icon {
            color: rgb(148, 163, 184);
        }

        .dtr-select-chevron {
            position: absolute;
            right: 0.65rem;
            width: 0.85rem;
            height: 0.85rem;
            color: rgb(100, 116, 139);
            pointer-events: none;
            z-index: 2;
        }

        .dark .dtr-select-chevron {
            color: rgb(148, 163, 184);
        }

        .dtr-native-select {
            width: 100%;
            height: 2.1rem;
            min-height: 2.1rem;
            box-sizing: border-box;
            padding: 0.25rem 1.85rem 0.25rem 2rem;
            border-radius: 0.45rem;
            border: 1px solid rgb(203, 213, 225);
            background-color: #ffffff;
            color: rgb(15, 23, 42);
            font-size: 0.775rem;
            font-weight: 600;
            line-height: 1.4;
            transition: all 120ms ease;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .dtr-native-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.18);
        }

        .dark .dtr-native-select {
            border-color: rgb(71, 85, 105);
            background-color: rgb(30, 41, 59);
            color: #ffffff;
            box-shadow: none;
        }

        .dark .dtr-native-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25);
        }

        .dark .dtr-native-select option {
            background-color: rgb(30, 41, 59);
            color: #ffffff;
        }

        /* Clean & Proportionate Filament Table Styling */
        .fi-page .fi-ta,
        .fi-page .fi-ta-ctn {
            max-width: 100%;
            min-width: 0;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .fi-page .fi-ta,
        .dark .fi-page .fi-ta-ctn {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .fi-page .fi-ta-header {
            gap: 0.65rem;
            padding: 0.65rem 0.95rem !important;
            background: rgb(248, 250, 252);
            border-bottom: 1px solid rgb(226, 232, 240);
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .dark .fi-page .fi-ta-header {
            background: rgb(30, 41, 59);
            border-bottom-color: rgb(51, 65, 85);
        }

        .fi-page .fi-ta-header-heading {
            font-size: 0.9rem !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(15, 23, 42);
            margin: 0;
            line-height: 1.25;
        }

        .dark .fi-page .fi-ta-header-heading {
            color: #ffffff;
        }

        .fi-page .fi-ta-header-description {
            font-size: 0.75rem !important;
            color: rgb(100, 116, 139) !important;
            margin-top: 0.15rem;
        }

        .dark .fi-page .fi-ta-header-description {
            color: rgb(148, 163, 184) !important;
        }

        /* Table Header Actions Toolbar (Dropdown + Buttons) */
        .fi-page .fi-ta-header .fi-ta-actions {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0.5rem !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .fi-page .fi-ta-header .fi-ta-actions .fi-btn {
            min-height: 2.1rem !important;
            height: 2.1rem !important;
            width: auto !important;
            max-width: none !important;
            padding: 0.35rem 0.85rem !important;
            font-size: 0.775rem !important;
            font-weight: 600 !important;
            border-radius: 0.45rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.35rem !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .fi-page .fi-ta-header .fi-ta-actions .fi-btn-label {
            display: inline-block !important;
            white-space: nowrap !important;
        }

        .fi-page .fi-ta-header .fi-ta-actions .fi-btn-icon {
            width: 0.95rem !important;
            height: 0.95rem !important;
            flex-shrink: 0 !important;
        }

        .fi-page .fi-ta table {
            font-size: 0.8rem !important;
            line-height: 1.3 !important;
            border-collapse: collapse !important;
            width: 100% !important;
        }

        .fi-page .fi-ta-header-cell {
            padding: 0.42rem 0.6rem !important;
            font-size: 0.685rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.04em !important;
            color: rgb(71, 85, 105) !important;
            background: rgb(241, 245, 249) !important;
            border-bottom: 1px solid rgb(203, 213, 225) !important;
            white-space: nowrap !important;
            height: auto !important;
            min-height: 0 !important;
        }

        .dark .fi-page .fi-ta-header-cell {
            color: rgb(203, 213, 225) !important;
            background: rgb(30, 41, 59) !important;
            border-bottom-color: rgb(71, 85, 105) !important;
        }

        .fi-page .fi-ta-cell,
        .fi-page .fi-ta table tbody td {
            padding: 0.35rem 0.55rem !important;
            border-bottom: 1px solid rgb(226, 232, 240) !important;
            background: transparent !important;
            vertical-align: middle !important;
            height: auto !important;
            min-height: 0 !important;
        }

        .dark .fi-page .fi-ta-cell,
        .dark .fi-page .fi-ta table tbody td {
            border-bottom-color: rgb(51, 65, 85) !important;
        }

        .fi-page .fi-ta-row,
        .fi-page .fi-ta table tbody tr {
            height: auto !important;
            min-height: 0 !important;
        }

        .fi-page .fi-ta-col-wrp,
        .fi-page .fi-ta-text,
        .fi-page .fi-ta-cell > div,
        .fi-page .fi-ta-cell > * {
            min-height: 0 !important;
            height: auto !important;
            padding-block: 0 !important;
            margin-block: 0 !important;
            line-height: 1.3 !important;
        }

        .fi-page .fi-ta-text-item {
            font-size: 0.8rem !important;
            line-height: 1.3 !important;
            padding: 0 !important;
            margin: 0 !important;
            display: inline-block;
        }

        .fi-page .dtr-cell-index,
        .fi-page .fi-ta-cell-index {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            color: rgb(148, 163, 184) !important;
            text-align: center !important;
            width: 2.35rem !important;
            min-width: 2.35rem !important;
            max-width: 2.35rem !important;
            padding: 0.32rem 0.35rem !important;
        }

        .dark .fi-page .dtr-cell-index,
        .dark .fi-page .fi-ta-cell-index {
            color: rgb(100, 116, 139) !important;
        }

        .fi-page .fi-ta table tbody tr:nth-child(even) {
            background-color: rgba(248, 250, 252, 0.65) !important;
        }

        .dark .fi-page .fi-ta table tbody tr:nth-child(even) {
            background-color: rgba(30, 41, 59, 0.35) !important;
        }

        .fi-page .fi-ta table tbody tr:hover {
            background-color: rgba(239, 246, 255, 0.8) !important;
        }

        .dark .fi-page .fi-ta table tbody tr:hover {
            background-color: rgba(51, 65, 85, 0.5) !important;
        }

        .fi-page .fi-ta .fi-badge {
            padding: 0.12rem 0.45rem !important;
            font-size: 0.7rem !important;
            font-weight: 750 !important;
            line-height: 1.2 !important;
            min-height: 0 !important;
            height: auto !important;
            border-radius: 9999px !important;
            white-space: nowrap !important;
        }

        /* Table Row Actions inside td */
        .fi-page .fi-ta table tbody td .fi-ta-actions {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0.25rem !important;
            padding: 0.2rem 0.45rem !important;
            white-space: nowrap !important;
        }

        .fi-page .fi-ta table tbody td .fi-ta-actions .fi-btn,
        .fi-page .fi-ta table tbody td .fi-ta-actions .fi-icon-btn {
            min-height: 1.75rem !important;
            height: 1.75rem !important;
            width: 1.75rem !important;
            padding: 0 !important;
            font-size: 0.725rem !important;
            border-radius: 0.35rem !important;
        }

        .fi-page .fi-pagination {
            padding: 0.45rem 0.85rem !important;
        }

        .fi-page .fi-pagination button,
        .fi-page .fi-pagination select,
        .fi-page .fi-pagination span {
            font-size: 0.75rem !important;
        }

        /* Tab Bar Styling */
        .fi-page .fi-tabs {
            background: #ffffff;
            border: 1px solid rgb(226, 232, 240);
            border-radius: 0.65rem;
            padding: 0.3rem;
            gap: 0.25rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            margin-bottom: 0.5rem;
        }

        .dark .fi-page .fi-tabs {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .fi-page .fi-tabs-item {
            border-radius: 0.45rem;
            font-size: 0.775rem;
            font-weight: 600;
            padding: 0.3rem 0.75rem;
            min-height: 2rem;
            transition: all 120ms ease;
        }

        .fi-page .fi-tabs-item:hover {
            background: rgb(241, 245, 249);
        }

        .dark .fi-page .fi-tabs-item:hover {
            background: rgb(30, 41, 59);
        }

        .fi-page .fi-tabs-item.fi-active {
            background: #2563eb !important;
            color: #ffffff !important;
        }

        .dark .fi-page .fi-tabs-item.fi-active {
            background: #2563eb !important;
            color: #ffffff !important;
        }

        .fi-page .fi-tabs-item.fi-active :is(span, svg) {
            color: #ffffff !important;
        }
    </style>

    <div wire:poll.15s="markReviewedRequestsAsSeen" class="dtr-employee-workspace">
        {{-- Tabs & Table Container --}}
        <section class="dtr-table-container">
            {{ $this->content }}
        </section>
    </div>
</x-filament-panels::page>
