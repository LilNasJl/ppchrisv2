<style>
    .fi-body[data-panel-id="kpi"] {
        --kpi-blue-50: #eff6ff;
        --kpi-blue-100: #dbeafe;
        --kpi-blue-700: #1d4ed8;
        --kpi-navy: #0b1f45;
    }

    .fi-body[data-panel-id="kpi"] .fi-ta {
        border-color: rgb(191 219 254 / .8);
        box-shadow: 0 12px 28px rgb(15 71 148 / .08);
    }

    .fi-body[data-panel-id="kpi"] .fi-ta-header-cell {
        background: var(--kpi-blue-50);
        color: var(--kpi-navy);
    }

    .dark .fi-body[data-panel-id="kpi"] .fi-ta-header-cell {
        background: rgb(30 64 175 / .22);
        color: #dbeafe;
    }

    .fi-body[data-panel-id="kpi"] .fi-ta-row:nth-child(even) {
        background: rgb(239 246 255 / .42);
    }

    .dark .fi-body[data-panel-id="kpi"] .fi-ta-row:nth-child(even) {
        background: rgb(30 64 175 / .08);
    }
</style>
