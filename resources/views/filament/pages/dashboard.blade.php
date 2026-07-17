<x-filament-panels::page>
    <style>
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) {
            --dashboard-blue: #2563eb;
            --dashboard-blue-strong: #1d4ed8;
            --dashboard-blue-soft: #eff6ff;
            --dashboard-border: rgba(37, 99, 235, 0.22);
            --dashboard-surface: #ffffff;
            --dashboard-muted: #64748b;
        }

        .dark .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) {
            --dashboard-blue: #60a5fa;
            --dashboard-blue-strong: #93c5fd;
            --dashboard-blue-soft: rgba(30, 64, 175, 0.24);
            --dashboard-border: rgba(96, 165, 250, 0.28);
            --dashboard-surface: #111827;
            --dashboard-muted: #94a3b8;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-heading {
            align-items: center;
            display: flex;
            gap: 0.75rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-heading::before {
            background: var(--dashboard-blue);
            border-radius: 999px;
            content: '';
            display: block;
            height: 2rem;
            width: 0.3rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-widgets > .fi-grid {
            align-items: stretch;
            gap: 1rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview .fi-section-content {
            gap: 0.85rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat {
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 8px;
            min-height: 7.5rem;
            overflow: hidden;
            padding: 0 !important;
            position: relative;
            transition: border-color 160ms ease, transform 160ms ease;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat::before {
            background: var(--dashboard-blue);
            content: '';
            inset: 0 auto 0 0;
            position: absolute;
            width: 0.25rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat:hover {
            border-color: var(--dashboard-blue);
            transform: translateY(-1px);
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-content {
            justify-content: center;
            padding: 1rem 1.15rem 1rem 1.35rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-label {
            color: var(--dashboard-muted);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-value {
            font-size: 2rem;
            line-height: 1.1;
            margin-top: 0.45rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) [data-dashboard-birthday] {
            background: var(--dashboard-blue-soft);
            border-color: var(--dashboard-blue);
            min-height: 8rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) [data-dashboard-birthday]::before {
            width: 0.4rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) [data-dashboard-birthday] .fi-wi-stats-overview-stat-value {
            color: var(--dashboard-blue-strong);
            font-size: clamp(1.35rem, 2vw, 2rem);
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) [data-dashboard-birthday] .fi-icon {
            color: var(--dashboard-blue);
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart {
            min-width: 0;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart > .fi-section {
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 8px;
            height: 100%;
            overflow: hidden;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header {
            background: var(--dashboard-blue-soft);
            border-bottom: 1px solid var(--dashboard-border);
            min-height: 3.5rem;
            padding: 0.85rem 1rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-heading {
            color: var(--dashboard-blue-strong);
            font-size: 0.95rem;
            font-weight: 800;
        }

        .ppc-dashboard-summary-heading {
            align-items: center;
            display: inline-flex;
            gap: 0.55rem;
        }

        .ppc-dashboard-summary-icon {
            color: var(--dashboard-blue);
            flex: 0 0 auto;
            height: 1.15rem;
            width: 1.15rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-content {
            padding: 1rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart-canvas-ctn {
            margin-inline: auto;
            max-width: 100%;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-attrition-card {
            border-color: var(--dashboard-border) !important;
            border-top: 4px solid var(--dashboard-blue) !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-attrition-title {
            color: var(--dashboard-blue-strong) !important;
        }

        .ppc-hr-dashboard-marker {
            display: none;
        }

        @media (max-width: 767px) {
            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-widgets > .fi-grid {
                gap: 0.8rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat {
                min-height: 6.75rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-content {
                padding: 0.85rem 0.8rem 0.85rem 1rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-value {
                font-size: 1.5rem;
            }
        }
    </style>

    <span class="ppc-hr-dashboard-marker" aria-hidden="true"></span>
</x-filament-panels::page>
