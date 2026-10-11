@include('filament.pages.partials.compact-management-table-styles')

<style>
    .fi-page-header-main-ctn > header.fi-header,
    .fi-breadcrumbs {
        display: none !important;
    }

    .memo-workspace {
        display: grid;
        gap: 0.85rem;
        min-width: 0;
    }

    .memo-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem 1.5rem;
        padding: 1.1rem 1.5rem;
        border: 1px solid #3b82f6;
        border-radius: 0.75rem;
        background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
        color: #fff;
    }

    .memo-header-copy {
        display: grid;
        gap: 0.25rem;
        min-width: 0;
    }

    .memo-eyebrow {
        margin: 0;
        color: #d9f722;
        font-size: 0.7rem;
        font-weight: 800;
    }

    .memo-title {
        margin: 0;
        color: #fff;
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .memo-subtitle {
        margin: 0;
        color: #e0ecff;
        font-size: 0.88rem;
        line-height: 1.35;
    }

    .memo-header-actions,
    .memo-header-actions > div {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    .memo-header-actions .fi-btn,
    .memo-return {
        display: inline-flex;
        min-height: 2.25rem;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.4rem 0.75rem;
        border-radius: 0.45rem;
        font-size: 0.76rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .memo-return {
        border: 1px solid rgb(147 197 253 / 0.4);
        background: rgb(10 31 68 / 0.6);
        color: #fff;
        text-decoration: none;
    }

    .memo-return:hover {
        border-color: rgb(191 219 254 / 0.65);
        background: rgb(10 31 68 / 0.85);
        color: #fff;
    }

    .memo-return svg {
        width: 1rem;
        height: 1rem;
    }

    .memo-content {
        min-width: 0;
    }

    .memo-content .fi-ta-header-cell {
        padding: 0.45rem 0.65rem !important;
    }

    .dark .memo-content .fi-ta-header-cell {
        background: #1d355a !important;
    }

    .dark .memo-content .fi-ta-header-cell-label {
        color: #bfdbfe !important;
    }

    .memo-content .fi-ta-text {
        min-height: 1.9rem !important;
        padding: 0.32rem 0.65rem !important;
    }

    .memo-form-page .memo-content form,
    .memo-form-page .memo-content .fi-sc-section {
        max-width: 52rem;
    }

    .memo-form-page .memo-content .fi-sc {
        gap: 0.75rem;
    }

    @media (max-width: 640px) {
        .memo-header {
            align-items: stretch;
            padding: 1rem;
        }

        .memo-title {
            font-size: 1.3rem;
        }

        .memo-header-actions,
        .memo-header-actions > div {
            justify-content: flex-start;
        }
    }
</style>
