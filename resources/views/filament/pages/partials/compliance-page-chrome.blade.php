<style>
    .fi-page-header-main-ctn > header.fi-header,
    .fi-breadcrumbs {
        display: none !important;
    }

    .cb-workspace { display: flex; flex-direction: column; gap: 0.75rem; width: 100%; }

    .cb-topbar {
        position: relative;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem 1.5rem;
        padding: 1.1rem 1.5rem;
        border-radius: 0.75rem;
        border: 1px solid #3b82f6;
        background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
        color: #fff;
        overflow: hidden;
    }
    .cb-topbar::before {
        content: none;
    }
    .dark .cb-topbar {
        border-color: #3b82f6;
        background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
    }
    .cb-copy { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
    .cb-eyebrow { margin: 0; color: #d9f722; font-size: 0.7rem; font-weight: 800; line-height: 1.2; }
    .cb-title { margin: 0; color: #fff; font-size: 1.65rem; font-weight: 800; line-height: 1.2; }
    .dark .cb-title { color: #fff; }
    .cb-subtitle { color: #e0ecff; font-size: 0.9rem; line-height: 1.4; }
    .dark .cb-subtitle { color: #e0ecff; }
    .cb-actions { display: flex; flex-wrap: nowrap; align-items: center; gap: 0.45rem; }
    .cb-topbar .cb-btn.outline { background: rgb(10 31 68 / 0.6); color: #fff; border-color: rgb(147 197 253 / 0.35); }
    .cb-topbar .cb-btn.outline:hover { background: rgb(10 31 68 / 0.85); border-color: rgb(191 219 254 / 0.65); color: #fff; }

    .cb-btn {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.38rem 0.7rem; border-radius: 0.45rem;
        font-size: 0.75rem; font-weight: 600; line-height: 1.2;
        text-decoration: none; white-space: nowrap; cursor: pointer;
        border: 1px solid transparent; background: #2563eb; color: #fff;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.18);
        transition: all 140ms ease;
    }
    .cb-btn:hover { background: #1d4ed8; transform: translateY(-1px); }
    .cb-btn.outline { background: #fff; color: rgb(51, 65, 85); border-color: rgb(203, 213, 225); box-shadow: none; }
    .cb-btn.outline:hover { background: rgb(248, 250, 252); border-color: rgb(148, 163, 184); color: rgb(15, 23, 42); }
    .dark .cb-btn.outline { background: rgb(30, 41, 59); color: rgb(226, 232, 240); border-color: rgb(71, 85, 105); }
    .dark .cb-btn.outline:hover { background: rgb(51, 65, 85); color: #fff; }
    .cb-btn.success { background: #059669; box-shadow: 0 1px 2px rgba(5, 150, 105, 0.2); }
    .cb-btn.success:hover { background: #047857; }
    .cb-btn-icon { width: 0.875rem; height: 0.875rem; flex-shrink: 0; }

    /* Tabs */
    .cb-tabs {
        display: flex; gap: 0.3rem; padding: 0.3rem;
        border-radius: 0.6rem; border: 1px solid rgb(226, 232, 240);
        background: rgb(241, 245, 249); width: fit-content; max-width: 100%; overflow-x: auto;
    }
    .dark .cb-tabs { border-color: rgb(51, 65, 85); background: rgb(30, 41, 59); }
    .cb-tab {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.35rem 0.8rem; border-radius: 0.45rem; border: 0;
        font-size: 0.76rem; font-weight: 700; cursor: pointer; white-space: nowrap;
        background: transparent; color: rgb(71, 85, 105); transition: all 120ms ease;
    }
    .dark .cb-tab { color: rgb(203, 213, 225); }
    .cb-tab:hover { background: rgba(255, 255, 255, 0.7); }
    .dark .cb-tab:hover { background: rgba(51, 65, 85, 0.7); }
    .cb-tab.active { background: #2563eb; color: #fff; box-shadow: 0 1px 2px rgba(37, 99, 235, 0.3); }

    /* Employee chip */
    .cb-chip {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.15rem 0.55rem; border-radius: 9999px;
        font-size: 0.7rem; font-weight: 700;
        background: rgb(219, 234, 254); color: rgb(30, 64, 175); border: 1px solid rgb(147, 197, 253);
    }
    .cb-topbar .cb-chip { background: rgb(10 31 68 / 0.38); color: #dbeafe; border-color: rgb(191 219 254 / 0.4); }
    .cb-topbar .cb-chip.violet { background: rgb(76 29 149 / 0.35); color: #ede9fe; border-color: rgb(196 181 253 / 0.45); }
    .dark .cb-chip { background: rgba(59, 130, 246, 0.22); color: rgb(191, 219, 254); border-color: rgba(96, 165, 250, 0.45); }
    .cb-chip.violet { background: rgb(237, 233, 254); color: rgb(91, 33, 182); border-color: rgb(196, 181, 253); }
    .dark .cb-chip.violet { background: rgba(139, 92, 246, 0.22); color: rgb(221, 214, 254); border-color: rgba(167, 139, 250, 0.45); }
    .cb-meta-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-top: 0.2rem; }

    @media (max-width: 640px) {
        .cb-topbar { padding: 1rem; }
        .cb-title { font-size: 1.35rem; }
        .cb-actions { width: 100%; flex-wrap: wrap; }
    }

    /* Compact widget tables */
    .cb-workspace .fi-wi-table, .cb-workspace .fi-ta { margin: 0 !important; }
    .cb-workspace .fi-ta-header-heading { font-size: 0.8rem !important; font-weight: 800 !important; }
    .cb-workspace .fi-ta-header { padding: 0.5rem 0.85rem !important; }
    .cb-workspace .fi-ta-header-ctn .fi-btn { padding: 0.3rem 0.65rem !important; font-size: 0.72rem !important; }

    /* Compact form (employee deduction page) */
    .cb-form .fi-fo-field-label-content, .cb-form .fi-fo-field-wrp-label { font-size: 0.7rem !important; font-weight: 700 !important; text-transform: uppercase; letter-spacing: 0.03em; }
    .cb-form .fi-input-wrp, .cb-form .fi-select-input { font-size: 0.78rem !important; }
    .cb-form input.fi-input { padding-block: 0.3rem !important; font-size: 0.78rem !important; }
    .cb-form .fi-sc-fieldset, .cb-form fieldset {
        padding: 0.65rem 0.85rem 0.8rem !important;
        border-radius: 0.65rem !important;
        border: 1px solid rgb(226, 232, 240) !important;
        background: #fff !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    }
    .dark .cb-form .fi-sc-fieldset, .dark .cb-form fieldset { border-color: rgb(51, 65, 85) !important; background: rgb(15, 23, 42) !important; }
    .cb-form legend { font-size: 0.72rem !important; font-weight: 800 !important; text-transform: uppercase; letter-spacing: 0.04em; padding-inline: 0.4rem !important; color: rgb(37, 99, 235); }
    .dark .cb-form legend { color: rgb(147, 197, 253); }
    .cb-form .fi-sc { gap: 0.35rem !important; }

    .cb-form .fi-fo-checkbox-list-options {
        width: 100%;
        max-width: 48rem;
        gap: 0.4rem !important;
    }
    .cb-form .fi-fo-checkbox-list-option {
        min-height: 2.25rem;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.55rem;
        border: 1px solid rgb(203, 213, 225);
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
    }
    .dark .cb-form .fi-fo-checkbox-list-option {
        border-color: rgb(71, 85, 105);
        background: rgb(24, 24, 27);
    }
    .cb-form .fi-fo-checkbox-list-option:has(input:checked) {
        border-color: #3b82f6;
        background: rgb(37 99 235 / 0.18);
    }
    .cb-form .fi-fo-checkbox-list-option:focus-within { outline: 2px solid #60a5fa; outline-offset: 2px; }
    .cb-form .fi-fo-checkbox-list-option .fi-checkbox-input { margin-top: 0; }
    .cb-form .fi-fo-checkbox-list-option-label { font-size: 0.75rem; line-height: 1.2; }

    /* Each deduction keeps its amount and term together in a compact block. */
    .cb-form .fi-sc-fieldset, .cb-form fieldset {
        padding: 0.35rem 0 0.45rem !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none;
    }
    .dark .cb-form .fi-sc-fieldset, .dark .cb-form fieldset {
        background: transparent !important;
        border: 0 !important;
    }
    .cb-form legend { padding-inline: 0 !important; margin-bottom: 0.35rem; letter-spacing: 0; }
    .cb-form .cb-row {
        align-self: start;
        padding: 0.5rem 0.6rem;
        border: 1px solid rgb(226, 232, 240);
        border-radius: 8px;
        background: rgb(255, 255, 255);
        min-width: 0;
    }
    .dark .cb-form .cb-row { border-color: rgb(55, 65, 81); background: rgb(24, 24, 27); }
    .cb-form .cb-row .fi-grid, .cb-form .cb-row .fi-sc {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 0.35rem 0.5rem !important;
        align-items: start;
    }
    .cb-form .cb-row .fi-grid > *, .cb-form .cb-row .fi-sc > * { grid-column: auto !important; min-width: 0; }
    .cb-form .cb-row .fi-grid > :first-child, .cb-form .cb-row .fi-sc > :first-child { grid-column: 1 / -1 !important; }
    .cb-form .cb-row-title { font-size: 0.8rem; font-weight: 800; color: rgb(15, 23, 42); line-height: 1.2; }
    .dark .cb-form .cb-row-title { color: #fff; }
    .cb-form .cb-row-desc { font-size: 0.68rem; color: rgb(100, 116, 139); line-height: 1.25; margin-top: 0.1rem; }
    .dark .cb-form .cb-row-desc { color: rgb(148, 163, 184); }
    .cb-form .cb-row .fi-fo-field-label-content { font-size: 0.62rem !important; color: rgb(100, 116, 139); }
    .cb-form .cb-row .fi-input-wrp { min-height: 0; }
    .cb-form .cb-row input.fi-input, .cb-form .cb-row select.fi-select-input { min-height: 2rem; padding-block: 0.22rem !important; }
    .cb-form .cb-row .fi-fo-field-wrp { gap: 0.1rem !important; }
    .cb-form .cb-row .fi-fo-field-wrp-label { letter-spacing: 0; }

    @media (min-width: 1280px) {
        .cb-form .cb-row .fi-grid, .cb-form .cb-row .fi-sc {
            grid-template-columns: minmax(5.5rem, 0.7fr) repeat(2, minmax(0, 1fr)) !important;
            align-items: center;
        }
        .cb-form .cb-row .fi-grid > :first-child, .cb-form .cb-row .fi-sc > :first-child {
            grid-column: 1 !important;
            grid-row: 1 / span 2;
        }
        .cb-form .cb-row .fi-grid > :nth-child(2), .cb-form .cb-row .fi-sc > :nth-child(2) { grid-column: 2; grid-row: 1; }
        .cb-form .cb-row .fi-grid > :nth-child(3), .cb-form .cb-row .fi-sc > :nth-child(3) { grid-column: 3; grid-row: 1; }
        .cb-form .cb-row .fi-grid > :nth-child(4), .cb-form .cb-row .fi-sc > :nth-child(4) { grid-column: 2; grid-row: 2; }
        .cb-form .cb-row .fi-grid > :nth-child(5), .cb-form .cb-row .fi-sc > :nth-child(5) { grid-column: 3; grid-row: 2; }
    }

    @media (max-width: 640px) {
        .cb-form .fi-sc-fieldset, .cb-form fieldset { margin: 0 !important; }
        .cb-form .cb-row { padding: 0.45rem; }
        .cb-form .cb-row .fi-grid, .cb-form .cb-row .fi-sc { gap: 0.3rem !important; }
    }
</style>
