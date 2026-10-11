<style data-compact-management-tables>
    .fi-page {
        --management-row-even: rgba(248, 250, 252, .75);
        --management-row-hover: rgba(59, 130, 246, .06);
    }

    .dark .fi-page {
        --management-row-even: rgba(30, 41, 59, .35);
        --management-row-hover: rgba(96, 165, 250, .12);
    }

    .fi-page .fi-ta,
    .fi-page .fi-ta-ctn {
        max-width: 100%;
        min-width: 0;
        border-radius: 0.65rem !important;
        border: 1px solid rgb(226, 232, 240) !important;
        background: #ffffff !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
    }

    .dark .fi-page .fi-ta,
    .dark .fi-page .fi-ta-ctn {
        border-color: rgb(51, 65, 85) !important;
        background: rgb(15, 23, 42) !important;
    }

    .fi-page .fi-ta-ctn {
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .fi-page .fi-ta table {
        min-width: 100%;
        width: 100%;
        font-size: 0.775rem !important;
        line-height: 1.25 !important;
        border-collapse: collapse !important;
    }

    .fi-page .fi-ta-header {
        padding: 0.55rem 0.85rem !important;
        background: rgb(248, 250, 252) !important;
        border-bottom: 1px solid rgb(226, 232, 240) !important;
    }

    .dark .fi-page .fi-ta-header {
        background: rgb(30, 41, 59) !important;
        border-bottom-color: rgb(51, 65, 85) !important;
    }

    .fi-page .fi-ta-header-toolbar {
        padding: 0.45rem 0.75rem !important;
        gap: 0.4rem !important;
    }

    .fi-page .fi-ta-header-cell {
        min-width: 4rem;
        padding: 0.42rem 0.6rem !important;
        white-space: nowrap !important;
        background: rgb(241, 245, 249) !important;
        border-bottom: 1px solid rgb(203, 213, 225) !important;
    }

    .dark .fi-page .fi-ta-header-cell {
        background: rgb(30, 41, 59) !important;
        border-bottom-color: rgb(71, 85, 105) !important;
    }

    .fi-page .fi-ta-header-cell-label {
        font-size: 0.685rem !important;
        font-weight: 750 !important;
        line-height: 1.2 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.035em !important;
        color: rgb(71, 85, 105) !important;
    }

    .dark .fi-page .fi-ta-header-cell-label {
        color: rgb(203, 213, 225) !important;
    }

    .fi-page .fi-ta-cell {
        padding-block: 0 !important;
        padding-inline: 0 !important;
        border-bottom: 1px solid rgb(226, 232, 240) !important;
        vertical-align: middle !important;
    }

    .dark .fi-page .fi-ta-cell {
        border-bottom-color: rgb(51, 65, 85) !important;
    }

    .fi-page .fi-ta-text {
        min-height: 1.85rem !important;
        padding: 0.28rem 0.6rem !important;
    }

    .fi-page .fi-ta-text-item {
        font-size: 0.775rem !important;
        line-height: 1.25 !important;
    }

    .fi-page .fi-ta-cell-index {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        color: rgb(148, 163, 184) !important;
        text-align: center !important;
        width: 2.25rem !important;
        min-width: 2.25rem !important;
        max-width: 2.25rem !important;
    }

    .dark .fi-page .fi-ta-cell-index {
        color: rgb(100, 116, 139) !important;
    }

    .fi-page .fi-ta-cell-index .fi-ta-text-item {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        font-weight: 700 !important;
    }

    .fi-page .fi-ta-image {
        padding: 0.18rem 0.6rem !important;
    }

    .fi-page .fi-ta-image img {
        height: 1.75rem !important;
        width: 1.75rem !important;
    }

    .fi-page .fi-ta-actions {
        padding: 0.2rem 0.5rem !important;
        gap: 0.25rem !important;
    }

    .fi-page .fi-ta-actions .fi-btn,
    .fi-page .fi-ta-actions .fi-icon-btn {
        min-height: 1.7rem !important;
        height: 1.7rem !important;
        padding: 0.2rem 0.45rem !important;
        font-size: 0.72rem !important;
        border-radius: 0.35rem !important;
    }

    .fi-page .fi-ta-table tbody tr:nth-child(even),
    .fi-page .fi-ta table tbody tr:nth-child(even) {
        background: var(--management-row-even) !important;
    }

    .fi-page .fi-ta-table tbody tr:hover,
    .fi-page .fi-ta table tbody tr:hover {
        background: var(--management-row-hover) !important;
    }

    .fi-page .fi-ta .fi-badge {
        padding: 0.1rem 0.4rem !important;
        font-size: 0.685rem !important;
        font-weight: 700 !important;
        line-height: 1.15 !important;
        border-radius: 9999px !important;
    }

    .fi-page .fi-pagination {
        padding: 0.4rem 0.75rem !important;
        border-top: 1px solid rgb(226, 232, 240) !important;
    }

    .dark .fi-page .fi-pagination {
        border-top-color: rgb(51, 65, 85) !important;
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

    :is(.branch-page, .departments-page, .designations-page) .fi-ta-header-toolbar {
        padding: 0.8rem 1.25rem !important;
        gap: 0.75rem !important;
    }

    :is(.branch-page, .departments-page, .designations-page) .fi-ta-header-cell {
        padding: 0.65rem 0.9rem !important;
    }

    :is(.branch-page, .departments-page, .designations-page) .fi-ta-text {
        padding-inline: 0.9rem !important;
    }

    :is(.branch-page, .departments-page, .designations-page) .fi-ta-cell-index {
        min-width: 5rem !important;
        width: 5rem !important;
        max-width: 5rem !important;
    }

    :is(.branch-page, .departments-page, .designations-page) .fi-ta table thead tr > :first-child,
    :is(.branch-page, .departments-page, .designations-page) .fi-ta table tbody tr > :first-child {
        padding-inline-start: 1.25rem !important;
    }

    :is(.branch-page, .departments-page, .designations-page) .fi-ta table thead tr > :last-child,
    :is(.branch-page, .departments-page, .designations-page) .fi-ta table tbody tr > :last-child {
        padding-inline-end: 1.25rem !important;
    }

    :is(.branch-page, .departments-page, .designations-page) .fi-pagination {
        padding: 0.75rem 1.25rem !important;
    }

    @media (min-width: 640px) {
        :is(.branch-page, .departments-page, .designations-page) .fi-ta-header-toolbar,
        :is(.branch-page, .departments-page, .designations-page) .fi-pagination {
            padding-inline: 1.5rem !important;
        }

        :is(.branch-page, .departments-page, .designations-page) .fi-ta table thead tr > :first-child,
        :is(.branch-page, .departments-page, .designations-page) .fi-ta table tbody tr > :first-child {
            padding-inline-start: 1.5rem !important;
        }

        :is(.branch-page, .departments-page, .designations-page) .fi-ta table thead tr > :last-child,
        :is(.branch-page, .departments-page, .designations-page) .fi-ta table tbody tr > :last-child {
            padding-inline-end: 1.5rem !important;
        }
    }
</style>
