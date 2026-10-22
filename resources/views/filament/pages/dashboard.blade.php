<x-filament-panels::page>
    <style>
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) {
            --dashboard-blue: #2563eb;
            --dashboard-blue-strong: #1e40af;
            --dashboard-blue-soft: #eff6ff;
            --dashboard-border: #e2e8f0;
            --dashboard-surface: #ffffff;
            --dashboard-muted: #64748b;
            --dashboard-text: #0f172a;
        }

        .dark .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) {
            --dashboard-blue: #60a5fa;
            --dashboard-blue-strong: #93c5fd;
            --dashboard-blue-soft: rgba(30, 64, 175, 0.2);
            --dashboard-border: #334155;
            --dashboard-surface: #1e293b;
            --dashboard-muted: #94a3b8;
            --dashboard-text: #f8fafc;
        }

        /* Compact Gradient Section Headers */
        .ppc-dashboard-group-header {
            background: linear-gradient(110deg, #1257b8 0%, #173c70 65%, #102a50 100%);
            border: 1px solid rgba(147, 197, 253, 0.35);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 4.25rem;
            padding: 0.85rem 1.25rem;
            width: 100%;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .ppc-dashboard-group-header--with-action {
            align-items: center;
            flex-direction: row;
            gap: 1rem;
            justify-content: space-between;
        }

        .ppc-dashboard-group-copy {
            min-width: 0;
            flex: 1 1 auto;
        }

        .ppc-dashboard-group-eyebrow-wrap {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.2rem;
        }

        .ppc-dashboard-group-eyebrow {
            color: #93c5fd;
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .ppc-dashboard-group-header h2 {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.2;
            margin: 0;
        }

        .ppc-dashboard-group-description {
            color: #dbeafe;
            font-size: 0.8125rem;
            line-height: 1.35;
            margin: 0.2rem 0 0;
            max-width: 52rem;
        }

        .ppc-dashboard-header-action {
            align-items: center;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 auto;
            gap: 0.35rem;
            height: 2.15rem;
            padding: 0 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 150ms ease, border-color 150ms ease, transform 150ms ease;
        }

        .ppc-dashboard-header-action:hover {
            background: rgba(255, 255, 255, 0.24);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
        }

        .ppc-dashboard-header-action svg {
            color: #93c5fd;
        }

        /* Holiday Highlights Widget */
        .ppc-holiday-highlights {
            --dashboard-surface: #000000;
            --dashboard-muted: #94a3b8;
            --dashboard-blue-strong: #93c5fd;
            background: #000000;
            border: 1px solid var(--dashboard-border);
            border-radius: 10px;
            color: #f8fafc;
            padding: 0.75rem 1rem;
            width: 100%;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
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
            font-size: 0.875rem;
            font-weight: 750;
            margin: 0;
        }

        .ppc-holiday-highlights-header time {
            color: var(--dashboard-muted);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .ppc-holiday-highlight-row {
            align-items: center;
            border-top: 1px solid var(--dashboard-border);
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
        }

        .ppc-holiday-highlight-row strong {
            display: block;
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .ppc-holiday-highlight-row p,
        .ppc-holiday-empty {
            color: var(--dashboard-muted);
            font-size: 0.75rem;
            margin: 0.15rem 0 0;
        }

        .ppc-holiday-today {
            background: rgba(22, 163, 74, 0.1);
            border: 1px solid rgba(22, 163, 74, 0.25);
            border-radius: 999px;
            color: #15803d;
            flex: 0 0 auto;
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
        }

        .dark .ppc-holiday-today {
            color: #86efac;
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .ppc-holiday-empty {
            border-top: 1px solid var(--dashboard-border);
            margin-top: 0.5rem;
            padding-top: 0.5rem;
        }

        /* Widgets Grid and Section Alignment */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-widgets > .fi-grid {
            align-items: stretch;
            gap: 0.75rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview .fi-section-content {
            gap: 0.65rem;
            padding: 0;
        }

        /* Stats Overview Cards */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat {
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 10px;
            min-height: 4.85rem;
            overflow: hidden;
            padding: 0 !important;
            position: relative;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            transition: border-color 150ms ease, transform 150ms ease, box-shadow 150ms ease;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-employee-snapshot-cards .fi-wi-stats-overview-stat,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-attendance-leave-widgets .fi-wi-stats-overview-stat {
            background: #000000 !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat::before {
            background: var(--dashboard-blue);
            content: '';
            inset: 0 auto 0 0;
            position: absolute;
            width: 3.5px;
            border-radius: 3px 0 0 3px;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat:hover {
            border-color: var(--dashboard-blue);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-content {
            justify-content: center;
            padding: 0.65rem 0.95rem 0.65rem 1.05rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-label {
            color: var(--dashboard-muted);
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-value {
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 0.2rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-description {
            font-size: 0.725rem;
            margin-top: 0.15rem;
        }

        /* Analytics Mode Cards */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) [data-dashboard-birthday] {
            background: var(--dashboard-blue-soft);
            border-color: var(--dashboard-blue);
            min-height: 6.5rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) [data-dashboard-birthday]::before {
            width: 4px;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) [data-dashboard-birthday] .fi-wi-stats-overview-stat-value {
            color: var(--dashboard-blue-strong);
            font-size: clamp(1.25rem, 2vw, 1.75rem);
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) [data-dashboard-birthday] .fi-icon {
            color: var(--dashboard-blue);
        }

        /* Chart Widgets */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart {
            min-width: 0;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart > .fi-section {
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 10px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            height: 100%;
            overflow: hidden;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-employment-charts .fi-wi-chart > .fi-section,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-employment-charts .fi-wi-chart .fi-section-header,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-employment-charts .fi-wi-chart .fi-section-content,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-attendance-leave-widgets .fi-wi-chart > .fi-section,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-attendance-leave-widgets .fi-wi-chart .fi-section-header,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-attendance-leave-widgets .fi-wi-chart .fi-section-content {
            background: #000000 !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header {
            background: #f8fafc;
            border-bottom: 1px solid var(--dashboard-border);
            min-height: 2.75rem;
            padding: 0.5rem 0.85rem;
            align-items: center;
        }

        .dark .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header {
            background: #1e293b;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-heading {
            color: var(--dashboard-blue-strong);
            font-size: 0.875rem;
            font-weight: 750;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-description {
            font-size: 0.725rem;
            color: var(--dashboard-muted);
            margin-top: 0.1rem;
        }

        /* Compact Chart Filter Form Elements */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-actions {
            align-items: center;
            gap: 0.4rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-actions .fi-input-wrp {
            min-height: 1.95rem !important;
            height: 1.95rem !important;
            border-radius: 6px !important;
            font-size: 0.75rem !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-actions .fi-input-wrp input,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-actions .fi-input-wrp select {
            font-size: 0.75rem !important;
            padding-block: 0.15rem !important;
        }

        .ppc-dashboard-summary-heading {
            align-items: center;
            display: inline-flex;
            gap: 0.5rem;
        }

        .ppc-dashboard-summary-icon {
            color: var(--dashboard-blue);
            flex: 0 0 auto;
            height: 1.05rem;
            width: 1.05rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-content {
            padding: 0.75rem 0.85rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart-canvas-ctn {
            margin-inline: auto;
            max-width: 100%;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-attrition-card {
            border-color: var(--dashboard-border) !important;
            border-top: 3px solid var(--dashboard-blue) !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-attrition-title {
            color: var(--dashboard-blue-strong) !important;
        }

        .ppc-hr-dashboard-marker {
            display: none;
        }

        @media (max-width: 767px) {
            .ppc-dashboard-group-header {
                border-radius: 10px;
                min-height: 3.75rem;
                padding: 0.75rem 0.95rem;
            }

            .ppc-dashboard-group-header h2 {
                font-size: 1.1rem;
            }

            .ppc-dashboard-group-description {
                font-size: 0.75rem;
            }

            .ppc-dashboard-header-action {
                height: 1.95rem;
                padding: 0 0.55rem;
                font-size: 0.7rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-widgets > .fi-grid {
                gap: 0.65rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat {
                min-height: 4.5rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-content {
                padding: 0.55rem 0.75rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-value {
                font-size: 1.25rem;
            }
        }
    </style>

    <span class="ppc-hr-dashboard-marker" aria-hidden="true"></span>

    {{-- Clean URL script: silently removes unwanted query parameters from the address bar --}}
    <script>
        (function () {
            function cleanDashboardUrl() {
                if (window.location.search && (window.location.search.includes('attendanceFilters') || window.location.search.includes('overtimeFilters'))) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('attendanceFilters');
                    url.searchParams.delete('overtimeFilters');
                    const clean = url.pathname + (url.search ? url.search : '') + url.hash;
                    window.history.replaceState({}, document.title, clean);
                }
            }
            cleanDashboardUrl();
            document.addEventListener('livewire:navigated', cleanDashboardUrl);
            document.addEventListener('DOMContentLoaded', cleanDashboardUrl);
        })();
    </script>
</x-filament-panels::page>
