<x-filament-widgets::widget>
    @php
        $metrics = $this->getMetrics();
    @endphp

    <style>
        /* Compact Page Layout for Payroll Periods */
        .fi-page {
            gap: 0.55rem !important;
        }

        .fi-page-header-main-ctn {
            padding-block: 0.25rem !important;
            margin-bottom: 0 !important;
        }

        .fi-header {
            gap: 0.5rem 1rem !important;
            padding-block: 0.15rem !important;
        }

        .fi-header-heading {
            font-size: 1.15rem !important;
            line-height: 1.3 !important;
            font-weight: 800 !important;
            letter-spacing: -0.015em !important;
        }

        .fi-header-subheading {
            font-size: 0.76rem !important;
            line-height: 1.25 !important;
            margin-top: 0.1rem !important;
        }

        .fi-header-actions-ctn .fi-btn {
            padding: 0.35rem 0.68rem !important;
            font-size: 0.75rem !important;
            min-height: 2rem !important;
            border-radius: 0.45rem !important;
        }

        .fi-wi {
            gap: 0.5rem !important;
        }

        /* Minimized Compact Stat Cards */
        .pp-compact-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.5rem;
            width: 100%;
        }

        @media (max-width: 1024px) {
            .pp-compact-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .pp-compact-grid {
                grid-template-columns: 1fr;
            }
        }

        .pp-stat-card {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.45rem 0.75rem;
            border-radius: 0.55rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            min-width: 0;
        }

        .dark .pp-stat-card {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .pp-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.45rem;
            flex-shrink: 0;
        }

        .pp-stat-icon svg {
            width: 1.05rem;
            height: 1.05rem;
        }

        .pp-stat-icon.primary {
            background: rgba(37, 99, 235, 0.1);
            color: rgb(37, 99, 235);
        }

        .dark .pp-stat-icon.primary {
            background: rgba(59, 130, 246, 0.18);
            color: rgb(96, 165, 250);
        }

        .pp-stat-icon.success {
            background: rgba(22, 163, 74, 0.1);
            color: rgb(22, 163, 74);
        }

        .dark .pp-stat-icon.success {
            background: rgba(34, 197, 94, 0.18);
            color: rgb(74, 222, 128);
        }

        .pp-stat-icon.slate {
            background: rgba(100, 116, 139, 0.12);
            color: rgb(71, 85, 105);
        }

        .dark .pp-stat-icon.slate {
            background: rgba(148, 163, 184, 0.16);
            color: rgb(203, 213, 225);
        }

        .pp-stat-icon.info {
            background: rgba(2, 132, 199, 0.1);
            color: rgb(2, 132, 199);
        }

        .dark .pp-stat-icon.info {
            background: rgba(14, 165, 233, 0.18);
            color: rgb(56, 189, 248);
        }

        .pp-stat-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1 1 auto;
        }

        .pp-stat-top {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.4rem;
        }

        .pp-stat-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(100, 116, 139);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .pp-stat-label {
            color: rgb(148, 163, 184);
        }

        .pp-stat-value {
            font-size: 0.88rem;
            font-weight: 800;
            line-height: 1.2;
            color: rgb(15, 23, 42);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .pp-stat-value {
            color: #ffffff;
        }

        .pp-stat-sub {
            font-size: 0.68rem;
            line-height: 1.15;
            color: rgb(100, 116, 139);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .pp-stat-sub {
            color: rgb(148, 163, 184);
        }

        /* Compact Tabs & Table */
        .fi-page .fi-sc-tabs,
        .fi-page .fi-tabs {
            padding: 0.2rem 0.3rem !important;
            gap: 0.25rem !important;
            margin-bottom: 0 !important;
        }

        .fi-page .fi-tabs-item {
            padding: 0.28rem 0.6rem !important;
            font-size: 0.74rem !important;
            border-radius: 0.4rem !important;
        }

        .fi-page .fi-ta-header-toolbar {
            padding: 0.4rem 0.75rem !important;
            gap: 0.5rem !important;
        }

        .fi-page .fi-ta-header-cell {
            padding: 0.38rem 0.6rem !important;
            white-space: nowrap;
        }

        .fi-page .fi-ta-header-cell-label {
            font-size: 0.68rem !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .fi-page .fi-ta-cell {
            padding-block: 0 !important;
        }

        .fi-page .fi-ta-text {
            padding: 0.3rem 0.6rem !important;
            min-height: 1.95rem !important;
        }

        .fi-page .fi-ta-text-item {
            font-size: 0.78rem !important;
            line-height: 1.2 !important;
        }

        .fi-page .fi-ta-actions {
            padding: 0.2rem 0.5rem !important;
        }

        .fi-page .fi-ta-ctn {
            border-radius: 0.6rem !important;
        }
    </style>

    <div class="pp-compact-grid">
        @foreach ($metrics as $metric)
            <div class="pp-stat-card">
                <div class="pp-stat-icon {{ $metric['tone'] }}">
                    <x-filament::icon :icon="$metric['icon']" />
                </div>
                <div class="pp-stat-body">
                    <span class="pp-stat-label">{{ $metric['label'] }}</span>
                    <span class="pp-stat-value" title="{{ $metric['value'] }}">{{ $metric['value'] }}</span>
                    <span class="pp-stat-sub" title="{{ $metric['sub'] }}">{{ $metric['sub'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
