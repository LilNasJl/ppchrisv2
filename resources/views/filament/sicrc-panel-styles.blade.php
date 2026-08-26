<style>
    .fi-panel-sicrc .fi-main,
    .fi-panel-sicrc .fi-main-content,
    .fi-panel-sicrc .fi-page,
    .fi-panel-sicrc .fi-page-content,
    .fi-panel-sicrc .fi-page-content > *,
    .fi-panel-sicrc .fi-wi,
    .fi-panel-sicrc .fi-wi-widget,
    .fi-panel-sicrc .fi-ta,
    .fi-panel-sicrc .fi-ta-ctn {
        width: 100% !important;
        max-width: none !important;
        min-width: 0;
    }

    .fi-panel-sicrc .fi-ta {
        border-color: rgb(191 219 254 / 0.8);
        box-shadow: 0 10px 24px rgb(15 71 148 / 0.08);
    }

    .fi-panel-sicrc .fi-ta-header-cell {
        background: rgb(239 246 255);
        color: rgb(11 31 69);
    }

    .dark .fi-panel-sicrc .fi-ta-header-cell {
        background: rgb(30 64 175 / 0.22);
        color: rgb(219 234 254);
    }

    .fi-panel-sicrc .fi-ta-row:nth-child(even) {
        background: rgb(239 246 255 / 0.42);
    }

    .dark .fi-panel-sicrc .fi-ta-row:nth-child(even) {
        background: rgb(30 64 175 / 0.08);
    }

    .fi-panel-sicrc .fi-ta-content,
    .fi-panel-sicrc .fi-ta-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .fi-panel-sicrc .fi-ta-table {
        width: max-content;
        min-width: 100%;
    }

    .fi-panel-sicrc .fi-ta-cell,
    .fi-panel-sicrc .fi-ta-header-cell {
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .fi-panel-sicrc .fi-main {
            padding-inline: 0.75rem;
        }
    }
</style>
