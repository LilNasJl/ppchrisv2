<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header,
        .fi-breadcrumbs {
            display: none !important;
        }

        .account-logs-workspace {
            display: grid;
            gap: 1.25rem;
            width: 100%;
        }

        .account-logs-header {
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

        .account-logs-header-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .account-logs-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .account-logs-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .account-logs-subtitle {
            margin: 0;
            color: #bfdbfe;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .account-logs-header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .account-logs-table-wrap {
            min-width: 0;
            width: 100%;
        }

        .account-logs-table-wrap .fi-ta,
        .account-logs-table-wrap .fi-ta-ctn {
            min-width: 0;
            width: 100%;
            border-radius: 0.75rem !important;
            overflow: hidden !important;
        }

        .account-logs-table-wrap .fi-ta-header-toolbar {
            padding: 0.75rem 1rem !important;
        }

        .account-logs-table-wrap .fi-ta-header-cell {
            padding: 0.65rem 0.85rem !important;
            font-size: 0.72rem !important;
            letter-spacing: 0.04em !important;
            text-transform: uppercase !important;
        }

        .account-logs-table-wrap .fi-ta-cell {
            padding-block: 0.35rem !important;
            vertical-align: middle !important;
        }

        .account-logs-table-wrap .fi-ta-text {
            min-height: 2.2rem !important;
            padding: 0.4rem 0.85rem !important;
            display: inline-flex !important;
            align-items: center !important;
        }

        .account-logs-table-wrap .fi-ta-cell-index {
            padding-inline: 0.5rem !important;
        }

        .account-logs-table-wrap .fi-ta-actions {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0.5rem !important;
            padding: 0.35rem 0.85rem !important;
            white-space: nowrap !important;
        }

        .account-logs-table-wrap .fi-ta-actions .fi-btn {
            min-height: 1.85rem !important;
            padding: 0.25rem 0.7rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            border-radius: 0.4rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
        }

        .account-logs-table-wrap .fi-ta-actions .fi-btn svg {
            width: 1rem !important;
            height: 1rem !important;
        }

        .account-logs-table-wrap .fi-pagination {
            padding: 0.6rem 1rem !important;
        }

        @media (max-width: 640px) {
            .account-logs-header {
                padding: 1rem;
            }
            .account-logs-title {
                font-size: 1.35rem;
            }
        }
    </style>

    <div class="account-logs-workspace">
        <header class="account-logs-header" aria-labelledby="account-logs-title">
            <div class="account-logs-header-copy">
                <p class="account-logs-eyebrow">SETTINGS</p>
                <h1 class="account-logs-title" id="account-logs-title">Account Logs</h1>
                <p class="account-logs-subtitle">Track authentication events, user logins, impersonation sessions, and security history.</p>
            </div>

            <div class="account-logs-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <div class="account-logs-table-wrap">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
