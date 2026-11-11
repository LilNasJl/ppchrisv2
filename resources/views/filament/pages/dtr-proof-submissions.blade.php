<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header,
        .fi-breadcrumbs {
            display: none !important;
        }

        .ofd-list-workspace {
            display: grid;
            gap: 1rem;
            width: 100%;
            min-width: 0;
        }

        .ofd-list-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.15rem 1.4rem;
            border: 1px solid rgba(59, 130, 246, 0.55);
            border-radius: 0.85rem;
            background: linear-gradient(115deg, #104294 0%, #173668 58%, #0f1f3d 100%);
            box-shadow: 0 10px 28px rgba(2, 6, 23, 0.3);
            color: #ffffff;
        }

        .ofd-list-header-copy {
            display: flex;
            flex: 1 1 320px;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }

        .ofd-list-eyebrow {
            margin: 0;
            color: #fde047;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .ofd-list-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.015em;
            line-height: 1.2;
        }

        .ofd-list-subtitle {
            margin: 0;
            color: #dbeafe;
            font-size: 0.86rem;
            line-height: 1.4;
        }

        .ofd-list-header-actions {
            flex: 0 0 auto;
        }

        .ofd-list-header-actions .fi-btn {
            min-height: 2.25rem;
            border-color: rgba(147, 197, 253, 0.4) !important;
            background: rgba(10, 31, 68, 0.55) !important;
            color: #ffffff !important;
        }

        .ofd-list-header-actions .fi-btn:hover {
            border-color: rgba(191, 219, 254, 0.7) !important;
            background: rgba(10, 31, 68, 0.85) !important;
            color: #ffffff !important;
        }

        .fi-panel-hr .ofd-list-workspace .fi-tabs {
            justify-content: flex-start !important;
            margin-inline: 0 !important;
            width: fit-content;
            max-width: 100%;
            padding: 0.28rem !important;
            gap: 0.25rem !important;
            border-radius: 0.6rem !important;
        }

        .dark .fi-panel-hr .ofd-list-workspace .fi-tabs {
            background: #070b12 !important;
            border-color: #1e293b !important;
        }

        .fi-panel-hr .ofd-list-workspace .fi-tabs-item {
            min-height: 2.05rem !important;
            padding: 0.32rem 0.72rem !important;
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            border-radius: 0.45rem !important;
        }

        .ofd-list-table-wrap {
            min-width: 0;
            width: 100%;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-sc.fi-sc-has-gap {
            row-gap: 0.85rem !important;
            gap: 0.85rem !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-ctn {
            border-radius: 0.75rem !important;
            border-top: 2px solid #2563eb !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta table {
            min-width: 100% !important;
            width: 100% !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-header {
            padding: 0.8rem 1rem !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-header-heading {
            color: #0f172a !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            line-height: 1.3 !important;
        }

        .dark .fi-panel-hr .ofd-list-table-wrap .fi-ta-header-heading {
            color: #f8fafc !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-header-description {
            color: #64748b !important;
            font-size: 0.78rem !important;
            line-height: 1.35 !important;
        }

        .dark .fi-panel-hr .ofd-list-table-wrap .fi-ta-header-description {
            color: #94a3b8 !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-header-toolbar {
            padding: 0.5rem 1rem !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-header-cell {
            min-width: 0 !important;
            padding: 0.56rem 0.65rem !important;
        }

        .fi-panel-hr .ofd-list-table-wrap :is(.fi-ta-header-cell-index, .fi-ta-cell-index) {
            width: 2.5rem !important;
            min-width: 2.5rem !important;
            max-width: 2.5rem !important;
            padding-inline: 0.25rem !important;
            text-align: center !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-cell-index .fi-ta-text {
            width: 100%;
            justify-content: center !important;
            padding-inline: 0.25rem !important;
            text-align: center !important;
        }

        .fi-panel-hr .ofd-list-table-wrap :is(.fi-ta-header-cell-employee-name-snapshot, .fi-ta-cell-employee-name-snapshot) {
            max-width: 14rem;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-text {
            min-height: 2.5rem !important;
            padding: 0.5rem 0.65rem !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-text.fi-ta-text-has-descriptions {
            row-gap: 0.16rem !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-text-item {
            font-size: 0.79rem !important;
            line-height: 1.28 !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-text-description {
            margin-top: 0 !important;
            color: #64748b !important;
            font-size: 0.71rem !important;
            line-height: 1.28 !important;
        }

        .dark .fi-panel-hr .ofd-list-table-wrap .fi-ta-text-description {
            color: #94a3b8 !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-cell .fi-ta-actions {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            flex-wrap: nowrap !important;
            gap: 0.35rem !important;
            padding: 0.45rem 0.85rem 0.45rem 0.5rem !important;
            white-space: nowrap !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-cell .fi-ta-actions .fi-btn {
            min-height: 1.75rem !important;
            height: 1.75rem !important;
            padding: 0.2rem 0.55rem !important;
            font-size: 0.73rem !important;
            font-weight: 600 !important;
            border-radius: 0.4rem !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-ta-cell .fi-ta-actions .fi-icon-btn {
            min-height: 1.75rem !important;
            height: 1.75rem !important;
            width: 1.75rem !important;
            padding: 0 !important;
            border-radius: 0.4rem !important;
        }

        .fi-panel-hr .ofd-list-table-wrap .fi-pagination {
            padding: 0.65rem 1rem !important;
        }

        @media (max-width: 960px) {
            .fi-panel-hr .ofd-list-table-wrap .fi-ta table {
                min-width: 820px !important;
            }
        }

        @media (max-width: 640px) {
            .ofd-list-header {
                padding: 1rem;
            }

            .ofd-list-title {
                font-size: 1.3rem;
            }
        }
    </style>

    <div class="ofd-list-workspace">
        <header class="ofd-list-header" aria-labelledby="ofd-list-title">
            <div class="ofd-list-header-copy">
                <p class="ofd-list-eyebrow">Workforce Records</p>
                <h1 class="ofd-list-title" id="ofd-list-title">On Field DTR</h1>
                <p class="ofd-list-subtitle">Review, edit, approve, or reject employee on-field attendance submissions.</p>
            </div>

            <div class="ofd-list-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <div class="ofd-list-table-wrap">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
