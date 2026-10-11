<x-filament-panels::page>
    <style>
        /* Completely Hide Page Header (Title, Subheading, Breadcrumbs) */
        .fi-page-header-main-ctn > header.fi-header,
        .fi-header-heading,
        .fi-header-subheading,
        .fi-breadcrumbs {
            display: none !important;
        }

        .fi-page {
            gap: 0.5rem !important;
        }

        .payroll-visibility-topbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.1rem 1.4rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #ffffff;
        }

        .payroll-visibility-topbar-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .payroll-visibility-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .payroll-visibility-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .payroll-visibility-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .payroll-visibility-return {
            display: inline-flex;
            min-height: 2.35rem;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border: 1px solid rgb(147 197 253 / 0.4);
            border-radius: 0.5rem;
            background: rgb(10 31 68 / 0.6);
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: background-color 150ms ease, border-color 150ms ease;
        }

        .payroll-visibility-return:hover {
            border-color: rgb(191 219 254 / 0.65);
            background: rgb(10 31 68 / 0.85);
            color: #ffffff;
        }

        .payroll-visibility-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .payroll-visibility-action {
            display: inline-flex;
            min-height: 2.35rem;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border: 1px solid transparent;
            border-radius: 0.5rem;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
            transition: background-color 150ms ease, border-color 150ms ease;
        }

        .payroll-visibility-action.enable {
            border-color: rgb(167 243 208 / 0.45);
            background: rgb(5 120 80 / 0.88);
        }

        .payroll-visibility-action.enable:hover {
            background: rgb(4 100 68);
        }

        .payroll-visibility-action.disable {
            border-color: rgb(254 202 202 / 0.45);
            background: rgb(185 28 28 / 0.88);
        }

        .payroll-visibility-action.disable:hover {
            background: rgb(153 27 27);
        }

        .payroll-visibility-action:disabled {
            cursor: wait;
            opacity: 0.65;
        }

        .payroll-visibility-action svg {
            width: 1rem;
            height: 1rem;
            flex: none;
        }

        .payroll-visibility-return svg {
            width: 1rem;
            height: 1rem;
            flex: none;
        }

        /* Table Container */
        .fi-page .fi-ta-ctn {
            border-radius: 0.6rem !important;
            border: 1px solid rgb(226, 232, 240) !important;
            background: #ffffff !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
            overflow: hidden !important;
        }

        .dark .fi-page .fi-ta-ctn {
            border-color: rgb(51, 65, 85) !important;
            background: rgb(15, 23, 42) !important;
        }

        /* Table Header Toolbar (Buttons Left + Search & Filters Right) */
        .fi-page .fi-ta-header-toolbar {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            padding: 0.65rem 1rem !important;
            gap: 0.75rem !important;
            border-bottom: 1px solid rgb(226, 232, 240) !important;
        }

        .dark .fi-page .fi-ta-header-toolbar {
            border-bottom-color: rgb(51, 65, 85) !important;
        }

        .fi-page .fi-ta-header-toolbar .fi-ta-actions {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Toolbar Buttons Styling */
        .fi-page .fi-ta-header-toolbar a.fi-btn,
        .fi-page .fi-ta-header-toolbar button.fi-btn {
            padding: 0.42rem 0.8rem !important;
            font-size: 0.78rem !important;
            font-weight: 650 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.15s ease-in-out !important;
        }

        /* Return Button (Gray) with solid background */
        .fi-page .fi-ta-header-toolbar a.fi-btn.fi-color-gray,
        .fi-page .fi-ta-header-toolbar a.fi-btn:not([class*="fi-color-"]) {
            background: rgb(241, 245, 249) !important;
            border: 1px solid rgb(203, 213, 225) !important;
            color: rgb(30, 41, 59) !important;
        }

        .fi-page .fi-ta-header-toolbar a.fi-btn.fi-color-gray:hover,
        .fi-page .fi-ta-header-toolbar a.fi-btn:not([class*="fi-color-"]):hover {
            background: rgb(226, 232, 240) !important;
            border-color: rgb(148, 163, 184) !important;
            color: rgb(15, 23, 42) !important;
        }

        .dark .fi-page .fi-ta-header-toolbar a.fi-btn.fi-color-gray,
        .dark .fi-page .fi-ta-header-toolbar a.fi-btn:not([class*="fi-color-"]) {
            background: rgb(30, 41, 59) !important;
            border-color: rgb(51, 65, 85) !important;
            color: rgb(241, 245, 249) !important;
        }

        .dark .fi-page .fi-ta-header-toolbar a.fi-btn.fi-color-gray:hover,
        .dark .fi-page .fi-ta-header-toolbar a.fi-btn:not([class*="fi-color-"]):hover {
            background: rgb(51, 65, 85) !important;
            border-color: rgb(71, 85, 105) !important;
            color: #ffffff !important;
        }

        /* Hide Column Manager if present */
        .fi-page .fi-ta-col-manager-dropdown {
            display: none !important;
        }

        /* Proper Spacing for Table Column Headers */
        .fi-page .fi-ta-header-cell {
            padding: 0.6rem 0.85rem !important;
            background: rgb(248, 250, 252) !important;
            border-bottom: 1px solid rgb(226, 232, 240) !important;
            white-space: nowrap !important;
        }

        .dark .fi-page .fi-ta-header-cell {
            background: rgb(30, 41, 59) !important;
            border-bottom-color: rgb(51, 65, 85) !important;
        }

        .fi-page .fi-ta-header-cell-label {
            font-size: 0.72rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.035em !important;
            color: rgb(71, 85, 105) !important;
        }

        .dark .fi-page .fi-ta-header-cell-label {
            color: rgb(203, 213, 225) !important;
        }

        /* Compact Table Body Cells */
        .fi-page .fi-ta-cell {
            padding-block: 0 !important;
            padding-inline: 0 !important;
        }

        .fi-page .fi-ta-text {
            padding: 0.38rem 0.85rem !important;
            min-height: 2.05rem !important;
        }

        .fi-page .fi-ta-text-item {
            font-size: 0.78rem !important;
            line-height: 1.25 !important;
            white-space: nowrap !important;
        }

        .fi-page .fi-ta-image {
            padding: 0.25rem 0.85rem !important;
        }

        .fi-page .fi-ta-toggle {
            padding: 0.25rem 0.85rem !important;
        }

        .fi-page .fi-ta-table tbody tr:nth-child(even) {
            background-color: rgba(248, 250, 252, 0.75) !important;
        }

        .dark .fi-page .fi-ta-table tbody tr:nth-child(even) {
            background-color: rgba(30, 41, 59, 0.35) !important;
        }

        .fi-page .fi-ta-table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }

        .dark .fi-page .fi-ta-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        .fi-page .fi-ta-badge {
            padding: 0.12rem 0.45rem !important;
            font-size: 0.7rem !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
        }

        .fi-page .fi-ta-pagination {
            padding: 0.45rem 0.85rem !important;
            border-top: 1px solid rgb(226, 232, 240) !important;
        }

        .dark .fi-page .fi-ta-pagination {
            border-top-color: rgb(51, 65, 85) !important;
        }

        @media (max-width: 640px) {
            .payroll-visibility-topbar {
                align-items: stretch;
                padding: 1rem;
            }

            .payroll-visibility-return {
                align-self: flex-start;
            }

            .payroll-visibility-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <section class="payroll-visibility-topbar" aria-labelledby="payroll-visibility-title">
        <div class="payroll-visibility-topbar-copy">
            <p class="payroll-visibility-eyebrow">PAYROLL ADMINISTRATION</p>
            <h1 class="payroll-visibility-title" id="payroll-visibility-title">Payroll Visibility</h1>
            <p class="payroll-visibility-subtitle">Control employee access to payroll details.</p>
        </div>
        <div class="payroll-visibility-actions">
            <a class="payroll-visibility-return" href="{{ \App\Filament\Pages\Payroll::getUrl() }}">
                <x-filament::icon icon="heroicon-o-arrow-left" />
                <span>Return to Payroll</span>
            </a>
            <button
                class="payroll-visibility-action enable"
                type="button"
                wire:click="enableAllPayrollVisibility"
                wire:confirm="Are you sure you want to make payroll visible to all employees?"
                wire:loading.attr="disabled"
                wire:target="enableAllPayrollVisibility"
            >
                <x-filament::icon icon="heroicon-o-eye" />
                <span>Enable All</span>
            </button>
            <button
                class="payroll-visibility-action disable"
                type="button"
                wire:click="disableAllPayrollVisibility"
                wire:confirm="Are you sure you want to hide payroll from all employees?"
                wire:loading.attr="disabled"
                wire:target="disableAllPayrollVisibility"
            >
                <x-filament::icon icon="heroicon-o-eye-slash" />
                <span>Disable All</span>
            </button>
        </div>
    </section>

    {{ $this->content }}
</x-filament-panels::page>
