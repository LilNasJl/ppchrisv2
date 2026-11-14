<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getStationDtrStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "STATION MANAGEMENT";
        }

        .emp-station-dtr-page {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        /* -------------------------------------------------------------
           KPI Stat Strip
           ------------------------------------------------------------- */
        .station-dtr-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem;
            width: 100%;
        }

        .station-dtr-kpi-card {
            position: relative;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            padding: 0.9rem 1.05rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 1px 2px -1px rgba(15, 23, 42, 0.03);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
        }

        .station-dtr-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.06), 0 4px 8px -4px rgba(15, 23, 42, 0.03);
            border-color: #cbd5e1;
        }

        .dark .station-dtr-kpi-card {
            background: #0f172a;
            border-color: #334155;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.2);
        }

        .dark .station-dtr-kpi-card:hover {
            border-color: #475569;
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.4);
        }

        /* Card Theme: Blue (Branches) */
        .station-dtr-kpi-card.theme-blue {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04) 0%, #ffffff 100%);
            border-color: rgba(37, 99, 235, 0.22);
        }
        .dark .station-dtr-kpi-card.theme-blue {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.15) 0%, #0f172a 100%);
            border-color: rgba(59, 130, 246, 0.3);
        }
        .station-dtr-kpi-card.theme-blue:hover {
            border-color: #2563eb;
        }
        .dark .station-dtr-kpi-card.theme-blue:hover {
            border-color: #3b82f6;
        }

        /* Card Theme: Emerald (Employees) */
        .station-dtr-kpi-card.theme-emerald {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.04) 0%, #ffffff 100%);
            border-color: rgba(16, 185, 129, 0.22);
        }
        .dark .station-dtr-kpi-card.theme-emerald {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.15) 0%, #0f172a 100%);
            border-color: rgba(16, 185, 129, 0.3);
        }
        .station-dtr-kpi-card.theme-emerald:hover {
            border-color: #10b981;
        }
        .dark .station-dtr-kpi-card.theme-emerald:hover {
            border-color: #34d399;
        }

        /* Card Theme: Indigo (Payroll Periods) */
        .station-dtr-kpi-card.theme-indigo {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.04) 0%, #ffffff 100%);
            border-color: rgba(99, 102, 241, 0.22);
        }
        .dark .station-dtr-kpi-card.theme-indigo {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.15) 0%, #0f172a 100%);
            border-color: rgba(99, 102, 241, 0.3);
        }
        .station-dtr-kpi-card.theme-indigo:hover {
            border-color: #6366f1;
        }
        .dark .station-dtr-kpi-card.theme-indigo:hover {
            border-color: #818cf8;
        }

        /* KPI Icon Wrapper */
        .station-dtr-kpi-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .station-dtr-kpi-card:hover .station-dtr-kpi-icon {
            transform: scale(1.05);
        }

        .station-dtr-kpi-card.theme-blue .station-dtr-kpi-icon {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .dark .station-dtr-kpi-card.theme-blue .station-dtr-kpi-icon {
            background: rgba(30, 64, 175, 0.25);
            color: #60a5fa;
            border-color: rgba(59, 130, 246, 0.4);
        }

        .station-dtr-kpi-card.theme-emerald .station-dtr-kpi-icon {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .dark .station-dtr-kpi-card.theme-emerald .station-dtr-kpi-icon {
            background: rgba(5, 150, 105, 0.25);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.4);
        }

        .station-dtr-kpi-card.theme-indigo .station-dtr-kpi-icon {
            background: #eef2ff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
        }
        .dark .station-dtr-kpi-card.theme-indigo .station-dtr-kpi-icon {
            background: rgba(79, 70, 229, 0.25);
            color: #818cf8;
            border-color: rgba(99, 102, 241, 0.4);
        }

        .station-dtr-kpi-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1;
        }

        .station-dtr-kpi-label {
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.2rem;
        }

        .dark .station-dtr-kpi-label {
            color: #94a3b8;
        }

        .station-dtr-kpi-val {
            font-size: 1.6rem;
            font-weight: 850;
            line-height: 1.15;
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
            color: #0f172a;
        }

        .dark .station-dtr-kpi-val {
            color: #f8fafc;
        }

        .station-dtr-kpi-subtext {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .dark .station-dtr-kpi-subtext {
            color: #94a3b8;
        }

        /* -------------------------------------------------------------
           Quick Workflow Shortcuts Strip
           ------------------------------------------------------------- */
        .station-dtr-shortcuts {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            padding: 0.8rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
        }

        .dark .station-dtr-shortcuts {
            background: #0f172a;
            border-color: #334155;
        }

        .station-dtr-shortcuts-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 0.5rem;
        }

        .dark .station-dtr-shortcuts-header {
            border-bottom-color: #1e293b;
        }

        .station-dtr-shortcuts-title {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.8rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #334155;
        }

        .dark .station-dtr-shortcuts-title {
            color: #e2e8f0;
        }

        .station-dtr-shortcuts-hint {
            font-size: 0.72rem;
            color: #94a3b8;
        }

        .station-dtr-shortcuts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 0.6rem;
            width: 100%;
        }

        .station-dtr-shortcut-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.65rem;
            padding: 0.6rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.15s ease;
        }

        .station-dtr-shortcut-item:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px -1px rgba(37, 99, 235, 0.08);
        }

        .dark .station-dtr-shortcut-item {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .station-dtr-shortcut-item:hover {
            background: rgba(30, 64, 175, 0.2);
            border-color: #3b82f6;
        }

        .station-dtr-shortcut-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #2563eb;
        }

        .dark .station-dtr-shortcut-icon {
            background: #0f172a;
            border-color: #334155;
            color: #60a5fa;
        }

        .station-dtr-shortcut-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1;
        }

        .station-dtr-shortcut-name {
            font-size: 0.75rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
        }

        .dark .station-dtr-shortcut-name {
            color: #f8fafc;
        }

        .station-dtr-shortcut-desc {
            font-size: 0.675rem;
            color: #64748b;
            line-height: 1.25;
        }

        .dark .station-dtr-shortcut-desc {
            color: #94a3b8;
        }

        .station-dtr-shortcut-arrow {
            color: #cbd5e1;
            flex-shrink: 0;
            transition: transform 0.15s ease, color 0.15s ease;
        }

        .station-dtr-shortcut-item:hover .station-dtr-shortcut-arrow {
            color: #2563eb;
            transform: translateX(3px);
        }

        .dark .station-dtr-shortcut-item:hover .station-dtr-shortcut-arrow {
            color: #60a5fa;
        }

        /* -------------------------------------------------------------
           Spacious Table Enclosure & Modern Styling
           ------------------------------------------------------------- */
        .station-dtr-table-wrap {
            width: 100%;
        }

        .station-dtr-table-wrap .fi-ta,
        .station-dtr-table-wrap .fi-ta-ctn {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-top: 3.5px solid #2563eb !important;
            border-radius: 0.875rem !important;
            box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.02) !important;
            overflow: hidden !important;
        }

        .dark .station-dtr-table-wrap .fi-ta,
        .dark .station-dtr-table-wrap .fi-ta-ctn {
            background: #0f172a !important;
            border-color: #334155 !important;
            border-top-color: #3b82f6 !important;
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.3) !important;
        }

        /* Header area */
        .station-dtr-table-wrap .fi-ta-header {
            padding: 0.9rem 1.1rem !important;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%) !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-header {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
            border-bottom-color: #334155 !important;
        }

        .station-dtr-table-wrap .fi-ta-header-heading {
            font-size: 1.025rem !important;
            font-weight: 800 !important;
            letter-spacing: -0.01em !important;
            color: #0f172a !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-header-heading {
            color: #f8fafc !important;
        }

        .station-dtr-table-wrap .fi-ta-header-description {
            font-size: 0.75rem !important;
            color: #64748b !important;
            margin-top: 0.25rem !important;
            line-height: 1.45 !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-header-description {
            color: #94a3b8 !important;
        }

        /* Toolbar */
        .station-dtr-table-wrap .fi-ta-header-toolbar {
            padding: 0.6rem 1rem !important;
            gap: 0.5rem !important;
            border-bottom: 1px solid #e2e8f0 !important;
            background: #ffffff !important;
        }

        .station-dtr-table-wrap .fi-ta-search-field .fi-input-wrp {
            min-height: 2.125rem !important;
        }

        .station-dtr-table-wrap .fi-ta-search-field input {
            min-height: 2.125rem !important;
            font-size: 0.8125rem !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-header-toolbar {
            background: #0f172a !important;
            border-bottom-color: #334155 !important;
        }

        /* Header cells */
        .station-dtr-table-wrap .fi-ta-header-cell {
            padding: 0.6rem 0.9rem !important;
            background: #f8fafc !important;
            border-bottom: 2px solid #e2e8f0 !important;
            font-size: 0.7rem !important;
            font-weight: 750 !important;
            letter-spacing: 0.05em !important;
            text-transform: uppercase !important;
            color: #475569 !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-header-cell {
            background: #1e293b !important;
            border-bottom-color: #334155 !important;
            color: #94a3b8 !important;
        }

        .station-dtr-table-wrap .fi-ta-header-cell-label {
            font-size: 0.7rem !important;
            font-weight: 750 !important;
            letter-spacing: 0.04em !important;
            color: inherit !important;
        }

        /* Body rows and cells */
        .station-dtr-table-wrap .fi-ta-cell {
            padding: 0.68rem 0.9rem !important;
            font-size: 0.8125rem !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-cell {
            border-bottom-color: #1e293b !important;
        }

        .station-dtr-table-wrap .fi-ta-text {
            min-height: auto !important;
            padding: 0 !important;
        }

        .station-dtr-table-wrap .fi-ta-text-item {
            font-size: 0.8125rem !important;
            line-height: 1.4 !important;
        }

        /* Zebra striping and hover */
        .station-dtr-table-wrap .fi-ta-table tbody tr:nth-child(even) {
            background: rgba(248, 250, 252, 0.75) !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-table tbody tr:nth-child(even) {
            background: rgba(30, 41, 59, 0.25) !important;
        }

        .station-dtr-table-wrap .fi-ta-table tbody tr:hover {
            background: rgba(239, 246, 255, 0.6) !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-table tbody tr:hover {
            background: rgba(30, 41, 59, 0.6) !important;
        }

        /* Index cell */
        .station-dtr-table-wrap .fi-ta-cell-index {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-size: 0.775rem !important;
            font-weight: 700 !important;
            color: #94a3b8 !important;
            text-align: center !important;
            width: 3.5rem !important;
            min-width: 3.5rem !important;
            max-width: 3.5rem !important;
        }

        /* Badges */
        .station-dtr-table-wrap .fi-badge {
            padding: 0.28rem 0.6rem !important;
            font-size: 0.725rem !important;
            font-weight: 700 !important;
            border-radius: 9999px !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }

        /* Action buttons */
        .station-dtr-table-wrap .fi-ta-actions .fi-btn {
            min-height: 2rem !important;
            height: 2rem !important;
            padding: 0.35rem 0.8rem !important;
            font-size: 0.75rem !important;
            font-weight: 650 !important;
            border-radius: 0.55rem !important;
            box-shadow: 0 1px 2px 0 rgba(37, 99, 235, 0.15) !important;
            transition: all 0.15s ease !important;
        }

        .station-dtr-table-wrap .fi-ta-actions .fi-btn:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px -2px rgba(37, 99, 235, 0.3) !important;
        }

        /* Pagination */
        .station-dtr-table-wrap .fi-pagination {
            padding: 0.6rem 1rem !important;
            border-top: 1px solid #e2e8f0 !important;
            background: #ffffff !important;
        }

        .dark .station-dtr-table-wrap .fi-pagination {
            background: #0f172a !important;
            border-top-color: #334155 !important;
        }

        /* ── Dark Theme & Contrast Overrides ────────────────────────── */
        .dark .station-dtr-kpi-card {
            background: #0f172a !important;
            border-color: #1e293b !important;
        }

        .dark .station-dtr-shortcuts {
            background: #000000 !important;
            border-color: #1e293b !important;
        }

        .dark .station-dtr-shortcut-item {
            background: #0f172a !important;
            border-color: #1e293b !important;
        }

        .dark .station-dtr-shortcut-item:hover {
            background: #1e293b !important;
            border-color: #3b82f6 !important;
        }

        .dark .station-dtr-table-wrap .fi-ta,
        .dark .station-dtr-table-wrap .fi-ta-ctn {
            background: #000000 !important;
            border-color: #1e293b !important;
            border-top: 3px solid #3b82f6 !important;
            border-radius: 0.55rem !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-header {
            background: #070b12 !important;
            border-bottom: 1px solid #1e293b !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-header-heading {
            color: #f8fafc !important;
            font-weight: 750 !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-header-cell {
            background: #070b12 !important;
            border-bottom: 1px solid #1e293b !important;
            color: #94a3b8 !important;
            font-size: 0.7rem !important;
            font-weight: 750 !important;
            letter-spacing: 0.03em !important;
            text-transform: uppercase !important;
            padding: 0.55rem 0.65rem !important;
            white-space: nowrap !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-cell {
            padding: 0.5rem 0.65rem !important;
            font-size: 0.8rem !important;
            border-bottom: 1px solid #161f2e !important;
            white-space: nowrap !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-table tbody tr:nth-child(even) {
            background: #070b12 !important;
        }

        .dark .station-dtr-table-wrap .fi-ta-table tbody tr:hover {
            background: #0f172a !important;
        }

        .station-dtr-table-wrap .fi-ta-header-description {
            display: none !important;
        }

        /* ── Modal Window & Transparency Fix ────────────────────────── */
        .fi-modal-window,
        .dark .fi-modal-window {
            background-color: #0f172a !important;
        }

        .fi-modal-header,
        .fi-modal-content,
        .fi-modal-footer {
            background-color: #0f172a !important;
        }

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .station-dtr-shortcuts-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .emp-station-dtr-page {
                gap: 0.65rem !important;
            }

            /* 3-column compact horizontal strip for stats */
            .station-dtr-kpis {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 0.45rem !important;
            }

            .station-dtr-kpi-card {
                padding: 0.55rem 0.45rem !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                gap: 0.3rem !important;
                border-radius: 0.5rem !important;
            }

            .station-dtr-kpi-icon {
                width: 2rem !important;
                height: 2rem !important;
                border-radius: 0.45rem !important;
            }

            .station-dtr-kpi-icon svg {
                width: 1.1rem !important;
                height: 1.1rem !important;
            }

            .station-dtr-kpi-body {
                align-items: center !important;
                width: 100% !important;
                gap: 0.1rem !important;
            }

            .station-dtr-kpi-label {
                font-size: 0.58rem !important;
                font-weight: 800 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 100% !important;
                line-height: 1.15 !important;
            }

            .station-dtr-kpi-val {
                font-size: 1.25rem !important;
                line-height: 1.15 !important;
            }

            .station-dtr-kpi-subtext {
                display: none !important;
            }

            /* Shortcuts on mobile */
            .station-dtr-shortcuts {
                padding: 0.65rem 0.75rem !important;
                border-radius: 0.55rem !important;
                gap: 0.45rem !important;
            }

            .station-dtr-shortcuts-header {
                padding-bottom: 0.35rem !important;
                margin-bottom: 0.15rem !important;
                border-bottom: 1px solid #1e293b !important;
            }

            .station-dtr-shortcuts-title {
                font-size: 0.78rem !important;
                font-weight: 750 !important;
            }

            .station-dtr-shortcuts-hint {
                display: none !important;
            }

            .station-dtr-shortcuts-grid {
                grid-template-columns: 1fr !important;
                gap: 0.35rem !important;
            }

            .station-dtr-shortcut-item {
                padding: 0.5rem 0.75rem !important;
                gap: 0.6rem !important;
                border-radius: 0.5rem !important;
            }

            .station-dtr-shortcut-icon {
                width: 1.85rem !important;
                height: 1.85rem !important;
                border-radius: 0.4rem !important;
            }

            .station-dtr-shortcut-icon svg {
                width: 0.95rem !important;
                height: 0.95rem !important;
            }

            .station-dtr-shortcut-name {
                font-size: 0.79rem !important;
                font-weight: 700 !important;
            }

            .station-dtr-shortcut-desc {
                display: none !important;
            }

            /* Table header on mobile */
            .station-dtr-table-wrap .fi-ta-header-heading {
                font-size: 0.92rem !important;
                line-height: 1.25 !important;
            }

            /* Modal Bottom Sheet on Mobile */
            .fi-modal-window-ctn {
                padding: 0 !important;
                display: flex !important;
                align-items: flex-end !important;
                justify-content: center !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .fi-modal-window {
                width: 100vw !important;
                max-width: 100vw !important;
                min-width: 100vw !important;
                margin: 0 !important;
                border-radius: 1rem 1rem 0 0 !important;
                border: 1px solid rgb(51, 65, 85) !important;
                border-bottom: 0 !important;
                background-color: #0f172a !important;
                box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.75) !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                max-height: 92dvh !important;
                overflow: hidden !important;
            }

            .fi-modal-header {
                flex-shrink: 0 !important;
                background-color: #0f172a !important;
                padding: 0.85rem 1rem 0.5rem !important;
                border-bottom: 1px solid rgb(51, 65, 85) !important;
            }

            .fi-modal-content {
                flex: 1 1 auto !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                background-color: #0f172a !important;
                padding: 0.65rem 0.85rem 0.75rem !important;
                gap: 0.75rem !important;
            }

            .fi-modal-footer {
                flex-shrink: 0 !important;
                background-color: #0f172a !important;
                padding: 0.65rem 0.85rem !important;
                border-top: 1px solid rgb(51, 65, 85) !important;
            }

            .fi-modal-footer-actions {
                width: 100% !important;
                display: flex !important;
                gap: 0.5rem !important;
            }

            .fi-modal-footer-actions .fi-btn {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                justify-content: center !important;
            }
        }
    </style>

    <div class="emp-station-dtr-page employee-blue-page">
        {{-- Quick Station Hub Navigation --}}
        <div class="station-dtr-shortcuts" aria-label="Station Quick Navigation">
            <div class="station-dtr-shortcuts-header">
                <span class="station-dtr-shortcuts-title">
                    <x-filament::icon icon="heroicon-m-bolt" class="w-4 h-4 text-amber-500" />
                    Station Management Shortcuts
                </span>
                <span class="station-dtr-shortcuts-hint">Direct links to hardware biometrics and attendance workflows</span>
            </div>
            <div class="station-dtr-shortcuts-grid">
                <a href="{{ \App\Filament\Employee\Pages\Station\StationBiometricsSync::getUrl() }}" class="station-dtr-shortcut-item">
                    <div class="station-dtr-shortcut-icon">
                        <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4" />
                    </div>
                    <div class="station-dtr-shortcut-info">
                        <span class="station-dtr-shortcut-name">Sync Device</span>
                        <span class="station-dtr-shortcut-desc">Poll station biometric punches</span>
                    </div>
                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 station-dtr-shortcut-arrow" />
                </a>

                <a href="{{ \App\Filament\Employee\Pages\Station\StationDtrImport::getUrl() }}" class="station-dtr-shortcut-item">
                    <div class="station-dtr-shortcut-icon">
                        <x-filament::icon icon="heroicon-m-arrow-up-tray" class="w-4 h-4" />
                    </div>
                    <div class="station-dtr-shortcut-info">
                        <span class="station-dtr-shortcut-name">D.T.R Importer</span>
                        <span class="station-dtr-shortcut-desc">Upload raw biometric .dat logs</span>
                    </div>
                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 station-dtr-shortcut-arrow" />
                </a>

                <a href="{{ \App\Filament\Employee\Pages\Station\ReviewDtrChangeRequests::getUrl() }}" class="station-dtr-shortcut-item">
                    <div class="station-dtr-shortcut-icon">
                        <x-filament::icon icon="heroicon-m-document-check" class="w-4 h-4" />
                    </div>
                    <div class="station-dtr-shortcut-info">
                        <span class="station-dtr-shortcut-name">Change Requests</span>
                        <span class="station-dtr-shortcut-desc">Review employee time adjustments</span>
                    </div>
                    @if(($stats['pending_cr'] ?? 0) > 0)
                        <span class="station-kpi-badge amber" style="margin-left: auto; margin-right: 0.35rem; font-size: 0.65rem; padding: 0.08rem 0.4rem; border-radius: 9999px; background: rgba(245, 158, 11, 0.2); color: #fcd34d; font-weight: 750;">
                            {{ $stats['pending_cr'] }} pending
                        </span>
                    @endif
                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 station-dtr-shortcut-arrow" />
                </a>

                <a href="{{ \App\Filament\Employee\Pages\Station\StationOverview::getUrl() }}" class="station-dtr-shortcut-item">
                    <div class="station-dtr-shortcut-icon">
                        <x-filament::icon icon="heroicon-m-squares-2x2" class="w-4 h-4" />
                    </div>
                    <div class="station-dtr-shortcut-info">
                        <span class="station-dtr-shortcut-name">Station Overview</span>
                        <span class="station-dtr-shortcut-desc">Station analytics and pending queue</span>
                    </div>
                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 station-dtr-shortcut-arrow" />
                </a>
            </div>
        </div>

        {{-- Table Content --}}
        <div class="station-dtr-table-wrap">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
