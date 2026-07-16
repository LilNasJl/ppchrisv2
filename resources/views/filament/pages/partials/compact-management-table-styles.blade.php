<style data-compact-management-tables>
    .fi-page {
        --management-row-even: rgba(248, 250, 252, .94);
        --management-row-hover: rgba(59, 130, 246, .08);
    }

    .dark .fi-page {
        --management-row-even: rgba(30, 41, 59, .58);
        --management-row-hover: rgba(96, 165, 250, .16);
    }

    .fi-page .fi-ta,
    .fi-page .fi-ta-ctn {
        max-width: 100%;
        min-width: 0;
    }

    .fi-page .fi-ta-ctn {
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .fi-page .fi-ta table {
        min-width: 100%;
        width: max-content;
    }

    .fi-page .fi-ta-header-cell {
        min-width: 5rem;
        padding: .4rem .55rem !important;
        white-space: nowrap;
    }

    .fi-page .fi-ta-header-cell-label {
        font-size: .72rem;
        line-height: 1.15;
    }

    .fi-page .fi-ta-text {
        min-height: 2.1rem;
        padding: .32rem .55rem !important;
    }

    .fi-page .fi-ta-text-item {
        font-size: .78rem !important;
        line-height: 1.2 !important;
    }

    .fi-page .fi-ta-image {
        padding-block: .28rem !important;
    }

    .fi-page .fi-ta-image img {
        height: 2rem !important;
        width: 2rem !important;
    }

    .fi-page .fi-ta-actions {
        padding-block: .25rem !important;
    }

    .fi-page .fi-ta table tbody tr:nth-child(even) {
        background: var(--management-row-even) !important;
    }

    .fi-page .fi-ta table tbody tr:hover {
        background: var(--management-row-hover) !important;
    }

    .fi-page :is(
        .fi-ta-header-cell-employee-lastname,
        .fi-ta-cell-employee-lastname,
        .fi-ta-header-cell-full-name,
        .fi-ta-cell-full-name,
        .fi-ta-header-cell-employee-full-name,
        .fi-ta-cell-employee-full-name
    ) {
        max-width: 15rem;
        min-width: 12rem;
        width: 15rem;
    }

    .fi-page :is(
        .fi-ta-header-cell-employee-department-name,
        .fi-ta-cell-employee-department-name,
        .fi-ta-header-cell-department-name,
        .fi-ta-cell-department-name
    ) {
        max-width: 12rem;
        min-width: 9rem;
        width: 12rem;
    }

    .fi-page :is(
        .fi-ta-header-cell-employee-branch-branch-name,
        .fi-ta-cell-employee-branch-branch-name,
        .fi-ta-header-cell-branch-branch-name,
        .fi-ta-cell-branch-branch-name,
        .fi-ta-header-cell-amortization-start-payroll-period-title,
        .fi-ta-cell-amortization-start-payroll-period-title
    ) {
        max-width: 12rem;
        min-width: 9rem;
        width: 12rem;
    }

    .fi-page :is(
        .fi-ta-header-cell-loan-type,
        .fi-ta-cell-loan-type,
        .fi-ta-header-cell-schedule,
        .fi-ta-cell-schedule
    ) {
        max-width: 11rem;
        min-width: 8rem;
        width: 11rem;
    }

    .fi-page :is(
        .fi-ta-cell-employee-lastname,
        .fi-ta-cell-full-name,
        .fi-ta-cell-employee-full-name,
        .fi-ta-cell-employee-department-name,
        .fi-ta-cell-department-name,
        .fi-ta-cell-employee-branch-branch-name,
        .fi-ta-cell-branch-branch-name,
        .fi-ta-cell-amortization-start-payroll-period-title,
        .fi-ta-cell-loan-type,
        .fi-ta-cell-schedule
    ) .fi-ta-text-item {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap !important;
    }

    @media (max-width: 640px) {
        .fi-page :is(
            .fi-ta-header-cell-employee-lastname,
            .fi-ta-cell-employee-lastname,
            .fi-ta-header-cell-full-name,
            .fi-ta-cell-full-name,
            .fi-ta-header-cell-employee-full-name,
            .fi-ta-cell-employee-full-name
        ) {
            max-width: 11rem;
            min-width: 11rem;
            width: 11rem;
        }
    }
</style>
