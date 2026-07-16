<style data-employee-blue-table-pages>
    .employee-blue-page {
        --employee-blue: #2563eb;
        --employee-blue-strong: #1d4ed8;
        --employee-blue-soft: #eff6ff;
        --employee-blue-row: rgba(239, 246, 255, .7);
        --employee-border: rgba(37, 99, 235, .2);
        --employee-surface: #ffffff;
        --employee-text: #0f172a;
        --employee-muted: #64748b;

        color: var(--employee-text);
        display: grid;
        gap: 1rem;
        min-width: 0;
    }

    .dark .employee-blue-page {
        --employee-blue: #60a5fa;
        --employee-blue-strong: #93c5fd;
        --employee-blue-soft: rgba(30, 64, 175, .28);
        --employee-blue-row: rgba(30, 64, 175, .12);
        --employee-border: rgba(96, 165, 250, .26);
        --employee-surface: #0f172a;
        --employee-text: #f8fafc;
        --employee-muted: #94a3b8;
    }

    .employee-blue-page .fi-ta,
    .employee-blue-page .fi-ta-ctn {
        max-width: 100%;
        min-width: 0;
    }

    .employee-blue-page .fi-ta-ctn {
        background: var(--employee-surface);
        border: 1px solid var(--employee-border);
        border-top: 3px solid var(--employee-blue);
        border-radius: 8px;
        box-shadow: none;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .employee-blue-page .fi-ta-header {
        background: var(--employee-blue-soft);
        border-bottom-color: var(--employee-border);
    }

    .employee-blue-page .fi-ta-header-heading,
    .employee-blue-page .fi-ta-header-description {
        color: var(--employee-text);
    }

    .employee-blue-page .fi-ta-header-cell {
        background: var(--employee-blue-soft);
        border-color: var(--employee-border);
        color: var(--employee-blue-strong);
    }

    .employee-blue-page .fi-ta table tbody tr:nth-child(even) {
        background: var(--employee-blue-row) !important;
    }

    .employee-blue-page .fi-ta table tbody tr:hover {
        background: var(--employee-blue-soft) !important;
    }

    .employee-blue-page .fi-ta-pagination,
    .employee-blue-page .fi-ta-filter-indicators {
        border-color: var(--employee-border);
    }

    .employee-blue-page .fi-tabs {
        background: var(--employee-surface);
        border: 1px solid var(--employee-border);
        border-top: 3px solid var(--employee-blue);
        border-radius: 8px;
        box-shadow: none;
        gap: .25rem;
        padding: .45rem;
    }

    .employee-blue-page .fi-tabs-item {
        border-radius: 6px;
        color: var(--employee-muted);
        min-height: 2.35rem;
    }

    .employee-blue-page .fi-tabs-item:hover,
    .employee-blue-page .fi-tabs-item:focus-visible {
        background: var(--employee-blue-soft);
        color: var(--employee-blue-strong);
    }

    .employee-blue-page .fi-tabs-item.fi-active {
        background: var(--employee-blue);
        color: #ffffff;
    }

    .employee-blue-page .fi-tabs-item.fi-active :is(svg, span) {
        color: #ffffff;
    }

    @media (max-width: 640px) {
        .employee-blue-page {
            gap: .75rem;
        }

        .employee-blue-page .fi-tabs {
            overflow-x: auto;
        }
    }
</style>
