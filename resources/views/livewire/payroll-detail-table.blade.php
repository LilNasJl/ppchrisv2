<div class="payroll-inline-table">
    <style>
        .payroll-inline-table {
            --payroll-row-even: rgba(248, 250, 252, .94);
            --payroll-row-hover: rgba(59, 130, 246, .08);
            --payroll-sticky: rgb(255, 255, 255);
            --payroll-sticky-even: rgb(248, 250, 252);
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 4px;
            width: 100%;
        }

        .dark .payroll-inline-table {
            --payroll-row-even: rgba(30, 41, 59, .58);
            --payroll-row-hover: rgba(96, 165, 250, .16);
            --payroll-sticky: rgb(17, 24, 39);
            --payroll-sticky-even: rgb(30, 41, 59);
        }

        .payroll-inline-table .fi-ta {
            min-width: 100%;
            width: 100%;
        }

        .payroll-inline-table .fi-ta-ctn {
            min-width: 100%;
        }

        .payroll-inline-table table {
            min-width: 100%;
            width: max-content;
        }

        .payroll-inline-table .fi-ta-header-cell {
            min-width: 5.25rem;
            padding: .35rem .45rem !important;
            white-space: nowrap;
        }

        .payroll-inline-table .fi-ta-header-cell-label {
            font-size: .7rem;
            line-height: 1.15;
        }

        .payroll-inline-table .fi-ta-text {
            min-height: 2rem;
            padding: .3rem .45rem !important;
            white-space: nowrap;
        }

        .payroll-inline-table .fi-ta-text-item {
            font-size: .75rem !important;
            line-height: 1.2 !important;
        }

        .payroll-inline-table .fi-ta-text-input {
            min-width: 5.5rem !important;
            padding: .2rem .35rem !important;
        }

        .payroll-inline-table table tbody tr:nth-child(even) {
            background: var(--payroll-row-even) !important;
        }

        .payroll-inline-table table tbody tr:hover {
            background: var(--payroll-row-hover) !important;
        }

        .payroll-inline-table input[type="number"] {
            font-size: .75rem;
            height: 1.9rem;
            min-width: 5rem;
            text-align: right;
            width: 5rem;
        }

        .payroll-inline-table .fi-ta-header-cell-index,
        .payroll-inline-table .fi-ta-cell-index {
            left: 0;
            min-width: 2.75rem;
            position: sticky;
            width: 2.75rem;
            z-index: 4;
        }

        .payroll-inline-table .fi-ta-header-cell-bank-id-no,
        .payroll-inline-table .fi-ta-cell-bank-id-no {
            left: 2.75rem;
            min-width: 6.5rem;
            position: sticky;
            width: 6.5rem;
            z-index: 4;
        }

        .payroll-inline-table .fi-ta-header-cell-name,
        .payroll-inline-table .fi-ta-cell-name {
            left: 9.25rem;
            max-width: 13rem;
            min-width: 13rem;
            position: sticky;
            width: 13rem;
            z-index: 4;
        }

        .payroll-inline-table .fi-ta-header-cell-index,
        .payroll-inline-table .fi-ta-header-cell-bank-id-no,
        .payroll-inline-table .fi-ta-header-cell-name {
            background: var(--payroll-sticky);
            z-index: 8;
        }

        .payroll-inline-table .fi-ta-cell-index,
        .payroll-inline-table .fi-ta-cell-bank-id-no,
        .payroll-inline-table .fi-ta-cell-name {
            background: var(--payroll-sticky);
        }

        .payroll-inline-table tbody tr:nth-child(even) .fi-ta-cell-index,
        .payroll-inline-table tbody tr:nth-child(even) .fi-ta-cell-bank-id-no,
        .payroll-inline-table tbody tr:nth-child(even) .fi-ta-cell-name {
            background: var(--payroll-sticky-even);
        }

        .payroll-inline-table .fi-ta-header-cell-designation,
        .payroll-inline-table .fi-ta-cell-designation,
        .payroll-inline-table .fi-ta-header-cell-branch,
        .payroll-inline-table .fi-ta-cell-branch {
            max-width: 10rem;
            min-width: 8rem;
        }

        .payroll-inline-table .fi-ta-cell-name .fi-ta-text-item,
        .payroll-inline-table .fi-ta-cell-designation .fi-ta-text-item,
        .payroll-inline-table .fi-ta-cell-branch .fi-ta-text-item {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .payroll-inline-table .fi-ta-header-cell-name,
            .payroll-inline-table .fi-ta-cell-name {
                max-width: 10rem;
                min-width: 10rem;
                width: 10rem;
            }
        }
    </style>

    {{ $this->table }}
</div>
