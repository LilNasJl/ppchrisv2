<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header,
        .fi-breadcrumbs {
            display: none !important;
        }

        .roles-workspace {
            display: grid;
            gap: 1.25rem;
            width: 100%;
        }

        .roles-header {
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

        .roles-header-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .roles-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .roles-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .roles-subtitle {
            margin: 0;
            color: #bfdbfe;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .roles-header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .roles-table-wrap {
            min-width: 0;
            width: 100%;
        }

        .roles-table-wrap .fi-ta,
        .roles-table-wrap .fi-ta-ctn {
            min-width: 0;
            width: 100%;
            border-radius: 0.75rem !important;
            overflow: hidden !important;
        }

        .roles-table-wrap .fi-ta-header-toolbar {
            padding: 0.75rem 1rem !important;
        }

        .roles-table-wrap .fi-ta-header-cell {
            padding: 0.6rem 0.85rem !important;
            font-size: 0.7rem !important;
            letter-spacing: 0.04em !important;
            text-transform: uppercase !important;
        }

        .roles-table-wrap .fi-ta-cell {
            padding-block: 0.3rem !important;
        }

        .roles-table-wrap .fi-ta-text {
            min-height: 2.1rem !important;
            padding: 0.35rem 0.75rem !important;
        }

        .roles-table-wrap .fi-ta-actions {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0.45rem !important;
            padding: 0.35rem 0.85rem !important;
            white-space: nowrap !important;
        }

        .roles-table-wrap .fi-ta-actions .fi-btn,
        .roles-table-wrap .fi-ta-actions .fi-icon-btn {
            min-height: 1.85rem !important;
            padding: 0.25rem 0.65rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            border-radius: 0.4rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }

        .roles-table-wrap .fi-ta-actions .fi-icon-btn {
            width: 2rem !important;
            height: 2rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0.25rem !important;
        }

        @media (max-width: 640px) {
            .roles-header {
                padding: 1rem;
            }
            .roles-title {
                font-size: 1.35rem;
            }
        }
    </style>

    <div class="roles-workspace">
        <header class="roles-header" aria-labelledby="roles-title">
            <div class="roles-header-copy">
                <p class="roles-eyebrow">SETTINGS</p>
                <h1 class="roles-title" id="roles-title">Shield Roles</h1>
                <p class="roles-subtitle">Define user roles, manage authorization scopes, and configure system permissions.</p>
            </div>

            <div class="roles-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <div class="roles-table-wrap">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
