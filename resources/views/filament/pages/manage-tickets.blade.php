<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header,
        .fi-breadcrumbs {
            display: none !important;
        }

        .ticket-workspace {
            display: grid;
            gap: 0.85rem;
            min-width: 0;
        }

        .ticket-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.1rem 1.5rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #fff;
        }

        .ticket-header-copy {
            display: grid;
            gap: 0.25rem;
            min-width: 0;
        }

        .ticket-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
        }

        .ticket-title {
            margin: 0;
            color: #fff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .ticket-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .ticket-table-wrap {
            min-width: 0;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
        }

        .ticket-table-wrap .fi-ta,
        .ticket-table-wrap .fi-ta-ctn {
            min-width: 0;
            width: 100%;
        }

        .ticket-table-wrap .fi-ta table {
            min-width: 960px;
        }

        .ticket-table-wrap .fi-ta-header {
            padding: 0.45rem 0.75rem !important;
        }

        .ticket-table-wrap .fi-ta-header-cell {
            padding: 0.4rem 0.55rem !important;
        }

        .ticket-table-wrap .fi-ta-actions {
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .ticket-header {
                padding: 1rem;
            }

            .ticket-title {
                font-size: 1.3rem;
            }
        }
    </style>

    <div class="ticket-workspace">
        <header class="ticket-header" aria-labelledby="ticket-title">
            <div class="ticket-header-copy">
                <p class="ticket-eyebrow">EMPLOYEE REQUESTS</p>
                <h1 class="ticket-title" id="ticket-title">Manage Tickets</h1>
                <p class="ticket-subtitle">Review employee requests, update progress, and share a response.</p>
            </div>
        </header>

        <div class="ticket-table-wrap">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
