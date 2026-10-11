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

        .ppc-dashboard-group-header {
            background: linear-gradient(108deg, #075bc3 0%, #123f7b 54%, #102b55 100%);
            border: 1px solid rgba(96, 165, 250, 0.58);
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 7.5rem;
            padding: 1.1rem 1.4rem;
            width: 100%;
        }

        .ppc-dashboard-group-header--with-action {
            align-items: center;
            flex-direction: row;
            gap: 1rem;
            justify-content: space-between;
        }

        .ppc-dashboard-group-copy { min-width: 0; }

        .ppc-dashboard-header-action {
            align-items: center;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(219, 234, 254, 0.45);
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 auto;
            height: 2.5rem;
            justify-content: center;
            transition: background-color 150ms ease, border-color 150ms ease;
            width: 2.5rem;
        }

        .ppc-dashboard-header-action:hover {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.8);
        }

        .ppc-dashboard-group-eyebrow {
            color: #d9f75a;
            font-size: 0.75rem;
            font-weight: 800;
            line-height: 1.3;
            margin: 0 0 0.3rem;
            text-transform: uppercase;
        }

        .ppc-dashboard-group-header h2 {
            color: #ffffff;
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.15;
            margin: 0;
        }

        .ppc-dashboard-group-header-attendance h2 {
            margin-top: 0.35rem;
        }

        .ppc-dashboard-group-description {
            color: #e1edff;
            font-size: 0.95rem;
            line-height: 1.45;
            margin: 0.4rem 0 0;
            max-width: 52rem;
        }

        .ppc-holiday-highlights {
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 8px;
            padding: 0.9rem 1rem;
            width: 100%;
        }

        .ppc-holiday-highlights-header {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            justify-content: space-between;
        }

        .ppc-holiday-highlights-header h3 {
            color: var(--dashboard-blue-strong);
            font-size: 0.95rem;
            font-weight: 800;
            margin: 0;
        }

        .ppc-holiday-highlights-header time {
            color: var(--dashboard-muted);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .ppc-holiday-highlight-row {
            align-items: center;
            border-top: 1px solid var(--dashboard-border);
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
        }

        .ppc-holiday-highlight-row strong {
            display: block;
            font-size: 0.9rem;
            font-weight: 750;
        }

        .ppc-holiday-highlight-row p,
        .ppc-holiday-empty {
            color: var(--dashboard-muted);
            font-size: 0.8rem;
            margin: 0.2rem 0 0;
        }

        .ppc-holiday-today {
            background: rgba(22, 163, 74, 0.12);
            border: 1px solid rgba(22, 163, 74, 0.28);
            border-radius: 999px;
            color: #15803d;
            flex: 0 0 auto;
            font-size: 0.7rem;
            font-weight: 750;
            padding: 0.2rem 0.55rem;
        }

        .dark .ppc-holiday-today {
            color: #86efac;
        }

        .ppc-holiday-empty {
            border-top: 1px solid var(--dashboard-border);
            margin-top: 0.75rem;
            padding-top: 0.75rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-widgets > .fi-grid {
            align-items: stretch;
            gap: 0.75rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview .fi-section-content {
            gap: 0.65rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat {
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 8px;
            min-height: 5.75rem;
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
            padding: 0.75rem 0.95rem 0.75rem 1.15rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-label {
            color: var(--dashboard-muted);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-value {
            font-size: 1.65rem;
            line-height: 1.1;
            margin-top: 0.25rem;
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
            min-height: 3rem;
            padding: 0.65rem 0.85rem;
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
            padding: 0.75rem;
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
            .ppc-dashboard-group-header {
                border-radius: 14px;
                min-height: 6.75rem;
                padding: 0.85rem 1rem;
            }

            .ppc-dashboard-group-header--with-action { gap: 0.75rem; }

            .ppc-dashboard-group-header h2 {
                font-size: 1.4rem;
            }

            .ppc-dashboard-group-description {
                font-size: 0.875rem;
            }

            .ppc-holiday-highlights {
                padding: 0.75rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-widgets > .fi-grid {
                gap: 0.65rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat {
                min-height: 5.25rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-content {
                padding: 0.65rem 0.7rem 0.65rem 0.95rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-value {
                font-size: 1.4rem;
            }
        }
    </style>

    <span class="ppc-hr-dashboard-marker" aria-hidden="true"></span>
</x-filament-panels::page>
