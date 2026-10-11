<x-filament-panels::page>
    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .on-field-dtr-page {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            width: 100%;
        }

        .on-field-dtr-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.25rem 1.5rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #fff;
        }

        .on-field-dtr-header-copy {
            display: flex;
            flex: 1 1 20rem;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }

        .on-field-dtr-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .on-field-dtr-title {
            margin: 0;
            color: #fff;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .on-field-dtr-description {
            margin: 0;
            color: #e0ecff;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .on-field-dtr-actions,
        .on-field-dtr-actions .fi-ac {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.65rem;
        }

        .on-field-dtr-actions .fi-btn {
            min-height: 2.2rem;
            border-radius: 0.45rem;
            white-space: nowrap;
        }

        .on-field-dtr-content .fi-ta,
        .on-field-dtr-content .fi-ta-ctn {
            min-width: 0;
            overflow: hidden;
            border: 1px solid rgb(51, 65, 85);
            border-radius: 0.7rem;
            background: rgb(15, 23, 42);
            box-shadow: none;
        }

        .on-field-dtr-content .fi-ta-header {
            gap: 0.65rem;
            padding: 0.8rem 1rem !important;
            border-bottom: 1px solid rgb(51, 65, 85);
            background: rgb(30, 41, 59);
        }

        .on-field-dtr-content .fi-ta-header-heading {
            margin: 0;
            color: #f8fafc;
            font-size: 0.95rem;
            font-weight: 750;
        }

        .on-field-dtr-content .fi-ta-header-description {
            margin-top: 0.2rem;
            color: rgb(148, 163, 184) !important;
            font-size: 0.78rem;
        }

        .on-field-dtr-content .fi-ta-header-toolbar {
            gap: 0.65rem;
            padding: 0.65rem 1rem;
            border-bottom: 1px solid rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .on-field-dtr-content .fi-ta-content-ctn {
            overflow-x: auto;
            background: rgb(15, 23, 42);
        }

        .on-field-dtr-content .fi-ta table {
            width: 100%;
            min-width: 1120px;
            font-size: 0.79rem;
            line-height: 1.35;
        }

        .on-field-dtr-content .fi-ta-header-cell {
            padding: 0.55rem 0.65rem !important;
            border-bottom: 1px solid rgb(71, 85, 105) !important;
            background: rgb(30, 41, 59) !important;
            color: rgb(203, 213, 225) !important;
            font-size: 0.69rem !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .on-field-dtr-content .fi-ta-cell {
            padding: 0.55rem 0.65rem !important;
            border-bottom: 1px solid rgb(51, 65, 85) !important;
            background: transparent !important;
            color: rgb(226, 232, 240);
            vertical-align: middle;
        }

        .on-field-dtr-content .fi-ta table tbody tr:nth-child(even) {
            background: rgba(30, 41, 59, 0.45);
        }

        .on-field-dtr-content .fi-ta table tbody tr:hover {
            background: rgba(51, 65, 85, 0.55);
        }

        .on-field-dtr-content .fi-ta .fi-badge {
            font-size: 0.7rem;
            font-weight: 700;
        }

        @media (max-width: 640px) {
            .on-field-dtr-header {
                align-items: stretch;
                padding: 1rem;
            }

            .on-field-dtr-title {
                font-size: 1.35rem;
            }

            .on-field-dtr-actions,
            .on-field-dtr-actions .fi-ac {
                justify-content: flex-start;
                width: 100%;
            }
        }
    </style>

    <div class="on-field-dtr-page">
        <header class="on-field-dtr-header">
            <div class="on-field-dtr-header-copy">
                <p class="on-field-dtr-eyebrow">WORKFORCE RECORDS</p>
                <h1 class="on-field-dtr-title">On Field DTR</h1>
                <p class="on-field-dtr-description">Submit attendance records for HR review and track their status.</p>
            </div>

            <div class="on-field-dtr-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <section class="on-field-dtr-content">
            {{ $this->content }}
        </section>
    </div>
</x-filament-panels::page>
