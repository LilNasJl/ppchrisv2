<div class="payroll-inline-table">
    <style>
        .payroll-inline-table {
            --payroll-row-hover: rgba(59, 130, 246, 0.04);
            --payroll-sticky-bg: #ffffff;
            --payroll-sticky-hover-bg: #f8fafc;
            --payroll-sticky-header-bg: #f1f5f9;
            --payroll-summary-bg: rgba(59, 130, 246, 0.12);
            --payroll-sticky-border: rgba(203, 213, 225, 0.9);
            max-width: 100%;
            width: 100%;
            container-type: inline-size;
        }

        .dark .payroll-inline-table {
            --payroll-row-hover: rgba(255, 255, 255, 0.03);
            --payroll-sticky-bg: rgb(15, 23, 42);
            --payroll-sticky-hover-bg: rgb(30, 41, 59);
            --payroll-sticky-header-bg: rgb(30, 41, 59);
            --payroll-summary-bg: rgba(30, 58, 138, 0.4);
            --payroll-sticky-border: rgba(51, 65, 85, 0.95);
        }

        .payroll-inline-table .fi-ta {
            min-width: 100%;
            width: 100%;
        }

        .payroll-inline-table .fi-ta-ctn {
            min-width: 100%;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            box-shadow: none !important;
            background: transparent !important;
            overflow: hidden;
        }

        .dark .payroll-inline-table .fi-ta-ctn {
            border-color: rgb(51, 65, 85);
            background: transparent !important;
        }

        /* Combined 1 Horizontal Table Header Toolbar */
        .payroll-inline-table .fi-ta-header-toolbar {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 0.5rem 1rem !important;
            padding: 0.45rem 0.75rem !important;
            background: transparent !important;
            border-bottom: 1px solid rgb(226, 232, 240);
        }

        .dark .payroll-inline-table .fi-ta-header-toolbar {
            background: transparent !important;
            border-bottom-color: rgb(51, 65, 85);
        }

        .payroll-inline-table .fi-ta-header-toolbar .fi-ta-actions {
            display: flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
        }

        .payroll-inline-table .fi-ta-header-toolbar .fi-btn {
            min-height: 2rem !important;
            height: 2rem !important;
            padding: 0.25rem 0.65rem !important;
            font-size: 0.76rem !important;
            font-weight: 650 !important;
            border-radius: 0.42rem !important;
        }

        .payroll-inline-table .fi-ta-content {
            overflow-x: auto;
            max-width: 100%;
            background: transparent !important;
        }

        .payroll-inline-table table {
            min-width: 100%;
            width: max-content;
            border-collapse: separate;
            border-spacing: 0;
            font-variant-numeric: tabular-nums;
            background: transparent !important;
        }

        .payroll-inline-table .fi-ta-header-cell {
            min-width: 4.75rem;
            padding: 0.42rem 0.55rem !important;
            white-space: nowrap;
            background: var(--payroll-sticky-header-bg);
            border-bottom: 1px solid rgb(203, 213, 225);
        }

        .dark .payroll-inline-table .fi-ta-header-cell {
            border-bottom-color: rgb(51, 65, 85);
            background: var(--payroll-sticky-header-bg);
        }

        .payroll-inline-table .fi-ta-header-cell-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            line-height: 1.15;
            color: rgb(71, 85, 105);
        }

        .dark .payroll-inline-table .fi-ta-header-cell-label {
            color: rgb(203, 213, 225);
        }

        .payroll-inline-table .fi-ta-cell {
            padding: 0.28rem 0.55rem !important;
            border-bottom: 1px solid rgb(226, 232, 240);
            vertical-align: middle;
        }

        .dark .payroll-inline-table .fi-ta-cell {
            border-bottom-color: rgb(51, 65, 85);
        }

        .payroll-inline-table .fi-ta-text {
            min-height: 1.85rem;
            padding: 0.28rem 0.55rem !important;
            white-space: nowrap;
        }

        .payroll-inline-table .fi-ta-text-item {
            font-size: 0.75rem !important;
            line-height: 1.2 !important;
        }

        .payroll-inline-table .fi-ta-text-input {
            min-width: 5.25rem !important;
            padding: 0.18rem 0.35rem !important;
        }

        .payroll-inline-table table tbody tr {
            background: transparent;
        }

        .payroll-inline-table table tbody tr:nth-child(even) {
            background: transparent;
        }

        .payroll-inline-table table tbody tr:hover {
            background: var(--payroll-row-hover) !important;
        }

        .payroll-inline-table table tbody tr.fi-ta-summary-row {
            background: var(--payroll-summary-bg) !important;
            border-top: 2px solid rgb(37, 99, 235);
        }

        .payroll-inline-table table tbody tr.fi-ta-summary-row .fi-ta-cell {
            color: rgb(15, 23, 42) !important;
            font-weight: 800;
            background: var(--payroll-summary-bg) !important;
        }

        .dark .payroll-inline-table table tbody tr.fi-ta-summary-row {
            background: var(--payroll-summary-bg) !important;
            border-top-color: rgb(96, 165, 250);
        }

        .dark .payroll-inline-table table tbody tr.fi-ta-summary-row .fi-ta-cell {
            color: #f8fafc !important;
            background: var(--payroll-summary-bg) !important;
        }

        .payroll-inline-table input[type="number"] {
            font-size: 0.74rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            height: 1.75rem;
            min-width: 4.85rem;
            text-align: right;
            width: 4.85rem;
            border-radius: 0.375rem;
        }

        /* Sticky Left Column 1: Index (#) */
        .payroll-inline-table .fi-ta-header-cell-index,
        .payroll-inline-table .fi-ta-cell-index {
            inset-inline-start: 0 !important;
            min-width: 2.75rem !important;
            width: 2.75rem !important;
            max-width: 2.75rem !important;
            position: sticky !important;
            text-align: center !important;
        }

        /* Sticky Left Column 2: Bank ID No. */
        .payroll-inline-table .fi-ta-header-cell-bank-id-no,
        .payroll-inline-table .fi-ta-cell-bank-id-no {
            inset-inline-start: 2.75rem !important;
            min-width: 6.5rem !important;
            width: 6.5rem !important;
            max-width: 6.5rem !important;
            position: sticky !important;
        }

        /* Sticky Left Column 3: Name */
        .payroll-inline-table .fi-ta-header-cell-name,
        .payroll-inline-table .fi-ta-cell-name {
            inset-inline-start: 9.25rem !important;
            min-width: 13rem !important;
            width: 13rem !important;
            max-width: 13rem !important;
            position: sticky !important;
            border-inline-end: 1px solid var(--payroll-sticky-border) !important;
            box-shadow: 3px 0 6px -2px rgba(0, 0, 0, 0.15) !important;
        }

        .dark .payroll-inline-table .fi-ta-header-cell-name,
        .dark .payroll-inline-table .fi-ta-cell-name {
            box-shadow: 3px 0 8px -2px rgba(0, 0, 0, 0.45) !important;
        }

        /* Opaque backgrounds for sticky headers to prevent scrolled text bleed-through */
        .payroll-inline-table .fi-ta-header-cell-index,
        .payroll-inline-table .fi-ta-header-cell-bank-id-no,
        .payroll-inline-table .fi-ta-header-cell-name {
            background: var(--payroll-sticky-header-bg) !important;
            z-index: 10 !important;
        }

        /* Opaque backgrounds for sticky body cells to prevent scrolled text bleed-through */
        .payroll-inline-table .fi-ta-cell-index,
        .payroll-inline-table .fi-ta-cell-bank-id-no,
        .payroll-inline-table .fi-ta-cell-name {
            background: var(--payroll-sticky-bg) !important;
            z-index: 6 !important;
        }

        /* Opaque backgrounds on row hover for sticky cells */
        .payroll-inline-table tbody tr:hover .fi-ta-cell-index,
        .payroll-inline-table tbody tr:hover .fi-ta-cell-bank-id-no,
        .payroll-inline-table tbody tr:hover .fi-ta-cell-name {
            background: var(--payroll-sticky-hover-bg) !important;
        }

        /* Opaque backgrounds on summary/total row for sticky cells */
        .payroll-inline-table tbody tr.fi-ta-summary-row .fi-ta-cell-index,
        .payroll-inline-table tbody tr.fi-ta-summary-row .fi-ta-cell-bank-id-no,
        .payroll-inline-table tbody tr.fi-ta-summary-row .fi-ta-cell-name {
            background: var(--payroll-summary-bg) !important;
        }

        .payroll-inline-table .fi-ta-cell-name .fi-ta-text-item {
            font-weight: 700;
            color: rgb(30, 64, 175);
        }

        .dark .payroll-inline-table .fi-ta-cell-name .fi-ta-text-item {
            color: rgb(147, 197, 253);
        }

        .payroll-inline-table .fi-ta-cell-net-pay .fi-ta-text-item,
        .payroll-inline-table .fi-ta-cell-gross-pay .fi-ta-text-item {
            font-weight: 700;
        }

        .payroll-inline-table .fi-ta-cell-net-pay .fi-ta-text-item {
            color: rgb(21, 128, 61);
        }

        .dark .payroll-inline-table .fi-ta-cell-net-pay .fi-ta-text-item {
            color: rgb(74, 222, 128);
        }

        .payroll-inline-table .fi-ta-header-cell-designation,
        .payroll-inline-table .fi-ta-cell-designation,
        .payroll-inline-table .fi-ta-header-cell-branch,
        .payroll-inline-table .fi-ta-cell-branch {
            max-width: 9.5rem;
            min-width: 7.5rem;
        }

        .payroll-inline-table .fi-ta-cell-name .fi-ta-text-item,
        .payroll-inline-table .fi-ta-cell-designation .fi-ta-text-item,
        .payroll-inline-table .fi-ta-cell-branch .fi-ta-text-item {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @container (width < 768px) {
            .payroll-inline-table .fi-ta-header-cell-index,
            .payroll-inline-table .fi-ta-cell-index,
            .payroll-inline-table .fi-ta-header-cell-bank-id-no,
            .payroll-inline-table .fi-ta-cell-bank-id-no,
            .payroll-inline-table .fi-ta-header-cell-name,
            .payroll-inline-table .fi-ta-cell-name {
                position: static !important;
                box-shadow: none !important;
            }

            .payroll-inline-table .fi-ta-header-cell-name,
            .payroll-inline-table .fi-ta-cell-name {
                max-width: 10.5rem;
                min-width: 9.5rem;
                width: auto;
            }
        }
    </style>

    {{ $this->table }}
</div>
