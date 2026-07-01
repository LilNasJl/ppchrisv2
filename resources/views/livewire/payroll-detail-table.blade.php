<div class="payroll-inline-table">
    <style>
        .payroll-inline-table {
            --payroll-row-even: rgba(248, 250, 252, .94);
            --payroll-row-hover: rgba(59, 130, 246, .08);
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 4px;
            width: 100%;
        }

        .dark .payroll-inline-table {
            --payroll-row-even: rgba(30, 41, 59, .58);
            --payroll-row-hover: rgba(96, 165, 250, .16);
        }

        .payroll-inline-table .fi-ta {
            min-width: 2400px;
            width: max-content;
        }

        .payroll-inline-table .fi-ta-ctn {
            min-width: inherit;
        }

        .payroll-inline-table table {
            min-width: 2400px;
        }

        .payroll-inline-table table tbody tr:nth-child(even) {
            background: var(--payroll-row-even) !important;
        }

        .payroll-inline-table table tbody tr:hover {
            background: var(--payroll-row-hover) !important;
        }

        .payroll-inline-table input[type="number"] {
            min-width: 7.5rem;
            text-align: right;
        }
    </style>

    {{ $this->table }}
</div>
