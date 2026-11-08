<x-filament-panels::page class="branch-page">
    @include('filament.pages.partials.system-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "ORGANIZATIONAL SETUP";
        }

        .branch-page .fi-breadcrumbs {
            display: none !important;
        }

        .branch-page .fi-ta-cell {
            padding-block: 0.35rem !important;
        }

        .branch-page .fi-ta-text {
            min-height: 2.3rem !important;
            padding: 0.45rem 0.75rem !important;
        }

        /* Thematic Stat Cards Strip */
        .branch-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.15rem;
        }

        @media (max-width: 1024px) {
            .branch-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .branch-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .branch-stat-card {
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

        .branch-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .dark .branch-stat-card {
            border-color: #1e293b;
            background: #000000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        .dark .branch-stat-card:hover {
            border-color: #334155;
            background: #050811;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.6);
        }

        .branch-stat-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 3.5px;
        }

        .branch-stat-card.theme-blue::before {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        }
        .branch-stat-card.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        .branch-stat-card.theme-purple::before {
            background: linear-gradient(180deg, #a855f7 0%, #7c3aed 100%);
        }
        .branch-stat-card.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }

        .branch-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .branch-stat-label {
            font-size: 0.68rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgb(100, 116, 139);
        }

        .dark .branch-stat-label {
            color: #94a3b8;
        }

        .branch-stat-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.45rem;
            flex-shrink: 0;
        }

        .theme-blue .branch-stat-icon-wrapper {
            background: rgb(239, 246, 255);
            color: #2563eb;
        }
        .dark .theme-blue .branch-stat-icon-wrapper {
            background: rgba(37, 99, 235, 0.18);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }

        .theme-emerald .branch-stat-icon-wrapper {
            background: rgb(236, 253, 245);
            color: #059669;
        }
        .dark .theme-emerald .branch-stat-icon-wrapper {
            background: rgba(16, 185, 129, 0.18);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .theme-purple .branch-stat-icon-wrapper {
            background: rgb(250, 245, 255);
            color: #7c3aed;
        }
        .dark .theme-purple .branch-stat-icon-wrapper {
            background: rgba(168, 85, 247, 0.18);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.25);
        }

        .theme-amber .branch-stat-icon-wrapper {
            background: rgb(254, 243, 199);
            color: #d97706;
        }
        .dark .theme-amber .branch-stat-icon-wrapper {
            background: rgba(245, 158, 11, 0.18);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .branch-stat-value {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1.2;
            color: rgb(15, 23, 42);
        }

        .dark .branch-stat-value {
            color: #ffffff;
        }

        .branch-stat-meta {
            font-size: 0.72rem;
            color: rgb(100, 116, 139);
            margin-top: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .dark .branch-stat-meta {
            color: #94a3b8;
        }

        /* --- Dark Theme Table Styling --- */
        .dark .branch-page .fi-ta,
        .dark .branch-page .fi-ta-ctn {
            background: #000000 !important;
            border-color: #1e293b !important;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5) !important;
        }

        .dark .branch-page .fi-ta table,
        .dark .branch-page .fi-ta table thead,
        .dark .branch-page .fi-ta table tbody {
            background: #000000 !important;
        }

        .dark .branch-page .fi-ta-header,
        .dark .branch-page .fi-ta-header-toolbar {
            background: #070b12 !important;
            border-bottom: 1px solid #1e293b !important;
        }

        .dark .branch-page .fi-ta-header-toolbar .fi-input-wrp {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
        }

        .dark .branch-page .fi-ta-header-toolbar .fi-input-wrp:focus-within {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
        }

        .dark .branch-page .fi-ta-header-toolbar input {
            background: transparent !important;
            color: #f8fafc !important;
        }

        .dark .branch-page .fi-ta-header-toolbar input::placeholder {
            color: #64748b !important;
        }

        .dark .branch-page .fi-ta-header-toolbar .fi-icon-btn,
        .dark .branch-page .fi-ta-header-toolbar .fi-btn {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            color: #94a3b8 !important;
        }

        .dark .branch-page .fi-ta-header-toolbar .fi-icon-btn:hover,
        .dark .branch-page .fi-ta-header-toolbar .fi-btn:hover {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        .dark .branch-page .fi-ta-header-cell {
            background: #070b12 !important;
            border-bottom: 1px solid #1e293b !important;
        }

        .dark .branch-page .fi-ta-header-cell-label {
            color: #93c5fd !important;
        }

        .dark .branch-page .fi-ta-header-cell svg,
        .dark .branch-page .fi-ta-header-cell .fi-ta-header-cell-sort-icon {
            color: #60a5fa !important;
        }

        .dark .branch-page .fi-ta table tbody tr {
            background: #000000 !important;
            transition: background-color 120ms ease !important;
        }

        .dark .branch-page .fi-ta table tbody tr:nth-child(even) {
            background: #050811 !important;
        }

        .dark .branch-page .fi-ta table tbody tr:hover {
            background: #0c1424 !important;
        }

        .dark .branch-page .fi-ta-cell {
            border-bottom: 1px solid #161f2e !important;
            background: transparent !important;
        }

        .dark .branch-page .fi-ta-cell-index {
            color: #64748b !important;
        }

        .dark .branch-page .fi-ta-text-item {
            color: #f8fafc !important;
        }

        .dark .branch-page .fi-pagination {
            background: #070b12 !important;
            border-top: 1px solid #1e293b !important;
        }

        .dark .branch-page .fi-pagination .fi-pagination-overview {
            color: #94a3b8 !important;
        }

        .dark .branch-page .fi-pagination .fi-btn,
        .dark .branch-page .fi-pagination .fi-icon-btn {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            color: #cbd5e1 !important;
        }

        .dark .branch-page .fi-pagination .fi-btn:hover,
        .dark .branch-page .fi-pagination .fi-icon-btn:hover {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #ffffff !important;
        }

        .dark .branch-page .fi-pagination .fi-btn.fi-active,
        .dark .branch-page .fi-pagination .fi-active .fi-btn {
            background: #2563eb !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
        }

        .dark .branch-page .fi-pagination .fi-select-input,
        .dark .branch-page .fi-pagination .fi-input-wrp {
            background: #000000 !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
        }

        .dark .branch-page .fi-ta-empty-state {
            background: #000000 !important;
        }
    </style>

    @php
        $metrics = $this->getBranchMetrics();
    @endphp

    <div class="branch-stats-grid">
        {{-- Card 1: Total Branches --}}
        <div class="branch-stat-card theme-blue">
            <div class="branch-stat-header">
                <span class="branch-stat-label">Total Branches</span>
                <div class="branch-stat-icon-wrapper">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="branch-stat-value">{{ number_format($metrics['total_branches']) }}</div>
                <div class="branch-stat-meta">
                    <x-filament::icon icon="heroicon-m-check-circle" class="w-3.5 h-3.5 text-blue-500" />
                    <span>Active stations & locations</span>
                </div>
            </div>
        </div>

        {{-- Card 2: Total Assigned Staff --}}
        <div class="branch-stat-card theme-emerald">
            <div class="branch-stat-header">
                <span class="branch-stat-label">Assigned Headcount</span>
                <div class="branch-stat-icon-wrapper">
                    <x-filament::icon icon="heroicon-o-users" class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="branch-stat-value">{{ number_format($metrics['total_employees']) }} Staff</div>
                <div class="branch-stat-meta">
                    <x-filament::icon icon="heroicon-m-user-group" class="w-3.5 h-3.5 text-emerald-500" />
                    <span>Station & branch personnel</span>
                </div>
            </div>
        </div>

        {{-- Card 3: Top Staffed Branch --}}
        <div class="branch-stat-card theme-purple">
            <div class="branch-stat-header">
                <span class="branch-stat-label">Largest Station</span>
                <div class="branch-stat-icon-wrapper">
                    <x-filament::icon icon="heroicon-o-sparkles" class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="branch-stat-value" style="font-size: 1.15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $metrics['top_branch_name'] }}">
                    {{ $metrics['top_branch_name'] }}
                </div>
                <div class="branch-stat-meta">
                    <x-filament::icon icon="heroicon-m-chart-bar" class="w-3.5 h-3.5 text-purple-500" />
                    <span>{{ number_format($metrics['top_branch_count']) }} employees allocated</span>
                </div>
            </div>
        </div>

        {{-- Card 4: 24-Hour Stations --}}
        <div class="branch-stat-card theme-amber">
            <div class="branch-stat-header">
                <span class="branch-stat-label">24-Hour Stations</span>
                <div class="branch-stat-icon-wrapper">
                    <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="branch-stat-value">{{ number_format($metrics['twenty_four_hours']) }} Stations</div>
                <div class="branch-stat-meta">
                    <x-filament::icon icon="heroicon-m-bolt" class="w-3.5 h-3.5 text-amber-500" />
                    <span>Continuous round-the-clock</span>
                </div>
            </div>
        </div>
    </div>

    @livewire(\App\Filament\Widgets\BranchTable::class, key('branch-table'))
</x-filament-panels::page>
