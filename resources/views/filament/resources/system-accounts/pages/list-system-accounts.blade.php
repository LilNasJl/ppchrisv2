<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header,
        .fi-breadcrumbs {
            display: none !important;
        }

        .sys-accounts-workspace {
            display: grid;
            gap: 1.25rem;
            width: 100%;
        }

        .sys-accounts-header {
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

        .sys-accounts-header-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .sys-accounts-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .sys-accounts-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .sys-accounts-subtitle {
            margin: 0;
            color: #bfdbfe;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .sys-accounts-header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sys-accounts-table-wrap {
            min-width: 0;
            width: 100%;
        }

        .sys-accounts-table-wrap .fi-ta,
        .sys-accounts-table-wrap .fi-ta-ctn {
            min-width: 0;
            width: 100%;
        }

        @media (max-width: 640px) {
            .sys-accounts-header {
                padding: 1rem;
            }
            .sys-accounts-title {
                font-size: 1.35rem;
            }
        }
    </style>

    <div class="sys-accounts-workspace">
        <header class="sys-accounts-header" aria-labelledby="sys-accounts-title">
            <div class="sys-accounts-header-copy">
                <p class="sys-accounts-eyebrow">SETTINGS</p>
                <h1 class="sys-accounts-title" id="sys-accounts-title">System Accounts</h1>
                <p class="sys-accounts-subtitle">Manage administrative user accounts, roles, access permissions, and account status.</p>
            </div>

            <div class="sys-accounts-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <div class="sys-accounts-table-wrap">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
