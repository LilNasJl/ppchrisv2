<x-filament-panels::page>
    <style>
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) {
            --dashboard-blue: #60a5fa;
            --dashboard-blue-strong: #93c5fd;
            --dashboard-blue-soft: rgba(30, 64, 175, 0.2);
            --dashboard-border: #1e293b;
            --dashboard-surface: #000000;
            --dashboard-card-surface: #0a1122;
            --dashboard-muted: #94a3b8;
            --dashboard-text: #f8fafc;
        }

        .dark .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) {
            --dashboard-blue: #60a5fa;
            --dashboard-blue-strong: #93c5fd;
            --dashboard-blue-soft: rgba(30, 64, 175, 0.2);
            --dashboard-border: #1e293b;
            --dashboard-surface: #000000;
            --dashboard-card-surface: #0a1122;
            --dashboard-muted: #94a3b8;
            --dashboard-text: #f8fafc;
        }

        /* Full Width Container Enforcement */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker),
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-main-ctn,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-main,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-content,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-master-grid {
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Master Grid Layout: 100% Full-Width Block wrapping Filament's 12-Column Grid */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-master-grid {
            display: block !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-master-grid > .fi-grid {
            width: 100% !important;
            max-width: 100% !important;
            gap: 1.25rem !important;
            align-items: start !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-left-col {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.85rem !important;
            min-width: 0 !important;
            width: 100% !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-right-col {
            min-width: 0 !important;
            width: 100% !important;
        }

        /* Desktop Sticky Shout Out Hall (>= 1280px) */
        @media (min-width: 1280px) {
            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-master-grid > .fi-grid > div:last-child {
                position: sticky !important;
                top: 5rem !important;
                align-self: start !important;
                height: fit-content !important;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-right-col,
            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-shoutout {
                position: sticky !important;
                top: 5rem !important;
                align-self: start !important;
                height: fit-content !important;
            }
        }

        /* Compact Gradient Section Headers */
        .ppc-dashboard-group-header {
            background: linear-gradient(110deg, #1257b8 0%, #173c70 65%, #102a50 100%);
            border: 1px solid rgba(147, 197, 253, 0.35);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 2.85rem;
            padding: 0.55rem 1rem;
            width: 100%;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04);
        }

        .ppc-dashboard-group-header--with-action {
            align-items: center;
            flex-direction: row;
            gap: 0.85rem;
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
            margin-bottom: 0.1rem;
        }

        .ppc-dashboard-group-eyebrow {
            color: #93c5fd;
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .ppc-dashboard-group-header h2 {
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.2;
            margin: 0;
        }

        .ppc-dashboard-group-description {
            color: #dbeafe;
            font-size: 0.75rem;
            line-height: 1.3;
            margin: 0.15rem 0 0;
            max-width: 52rem;
        }

        .ppc-dashboard-header-action {
            align-items: center;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 6px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 auto;
            gap: 0.35rem;
            height: 1.95rem;
            padding: 0 0.7rem;
            font-size: 0.725rem;
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
            height: 14px;
            width: 14px;
        }

        /* Holiday Highlights Widget */
        .ppc-holiday-highlights {
            --dashboard-surface: #000000;
            --dashboard-muted: #94a3b8;
            --dashboard-blue-strong: #93c5fd;
            background: #000000;
            border: 1px solid var(--dashboard-border);
            border-radius: 8px;
            color: #f8fafc;
            padding: 0.55rem 0.85rem;
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
            font-size: 0.8125rem;
            font-weight: 750;
            margin: 0;
        }

        .ppc-holiday-highlights-header time {
            color: var(--dashboard-muted);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .ppc-holiday-highlight-row {
            align-items: center;
            border-top: 1px solid var(--dashboard-border);
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            margin-top: 0.35rem;
            padding-top: 0.35rem;
        }

        .ppc-holiday-highlight-row strong {
            display: block;
            font-size: 0.775rem;
            font-weight: 700;
        }

        .ppc-holiday-highlight-row p,
        .ppc-holiday-empty {
            color: var(--dashboard-muted);
            font-size: 0.7rem;
            margin: 0.1rem 0 0;
        }

        .ppc-holiday-today {
            background: rgba(22, 163, 74, 0.1);
            border: 1px solid rgba(22, 163, 74, 0.25);
            border-radius: 999px;
            color: #15803d;
            flex: 0 0 auto;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
        }

        .dark .ppc-holiday-today {
            color: #86efac;
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .ppc-holiday-empty {
            border-top: 1px solid var(--dashboard-border);
            margin-top: 0.35rem;
            padding-top: 0.35rem;
        }

        /* Birthday Highlights Widget */
        .ppc-birthday-highlights {
            --dashboard-surface: #000000;
            --dashboard-muted: #94a3b8;
            --dashboard-blue-strong: #93c5fd;
            background: #000000 !important;
            border: 1px solid var(--dashboard-border) !important;
            border-radius: 8px;
            color: #f8fafc;
            padding: 0.65rem 0.85rem;
            width: 100%;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }

        .ppc-birthday-highlights-header {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            justify-content: space-between;
        }

        .ppc-birthday-highlights-title-wrap {
            align-items: center;
            display: flex;
            gap: 0.5rem;
        }

        .ppc-birthday-highlights-title-wrap svg {
            color: #f59e0b;
            height: 16px;
            width: 16px;
            flex-shrink: 0;
        }

        .ppc-birthday-highlights-header h3 {
            color: var(--dashboard-blue-strong);
            font-size: 0.8125rem;
            font-weight: 750;
            margin: 0;
        }

        .ppc-birthday-highlights-tag {
            color: var(--dashboard-muted);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .ppc-birthday-highlights-link {
            align-items: center;
            color: #60a5fa;
            display: inline-flex;
            font-size: 0.7rem;
            font-weight: 700;
            gap: 0.3rem;
            text-decoration: none;
            transition: color 150ms ease;
        }

        .ppc-birthday-highlights-link:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        .ppc-birthday-cards-grid {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            margin-top: 0.45rem;
        }

        .ppc-birthday-card {
            background: #0a1122;
            border: 1px solid var(--dashboard-border);
            border-left: 3.5px solid #f59e0b;
            border-radius: 6px;
            padding: 0.55rem 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            transition: border-color 150ms ease, transform 150ms ease;
        }

        .ppc-birthday-card:hover {
            border-color: rgba(245, 158, 11, 0.4);
            transform: translateY(-1px);
        }

        .ppc-birthday-person {
            align-items: center;
            display: flex;
            gap: 0.75rem;
            min-width: 0;
        }

        .ppc-birthday-avatar {
            align-items: center;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 50%;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 34px;
            font-size: 0.75rem;
            font-weight: 800;
            height: 34px;
            justify-content: center;
            width: 34px;
            overflow: hidden;
        }

        .ppc-birthday-avatar img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        .ppc-birthday-meta {
            min-width: 0;
            display: flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .ppc-birthday-name-wrap {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .ppc-birthday-name-wrap strong {
            color: #f8fafc;
            font-size: 0.8125rem;
            font-weight: 750;
        }

        .ppc-birthday-age-pill {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 999px;
            color: #fcd34d;
            font-size: 0.625rem;
            font-weight: 700;
            padding: 0.05rem 0.45rem;
        }

        .ppc-birthday-meta p {
            color: var(--dashboard-muted);
            font-size: 0.7rem;
            margin: 0.15rem 0 0;
        }

        .ppc-birthday-badge-today {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 999px;
            color: #86efac;
            flex: 0 0 auto;
            font-size: 0.65rem;
            font-weight: 750;
            padding: 0.15rem 0.55rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .ppc-birthday-badge-today svg {
            color: #f59e0b;
            height: 12px;
            width: 12px;
        }

        .ppc-birthday-empty-row {
            align-items: center;
            border-top: 1px solid var(--dashboard-border);
            display: flex;
            justify-content: space-between;
            margin-top: 0.4rem;
            padding-top: 0.4rem;
        }

        .ppc-birthday-empty-info {
            align-items: center;
            display: flex;
            gap: 0.45rem;
        }

        .ppc-birthday-empty {
            color: var(--dashboard-muted);
            font-size: 0.725rem;
            margin: 0;
        }

        .ppc-birthday-upcoming-strip {
            border-top: 1px solid var(--dashboard-border);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
            margin-top: 0.45rem;
            padding-top: 0.45rem;
            font-size: 0.7rem;
            color: var(--dashboard-muted);
        }

        .ppc-birthday-upcoming-title {
            color: #93c5fd;
            font-weight: 750;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .ppc-birthday-upcoming-pill {
            background: #0a1122;
            border: 1px solid var(--dashboard-border);
            border-radius: 999px;
            padding: 0.1rem 0.5rem;
            font-size: 0.65rem;
            color: #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .ppc-birthday-upcoming-pill strong {
            color: #f8fafc;
            font-weight: 700;
        }

        .ppc-birthday-upcoming-pill span {
            color: #94a3b8;
        }

        /* Widgets Grid and Section Alignment */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-widgets > .fi-grid {
            align-items: stretch;
            gap: 0.65rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview .fi-section-content {
            gap: 0.5rem;
            padding: 0;
        }

        /* Ultra-Compact Stats Overview Cards */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat {
            background: var(--dashboard-surface);
            border: 1px solid var(--dashboard-border);
            border-radius: 8px;
            min-height: 3.85rem;
            overflow: hidden;
            padding: 0 !important;
            position: relative;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            transition: border-color 150ms ease, transform 150ms ease, box-shadow 150ms ease;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-employee-snapshot-cards .fi-wi-stats-overview-stat,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-leave-cards .fi-wi-stats-overview-stat,
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
            box-shadow: 0 3px 5px -1px rgba(0, 0, 0, 0.04);
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-content {
            justify-content: center;
            padding: 0.45rem 0.75rem 0.45rem 0.85rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-label {
            color: var(--dashboard-muted);
            font-size: 0.65rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-value {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 0.15rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-description {
            font-size: 0.6875rem;
            margin-top: 0.1rem;
        }

        /* Chart Widgets */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart {
            min-width: 0;
            width: 100%;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section {
            border: 1px solid var(--dashboard-border);
            border-radius: 10px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.4);
            overflow: hidden;
            background: #000000 !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header {
            background: #0a1122 !important;
            border-bottom: 1px solid var(--dashboard-border);
            min-height: 2.35rem;
            padding: 0.4rem 0.75rem;
            align-items: center;
        }

        .dark .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header {
            background: #0a1122 !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-heading {
            color: var(--dashboard-blue-strong);
            font-size: 0.8125rem;
            font-weight: 750;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-description {
            font-size: 0.6875rem;
            color: var(--dashboard-muted);
            margin-top: 0.05rem;
        }

        /* Compact Chart Filter Form Elements */
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-actions {
            align-items: center;
            gap: 0.35rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-actions .fi-input-wrp {
            background: #000000 !important;
            border: 1px solid var(--dashboard-border) !important;
            color: #f8fafc !important;
            min-height: 1.85rem !important;
            height: 1.85rem !important;
            border-radius: 6px !important;
            font-size: 0.725rem !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-actions .fi-input-wrp input,
        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-header-actions .fi-input-wrp select {
            font-size: 0.725rem !important;
            padding-block: 0.1rem !important;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart .fi-section-content {
            background: #000000 !important;
            padding: 0.65rem 0.75rem;
        }

        .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-chart-canvas-ctn {
            margin-inline: auto;
            max-width: 100%;
        }

        .ppc-hr-dashboard-marker {
            display: none;
        }

        /* Tablet & Stacked Breakpoint (<= 1279px) */
        @media (max-width: 1279px) {
            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-master-grid > .fi-grid > div:last-child,
            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-right-col,
            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .ppc-dashboard-shoutout {
                position: static !important;
                top: auto !important;
                align-self: auto !important;
            }

            .ppc-dashboard-group-header {
                min-height: 2.65rem;
                padding: 0.45rem 0.85rem;
            }

            .ppc-dashboard-group-header h2 {
                font-size: 0.975rem;
            }
        }

        /* Mobile Phone Breakpoint (< 768px) */
        @media (max-width: 767px) {
            .ppc-dashboard-group-header {
                border-radius: 8px;
                min-height: 2.5rem;
                padding: 0.45rem 0.75rem;
            }

            .ppc-dashboard-group-header h2 {
                font-size: 0.925rem;
            }

            .ppc-dashboard-group-description {
                font-size: 0.7rem;
            }

            .ppc-dashboard-header-action {
                height: 1.85rem;
                padding: 0 0.55rem;
                font-size: 0.6875rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-page-header-widgets > .fi-grid {
                gap: 0.5rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat {
                min-height: 3.5rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-content {
                padding: 0.4rem 0.6rem;
            }

            .fi-panel-hr .fi-page:has(.ppc-hr-dashboard-marker) .fi-wi-stats-overview-stat-value {
                font-size: 1.2rem;
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
