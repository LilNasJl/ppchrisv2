<x-filament-panels::page>
    <style>
        .fi-page-header-main-ctn > header.fi-header,
        .fi-breadcrumbs {
            display: none !important;
        }

        .on-field-dtr-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        /* ── Simplified Hero Header ───────────────────────────────────── */
        .on-field-dtr-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            padding: 0.95rem 1.25rem;
            border: 1px solid rgba(59, 130, 246, 0.6);
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8 0%, #173c70 65%, #102a50 100%);
            box-shadow: 0 4px 16px -2px rgba(2, 6, 23, 0.4);
            color: #ffffff;
            box-sizing: border-box;
            width: 100%;
            max-width: 100%;
        }

        .on-field-dtr-header-copy {
            display: flex;
            flex: 1 1 12rem;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }

        .on-field-dtr-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .on-field-dtr-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.015em;
            line-height: 1.2;
        }

        .on-field-dtr-actions,
        .on-field-dtr-actions .fi-ac {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .on-field-dtr-actions .fi-btn {
            min-height: 2.15rem;
            border-radius: 0.45rem;
            white-space: nowrap;
            font-size: 0.79rem !important;
            font-weight: 600 !important;
        }

        /* ── Tabs & Filter Bar ────────────────────────────────────────── */
        .on-field-dtr-content {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .on-field-dtr-content .fi-tabs {
            border: 1px solid rgb(51, 65, 85) !important;
            background: rgb(15, 23, 42) !important;
            border-radius: 0.6rem !important;
            padding: 0.25rem !important;
            gap: 0.25rem !important;
            margin-bottom: 0.65rem !important;
            width: fit-content;
            max-width: 100%;
            overflow-x: auto;
            scrollbar-width: thin;
            box-sizing: border-box;
        }

        .on-field-dtr-content .fi-tabs-item {
            border-radius: 0.45rem !important;
            font-size: 0.775rem !important;
            font-weight: 600 !important;
            padding: 0.3rem 0.75rem !important;
            min-height: 2rem !important;
            color: rgb(203, 213, 225) !important;
            transition: all 120ms ease;
        }

        .on-field-dtr-content .fi-tabs-item:hover {
            background: rgb(30, 41, 59) !important;
            color: #ffffff !important;
        }

        .on-field-dtr-content .fi-tabs-item.fi-active {
            background: #2563eb !important;
            color: #ffffff !important;
        }

        .on-field-dtr-content .fi-tabs-item.fi-active :is(span, svg) {
            color: #ffffff !important;
        }

        /* ── Table Toolbar & Period Select ───────────────────────────── */
        .dtr-period-select-wrap {
            width: 270px;
            min-width: 200px;
            max-width: 100%;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            box-sizing: border-box;
        }

        .dtr-select-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
        }

        .dtr-select-icon {
            position: absolute;
            left: 0.65rem;
            width: 0.95rem;
            height: 0.95rem;
            color: rgb(148, 163, 184);
            pointer-events: none;
            z-index: 2;
        }

        .dtr-select-chevron {
            position: absolute;
            right: 0.65rem;
            width: 0.85rem;
            height: 0.85rem;
            color: rgb(148, 163, 184);
            pointer-events: none;
            z-index: 2;
        }

        .dtr-native-select {
            width: 100%;
            height: 2.1rem;
            min-height: 2.1rem;
            box-sizing: border-box;
            padding: 0.25rem 1.85rem 0.25rem 2rem;
            border-radius: 0.45rem;
            border: 1px solid rgb(71, 85, 105);
            background-color: rgb(30, 41, 59);
            color: #ffffff;
            font-size: 0.775rem;
            font-weight: 600;
            line-height: 1.4;
            transition: all 120ms ease;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            box-shadow: none;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .dtr-native-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25);
        }

        .dtr-native-select option {
            background-color: rgb(30, 41, 59);
            color: #ffffff;
        }

        /* ── Table Styling ────────────────────────────────────────────── */
        .on-field-dtr-content .fi-ta,
        .on-field-dtr-content .fi-ta-ctn {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: hidden;
            border: 1px solid rgb(51, 65, 85);
            border-radius: 0.7rem;
            background: rgb(15, 23, 42);
            box-shadow: none;
            box-sizing: border-box;
        }

        .on-field-dtr-content .fi-ta-header {
            gap: 0.65rem;
            padding: 0.75rem 1rem !important;
            border-bottom: 1px solid rgb(51, 65, 85);
            background: rgb(30, 41, 59);
            box-sizing: border-box;
        }

        .on-field-dtr-content .fi-ta-header-toolbar {
            gap: 0.65rem;
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid rgb(51, 65, 85);
            background: rgb(15, 23, 42);
            box-sizing: border-box;
        }

        .on-field-dtr-content .fi-ta-content-ctn {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: rgb(15, 23, 42);
            box-sizing: border-box;
        }

        .on-field-dtr-content .fi-ta table {
            width: 100%;
            min-width: 580px;
            font-size: 0.8rem;
            line-height: 1.35;
            border-collapse: collapse;
        }

        .on-field-dtr-content .fi-ta-header-cell {
            padding: 0.5rem 0.65rem !important;
            border-bottom: 1px solid rgb(71, 85, 105) !important;
            background: rgb(30, 41, 59) !important;
            color: rgb(203, 213, 225) !important;
            font-size: 0.7rem !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .on-field-dtr-content .fi-ta-cell {
            padding: 0.55rem 0.65rem !important;
            border-bottom: 1px solid rgb(51, 65, 85) !important;
            background: transparent !important;
            color: rgb(226, 232, 240);
            vertical-align: middle;
        }

        .on-field-dtr-content .fi-ta-cell-index {
            width: 2.25rem !important;
            min-width: 2.25rem !important;
            max-width: 2.25rem !important;
            text-align: center !important;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            color: rgb(148, 163, 184);
        }

        .on-field-dtr-content .fi-ta table tbody tr:nth-child(even) {
            background: rgba(30, 41, 59, 0.45);
        }

        .on-field-dtr-content .fi-ta table tbody tr:hover {
            background: rgba(51, 65, 85, 0.55);
        }

        .on-field-dtr-content .fi-ta-text.fi-ta-text-has-descriptions {
            row-gap: 0.15rem !important;
        }

        .on-field-dtr-content .fi-ta-text-item {
            font-size: 0.79rem !important;
            line-height: 1.3 !important;
        }

        .on-field-dtr-content .fi-ta-text-description {
            margin-top: 0.1rem !important;
            color: rgb(148, 163, 184) !important;
            font-size: 0.72rem !important;
            line-height: 1.25 !important;
        }

        .on-field-dtr-content .fi-badge {
            font-size: 0.7rem;
            font-weight: 700;
        }

        /* ── Modal Window & Transparency Fix ────────────────────────── */
        .fi-modal-window,
        .dark .fi-modal-window {
            background-color: #0f172a !important;
        }

        .fi-modal-header,
        .fi-modal-content,
        .fi-modal-footer {
            background-color: #0f172a !important;
        }

        /* ── Mobile Responsive Adjustments ───────────────────────────── */
        @media (max-width: 640px) {
            .on-field-dtr-header {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                padding: 0.85rem 0.95rem !important;
                gap: 0.75rem !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .on-field-dtr-header-copy {
                width: 100% !important;
                flex: 0 0 auto !important;
            }

            .on-field-dtr-title {
                font-size: 1.25rem !important;
            }

            .on-field-dtr-actions {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            .on-field-dtr-actions .fi-ac {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                width: 100% !important;
                max-width: 100% !important;
                gap: 0.5rem !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            .on-field-dtr-actions .fi-btn {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                min-height: 2.35rem !important;
                padding: 0.4rem 0.5rem !important;
                font-size: 0.775rem !important;
                font-weight: 600 !important;
                border-radius: 0.5rem !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                box-sizing: border-box !important;
                gap: 0.35rem !important;
                margin: 0 !important;
            }

            .on-field-dtr-actions .fi-btn svg.fi-icon {
                flex-shrink: 0 !important;
                width: 1.05rem !important;
                height: 1.05rem !important;
                margin: 0 !important;
            }

            .dtr-period-select-wrap {
                width: 100%;
                max-width: 100%;
            }

            .on-field-dtr-content .fi-ta table {
                min-width: 520px;
            }

            /* ── Modal Full Width & Transparency Fix for Mobile View ─── */
            .fi-modal-window-ctn {
                padding: 0 !important;
                display: flex !important;
                align-items: flex-end !important;
                justify-content: center !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .fi-modal-window {
                width: 100vw !important;
                max-width: 100vw !important;
                min-width: 100vw !important;
                margin: 0 !important;
                border-radius: 1rem 1rem 0 0 !important;
                border: 1px solid rgb(51, 65, 85) !important;
                border-bottom: 0 !important;
                background-color: #0f172a !important;
                box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.75) !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                max-height: 92dvh !important;
                overflow: hidden !important;
            }

            .fi-modal-header {
                flex-shrink: 0 !important;
                background-color: #0f172a !important;
                padding: 0.85rem 1rem 0.5rem !important;
                border-bottom: 1px solid rgb(51, 65, 85) !important;
            }

            .fi-modal-content {
                flex: 1 1 auto !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                background-color: #0f172a !important;
                padding: 0.65rem 0.85rem 0.75rem !important;
                gap: 0.75rem !important;
            }

            .fi-modal-footer {
                flex-shrink: 0 !important;
                background-color: #0f172a !important;
                padding: 0.65rem 0.85rem !important;
                border-top: 1px solid rgb(51, 65, 85) !important;
            }

            .fi-modal-footer-actions {
                width: 100% !important;
                display: flex !important;
                gap: 0.5rem !important;
            }

            .fi-modal-footer-actions .fi-btn {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                justify-content: center !important;
            }

            /* Repeater inside modal takes full available width */
            .fi-modal-content .fi-fo-repeater {
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .fi-modal-content .fi-fo-repeater-item {
                padding: 0.5rem 0.65rem !important;
                border-radius: 0.5rem !important;
                margin-bottom: 0.5rem !important;
                background-color: rgb(30, 41, 59) !important;
                border: 1px solid rgb(51, 65, 85) !important;
                box-sizing: border-box !important;
            }

            .fi-modal-content .fi-fo-repeater-item-header {
                padding: 0.35rem 0.5rem !important;
            }

            .fi-modal-content .fi-fo-repeater-item-content {
                padding: 0.35rem 0 0 !important;
                gap: 0.45rem !important;
            }
        }
    </style>

    <div class="on-field-dtr-page">
        {{-- Simplified Hero Header --}}
        <header class="on-field-dtr-header">
            <div class="on-field-dtr-header-copy">
                <p class="on-field-dtr-eyebrow">WORKFORCE RECORDS</p>
                <h1 class="on-field-dtr-title">On Field DTR</h1>
            </div>

            <div class="on-field-dtr-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        {{-- Main Table & Tabs Section --}}
        <section class="on-field-dtr-content">
            {{ $this->content }}
        </section>
    </div>
</x-filament-panels::page>
