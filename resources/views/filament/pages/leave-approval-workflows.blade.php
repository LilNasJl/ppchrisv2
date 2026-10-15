<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header,
        .fi-breadcrumbs {
            display: none !important;
        }

        .workflows-workspace {
            display: grid;
            gap: 1.25rem;
            width: 100%;
        }

        .workflows-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.25rem 1.5rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #ffffff;
            box-shadow: 0 4px 14px -2px rgba(18, 87, 184, 0.25);
        }

        .workflows-header-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .workflows-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .workflows-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .workflows-subtitle {
            margin: 0;
            color: #bfdbfe;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .workflows-header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .workflows-table-wrap {
            min-width: 0;
            width: 100%;
        }

        .workflows-table-wrap .fi-ta,
        .workflows-table-wrap .fi-ta-ctn {
            min-width: 0;
            width: 100%;
            border-radius: 0.75rem !important;
            overflow: hidden !important;
        }

        .workflows-table-wrap .fi-ta-header-toolbar {
            padding: 0.75rem 1rem !important;
        }

        .workflows-table-wrap .fi-ta-header-cell {
            padding: 0.6rem 0.85rem !important;
            font-size: 0.7rem !important;
            letter-spacing: 0.04em !important;
            text-transform: uppercase !important;
        }

        .workflows-table-wrap .fi-ta-cell {
            padding-block: 0.3rem !important;
        }

        .workflows-table-wrap .fi-ta-text {
            min-height: 2.1rem !important;
            padding: 0.35rem 0.75rem !important;
        }

        .workflows-table-wrap .fi-ta-actions {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0.45rem !important;
            padding: 0.35rem 0.85rem !important;
            white-space: nowrap !important;
        }

        .workflows-table-wrap .fi-ta-actions .fi-btn,
        .workflows-table-wrap .fi-ta-actions .fi-icon-btn {
            min-height: 1.85rem !important;
            padding: 0.25rem 0.65rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            border-radius: 0.4rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }

        .workflows-table-wrap .fi-ta-actions .fi-icon-btn {
            width: 2rem !important;
            height: 2rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0.25rem !important;
        }

        @media (max-width: 640px) {
            .workflows-header {
                padding: 1rem;
            }
            .workflows-title {
                font-size: 1.35rem;
            }
        }
    </style>

    <div class="workflows-workspace">
        <header class="workflows-header" aria-labelledby="workflows-title">
            <div class="workflows-header-copy">
                <p class="workflows-eyebrow">SETTINGS</p>
                <h1 class="workflows-title" id="workflows-title">Approval Workflows</h1>
                <p class="workflows-subtitle">Configure approval chains, multilevel routing rules, scope criteria, and approver assignments.</p>
            </div>

            <div class="workflows-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <div class="workflows-table-wrap">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
