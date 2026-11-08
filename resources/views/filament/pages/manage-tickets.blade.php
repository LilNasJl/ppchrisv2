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

        /* Thematic Stat Cards Strip */
        .ticket-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.15rem;
        }

        @media (max-width: 1024px) {
            .ticket-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .ticket-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .ticket-stat-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.45rem;
            padding: 0.85rem 1.05rem;
            border-radius: 0.7rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
            transition: transform 140ms ease, box-shadow 140ms ease;
        }

        .ticket-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .dark .ticket-stat-card {
            border-color: #1e293b;
            background: #000000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        .dark .ticket-stat-card:hover {
            border-color: #334155;
            background: #050811;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.6);
        }

        .ticket-stat-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 3.5px;
        }

        .ticket-stat-card.theme-blue::before {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        }
        .ticket-stat-card.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }
        .ticket-stat-card.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        .ticket-stat-card.theme-purple::before {
            background: linear-gradient(180deg, #a855f7 0%, #7c3aed 100%);
        }

        .ticket-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .ticket-stat-label {
            font-size: 0.68rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgb(100, 116, 139);
        }

        .dark .ticket-stat-label {
            color: #94a3b8;
        }

        .ticket-stat-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.45rem;
            flex-shrink: 0;
        }

        .theme-blue .ticket-stat-icon-wrapper {
            background: rgb(239, 246, 255);
            color: #2563eb;
        }
        .dark .theme-blue .ticket-stat-icon-wrapper {
            background: rgba(37, 99, 235, 0.18);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }

        .theme-amber .ticket-stat-icon-wrapper {
            background: rgb(254, 243, 199);
            color: #d97706;
        }
        .dark .theme-amber .ticket-stat-icon-wrapper {
            background: rgba(245, 158, 11, 0.18);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .theme-emerald .ticket-stat-icon-wrapper {
            background: rgb(236, 253, 245);
            color: #059669;
        }
        .dark .theme-emerald .ticket-stat-icon-wrapper {
            background: rgba(16, 185, 129, 0.18);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .theme-purple .ticket-stat-icon-wrapper {
            background: rgb(250, 245, 255);
            color: #7c3aed;
        }
        .dark .theme-purple .ticket-stat-icon-wrapper {
            background: rgba(168, 85, 247, 0.18);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.25);
        }

        .ticket-stat-value {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1.2;
            color: rgb(15, 23, 42);
        }

        .dark .ticket-stat-value {
            color: #ffffff;
        }

        .ticket-stat-meta {
            font-size: 0.72rem;
            color: rgb(100, 116, 139);
            margin-top: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .dark .ticket-stat-meta {
            color: #94a3b8;
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

    @php
        $metrics = $this->getTicketMetrics();
    @endphp

    <div class="ticket-workspace">
        <header class="ticket-header" aria-labelledby="ticket-title">
            <div class="ticket-header-copy">
                <p class="ticket-eyebrow">EMPLOYEE REQUESTS</p>
                <h1 class="ticket-title" id="ticket-title">Manage Tickets</h1>
                <p class="ticket-subtitle">Review employee requests, update progress, and share a response.</p>
            </div>
        </header>

        <div class="ticket-stats-grid">
            {{-- Card 1: Total Tickets --}}
            <div class="ticket-stat-card theme-blue">
                <div class="ticket-stat-header">
                    <span class="ticket-stat-label">Total Tickets</span>
                    <div class="ticket-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-ticket" class="w-4 h-4" />
                    </div>
                </div>
                <div>
                    <div class="ticket-stat-value">{{ number_format($metrics['total_tickets']) }}</div>
                    <div class="notice-stat-meta">
                        <x-filament::icon icon="heroicon-m-check-circle" class="w-3.5 h-3.5 text-blue-500" />
                        <span>All employee inquiries</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Pending Action --}}
            <div class="ticket-stat-card theme-amber">
                <div class="ticket-stat-header">
                    <span class="ticket-stat-label">Pending Action</span>
                    <div class="ticket-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4" />
                    </div>
                </div>
                <div>
                    <div class="ticket-stat-value">{{ number_format($metrics['pending_tickets']) }}</div>
                    <div class="notice-stat-meta">
                        <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-3.5 h-3.5 text-amber-500" />
                        <span>Awaiting HR response</span>
                    </div>
                </div>
            </div>

            {{-- Card 3: Resolved / Done --}}
            <div class="ticket-stat-card theme-emerald">
                <div class="ticket-stat-header">
                    <span class="ticket-stat-label">Resolved Tickets</span>
                    <div class="ticket-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-check-badge" class="w-4 h-4" />
                    </div>
                </div>
                <div>
                    <div class="ticket-stat-value">{{ number_format($metrics['done_tickets']) }}</div>
                    <div class="notice-stat-meta">
                        <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5 text-emerald-500" />
                        <span>Completed requests</span>
                    </div>
                </div>
            </div>

            {{-- Card 4: Requesting Staff --}}
            <div class="ticket-stat-card theme-purple">
                <div class="ticket-stat-header">
                    <span class="ticket-stat-label">Requesting Staff</span>
                    <div class="ticket-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-users" class="w-4 h-4" />
                    </div>
                </div>
                <div>
                    <div class="ticket-stat-value">{{ number_format($metrics['requesting_employees']) }} Staff</div>
                    <div class="notice-stat-meta">
                        <x-filament::icon icon="heroicon-m-user-group" class="w-3.5 h-3.5 text-purple-500" />
                        <span>Employees submitted</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-table-wrap">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
