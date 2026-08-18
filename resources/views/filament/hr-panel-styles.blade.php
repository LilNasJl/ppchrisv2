<style data-hr-panel-design-system>
    .fi-panel-hr {
        --hr-blue: #2563eb;
        --hr-blue-strong: #1d4ed8;
        --hr-blue-soft: #eff6ff;
        --hr-blue-row: rgba(239, 246, 255, .72);
        --hr-border: rgba(37, 99, 235, .2);
        --hr-surface: #ffffff;
        --hr-surface-muted: #f8fafc;
        --hr-text: #0f172a;
        --hr-muted: #64748b;
    }

    .dark .fi-panel-hr {
        --hr-blue: #60a5fa;
        --hr-blue-strong: #93c5fd;
        --hr-blue-soft: rgba(30, 64, 175, .26);
        --hr-blue-row: rgba(30, 64, 175, .12);
        --hr-border: rgba(96, 165, 250, .26);
        --hr-surface: #0f172a;
        --hr-surface-muted: #111827;
        --hr-text: #f8fafc;
        --hr-muted: #94a3b8;
    }

    .fi-panel-hr .fi-page,
    .fi-panel-hr .fi-main,
    .fi-panel-hr .fi-main-ctn,
    .fi-panel-hr .fi-main-content {
        color: var(--hr-text);
        max-width: none;
        min-width: 0;
        width: 100%;
    }

    .fi-panel-hr .fi-main,
    .fi-panel-hr .fi-main-ctn,
    .fi-panel-hr .fi-main-content {
        flex: 1 1 auto;
    }

    .fi-panel-hr .fi-page-header-heading {
        color: var(--hr-text);
        letter-spacing: 0;
    }

    .fi-panel-hr .fi-topbar {
        border-bottom-color: var(--hr-border);
        box-shadow: none;
    }

    .fi-panel-hr .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: var(--hr-blue-soft);
        color: var(--hr-blue-strong);
    }

    .fi-panel-hr .fi-sidebar-item.fi-active :is(.fi-sidebar-item-label, .fi-icon) {
        color: var(--hr-blue-strong);
    }

    .fi-panel-hr .fi-btn {
        border-radius: 6px;
        box-shadow: none;
    }

    .fi-panel-hr .fi-btn.fi-color-primary {
        font-weight: 700;
    }

    .fi-panel-hr :is(
        .fi-sc-section,
        .fi-wi-stats-overview-stat,
        .fi-wi-chart,
        .fi-modal-window,
        .fi-dropdown-panel
    ) {
        border-color: var(--hr-border);
        border-radius: 8px;
        box-shadow: none;
    }

    .fi-panel-hr .fi-sc-section-header {
        background: var(--hr-blue-soft);
        border-bottom-color: var(--hr-border);
    }

    .fi-panel-hr .fi-wi-stats-overview-stat-value {
        color: var(--hr-blue-strong);
    }

    .fi-panel-hr .fi-sc-fieldset {
        border-color: var(--hr-border);
        border-radius: 8px;
    }

    .fi-panel-hr .fi-sc-fieldset-legend {
        color: var(--hr-blue-strong);
    }

    .fi-panel-hr .fi-tabs {
        background: var(--hr-surface);
        border: 1px solid var(--hr-border);
        border-radius: 8px;
        box-shadow: none;
        gap: .25rem;
        max-width: 100%;
        overflow-x: auto;
        padding: .4rem;
    }

    .fi-panel-hr .fi-tabs-item {
        border-radius: 6px;
        color: var(--hr-muted);
        min-height: 2.35rem;
    }

    .fi-panel-hr .fi-tabs-item:hover,
    .fi-panel-hr .fi-tabs-item:focus-visible {
        background: var(--hr-blue-soft);
        color: var(--hr-blue-strong);
    }

    .fi-panel-hr .fi-tabs-item.fi-active {
        background: var(--hr-blue);
        color: #ffffff;
    }

    .fi-panel-hr .fi-tabs-item.fi-active :is(span, svg) {
        color: #ffffff;
    }

    .fi-panel-hr .fi-ta,
    .fi-panel-hr .fi-ta-ctn {
        max-width: 100%;
        min-width: 0;
    }

    .fi-panel-hr .fi-ta-ctn {
        background: var(--hr-surface);
        border: 1px solid var(--hr-border);
        border-top: 3px solid var(--hr-blue);
        border-radius: 8px;
        box-shadow: none;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .fi-panel-hr .fi-ta table {
        min-width: 100%;
        width: max-content;
    }

    .fi-panel-hr .fi-ta-header {
        background: var(--hr-blue-soft);
        border-bottom-color: var(--hr-border);
    }

    .fi-panel-hr .fi-ta-header-heading,
    .fi-panel-hr .fi-ta-header-description {
        color: var(--hr-text);
    }

    .fi-panel-hr .fi-ta-header-cell {
        background: var(--hr-blue-soft);
        border-color: var(--hr-border);
        color: var(--hr-blue-strong);
        min-width: 5rem;
        padding: .4rem .55rem !important;
        white-space: nowrap;
    }

    .fi-panel-hr .fi-ta-header-cell-label {
        font-size: .72rem;
        line-height: 1.15;
    }

    .fi-panel-hr .fi-ta-text {
        min-height: 2.1rem;
        padding: .32rem .55rem !important;
    }

    .fi-panel-hr .fi-ta-text-item {
        font-size: .78rem !important;
        line-height: 1.2 !important;
    }

    .fi-panel-hr .fi-ta-image {
        padding-block: .28rem !important;
    }

    .fi-panel-hr .fi-ta-image img {
        height: 2rem !important;
        width: 2rem !important;
    }

    .fi-panel-hr .fi-ta-actions {
        padding-block: .25rem !important;
    }

    .fi-panel-hr .fi-ta table tbody tr:nth-child(even) {
        background: var(--hr-blue-row) !important;
    }

    .fi-panel-hr .fi-ta table tbody tr:hover {
        background: var(--hr-blue-soft) !important;
    }

    .fi-panel-hr :is(.fi-ta-pagination, .fi-ta-filter-indicators) {
        border-color: var(--hr-border);
    }

    .fi-panel-hr :is(.fi-input-wrp, .fi-select-input) {
        border-radius: 6px;
    }

    .fi-panel-hr .fi-input-wrp:focus-within {
        outline-color: var(--hr-blue);
    }

    @media (max-width: 640px) {
        .fi-panel-hr .fi-page-header-main-ctn {
            align-items: stretch;
        }

        .fi-panel-hr .fi-page-header-actions {
            width: 100%;
        }

        .fi-panel-hr .fi-page-header-actions .fi-btn {
            justify-content: center;
        }

        .fi-panel-hr .fi-ta-header-cell {
            min-width: 6.5rem;
        }
    }
</style>
