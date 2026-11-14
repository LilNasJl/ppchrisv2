<x-filament-panels::page>
    @php
        $selectedPeriod = $this->selectedPeriod;
        $overview = $this->overview;
        $changeRequestsUrl = $this->getChangeRequestsUrl();
        $onFieldDtrUrl = $this->getOnFieldDtrUrl();
        $unseenCount = $this->getUnseenReviewedRequestCount();
        $printUrl = $this->getPrintUrl();
        $canPrint = $this->canPrintDtr();
    @endphp

    <style>
        /* Suppress default Filament header if present */
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .dtr-employee-workspace {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            margin-bottom: 0.5rem;
        }


        .dark .dtr-topbar {
            border-color: #1e293b !important;
            background: linear-gradient(110deg, #0f274a, #08152b 68%, #030814) !important;
        }
        .dtr-topbar {
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
        }

        .dtr-topbar-copy {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }

        .dtr-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .dtr-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .dtr-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .dtr-actions-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .dtr-topbar .dtr-btn-badge {
            margin-left: 0.15rem;
        }

        /* Filter / Toolbar Section Card */
        .dtr-filter-bar {
            position: relative;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            padding: 0.75rem 1.15rem;
            border-radius: 0.75rem;
            border: 1px solid rgb(226, 232, 240);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            overflow: visible;
        }

        .dtr-filter-bar::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inline-size: 100%;
            block-size: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
        }

        .dark .dtr-filter-bar {
            border-color: #1e293b;
            background: #000000;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5);
        }

        .dtr-filter-heading {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-shrink: 0;
        }

        .dtr-filter-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            background: rgb(239, 246, 255);
            color: #2563eb;
            flex-shrink: 0;
        }

        .dark .dtr-filter-icon {
            background: rgba(37, 99, 235, 0.18);
            color: #60a5fa;
        }

        .dtr-filter-heading h3 {
            font-size: 0.95rem;
            font-weight: 800;
            color: rgb(15, 23, 42);
            line-height: 1.2;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .dark .dtr-filter-heading h3 {
            color: #ffffff;
        }

        .dtr-filter-heading p {
            font-size: 0.75rem;
            color: rgb(100, 116, 139);
            margin: 0.15rem 0 0;
        }

        .dark .dtr-filter-heading p {
            color: rgb(148, 163, 184);
        }

        .dtr-filter-controls {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .dtr-period-select-wrap {
            width: 270px;
            min-width: 230px;
            max-width: 320px;
            flex-shrink: 0;
        }

        .dtr-select-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .dtr-select-icon {
            position: absolute;
            left: 0.65rem;
            width: 0.95rem;
            height: 0.95rem;
            color: rgb(100, 116, 139);
            pointer-events: none;
            z-index: 1;
        }

        .dark .dtr-select-icon {
            color: rgb(148, 163, 184);
        }

        .dtr-select-chevron {
            position: absolute;
            right: 0.65rem;
            width: 0.85rem;
            height: 0.85rem;
            color: rgb(100, 116, 139);
            pointer-events: none;
            z-index: 1;
        }

        .dark .dtr-select-chevron {
            color: rgb(148, 163, 184);
        }

        .dtr-native-select {
            width: 100%;
            height: 2.15rem;
            min-height: 2.15rem;
            box-sizing: border-box;
            padding: 0.25rem 1.85rem 0.25rem 2rem;
            border-radius: 0.45rem;
            border: 1px solid rgb(203, 213, 225);
            background-color: #ffffff;
            color: rgb(15, 23, 42);
            font-size: 0.775rem;
            font-weight: 600;
            line-height: 1.4;
            transition: all 120ms ease;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .dtr-native-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.18);
        }

        .dark .dtr-native-select {
            border-color: #1e293b;
            background-color: #070b12;
            color: #ffffff;
            box-shadow: none;
        }

        .dark .dtr-native-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25);
        }

        .dark .dtr-native-select option {
            background-color: #070b12;
            color: #ffffff;
        }

        .dtr-btn {
            position: relative;
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.8rem;
            min-height: 2.15rem;
            height: 2.15rem;
            box-sizing: border-box;
            border-radius: 0.45rem;
            font-size: 0.775rem;
            font-weight: 600;
            text-decoration: none;
            color: #ffffff;
            background: #2563eb;
            border: 1px solid transparent;
            box-shadow: 0 1px 2px 0 rgba(37, 99, 235, 0.15);
            transition: all 130ms ease;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .dtr-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .dtr-btn-success {
            background: #16a34a !important;
            border-color: #22c55e !important;
            box-shadow: 0 1px 2px 0 rgba(22, 163, 74, 0.2) !important;
            color: #ffffff !important;
        }

        .dtr-btn-success:hover {
            background: #15803d !important;
        }

        .dtr-btn.outline {
            color: rgb(30, 41, 59);
            background: #ffffff;
            border-color: rgb(203, 213, 225);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }

        .dtr-btn.outline:hover {
            background: rgb(248, 250, 252);
            border-color: rgb(148, 163, 184);
            color: rgb(15, 23, 42);
        }

        .dark .dtr-btn.outline {
            color: rgb(226, 232, 240);
            background: rgb(30, 41, 59);
            border-color: rgb(71, 85, 105);
            box-shadow: none;
        }

        .dark .dtr-btn.outline:hover {
            background: rgb(51, 65, 85);
            border-color: rgb(100, 116, 139);
        }

        .dtr-btn-icon {
            width: 0.9rem;
            height: 0.9rem;
            flex-shrink: 0;
        }

        .dtr-btn-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.1rem 0.35rem;
            font-size: 0.675rem;
            font-weight: 800;
            line-height: 1;
            color: #ffffff;
            background-color: #ef4444;
            border-radius: 9999px;
            margin-left: 0.15rem;
        }

        /* Thematic Stat Summary Cards */
        .dtr-summary-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.65rem;
        }

        .dtr-stat-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.3rem;
            padding: 0.7rem 0.95rem;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
            transition: transform 130ms ease;
        }

        .dtr-stat-card:hover {
            transform: translateY(-1px);
        }

        .dark .dtr-stat-card {
            border-color: #1e293b;
            background: #000000;
        }

        .dtr-stat-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 3px;
        }

        .dtr-stat-card.theme-blue::before {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        }
        .dtr-stat-card.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        .dtr-stat-card.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }
        .dtr-stat-card.theme-purple::before {
            background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);
        }

        .dtr-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .dtr-stat-label {
            font-size: 0.675rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(100, 116, 139);
        }

        .dark .dtr-stat-label {
            color: rgb(148, 163, 184);
        }

        .dtr-stat-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.65rem;
            height: 1.65rem;
            border-radius: 0.45rem;
            flex-shrink: 0;
        }

        .theme-blue .dtr-stat-icon-wrapper {
            background: rgb(239, 246, 255);
            color: #2563eb;
        }
        .dark .theme-blue .dtr-stat-icon-wrapper {
            background: rgba(37, 99, 235, 0.18);
            color: #60a5fa;
        }

        .theme-emerald .dtr-stat-icon-wrapper {
            background: rgb(236, 253, 245);
            color: #059669;
        }
        .dark .theme-emerald .dtr-stat-icon-wrapper {
            background: rgba(5, 150, 105, 0.18);
            color: #34d399;
        }

        .theme-amber .dtr-stat-icon-wrapper {
            background: rgb(254, 243, 199);
            color: #d97706;
        }
        .dark .theme-amber .dtr-stat-icon-wrapper {
            background: rgba(217, 119, 6, 0.18);
            color: #fbbf24;
        }

        .theme-purple .dtr-stat-icon-wrapper {
            background: rgb(245, 243, 255);
            color: #7c3aed;
        }
        .dark .theme-purple .dtr-stat-icon-wrapper {
            background: rgba(124, 58, 237, 0.18);
            color: #a78bfa;
        }

        .dtr-stat-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: rgb(15, 23, 42);
            line-height: 1.15;
            letter-spacing: -0.01em;
        }

        .dark .dtr-stat-value {
            color: #ffffff;
        }

        .dtr-stat-meta {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.72rem;
            color: rgb(100, 116, 139);
            font-weight: 500;
        }

        .dark .dtr-stat-meta {
            color: rgb(148, 163, 184);
        }

        /* Clean & Proportionate Filament Table Styling */
        .fi-page .fi-ta,
        .fi-page .fi-ta-ctn {
            max-width: 100%;
            min-width: 0;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .fi-page .fi-ta,
        .dark .fi-page .fi-ta-ctn {
            border-color: #1e293b;
            background: #000000;
        }

        .fi-page .fi-ta-header {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 0.75rem 1rem !important;
            padding: 0.75rem 1rem !important;
            background: rgb(248, 250, 252) !important;
            border-bottom: 1px solid rgb(226, 232, 240) !important;
        }

        .dark .fi-page .fi-ta-header {
            background: #0b1120 !important;
            border-bottom-color: #1e293b !important;
        }

        .fi-page .fi-ta-header > div:first-child {
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }

        .fi-page .fi-ta-header .fi-ta-header-heading,
        .fi-page .fi-ta-header .fi-ta-heading {
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            line-height: 1.25rem !important;
            letter-spacing: 0.015em !important;
            color: #0f172a !important;
            margin: 0 !important;
        }

        .dark .fi-page .fi-ta-header .fi-ta-header-heading,
        .dark .fi-page .fi-ta-header .fi-ta-heading {
            color: #f1f5f9 !important;
        }

        .fi-page .fi-ta-header .fi-ta-header-description,
        .fi-page .fi-ta-header .fi-ta-description {
            font-size: 0.725rem !important;
            line-height: 1.1rem !important;
            color: rgb(100, 116, 139) !important;
            margin-top: 0.15rem !important;
            margin-bottom: 0 !important;
        }

        .dark .fi-page .fi-ta-header .fi-ta-header-description,
        .dark .fi-page .fi-ta-header .fi-ta-description {
            color: rgb(148, 163, 184) !important;
        }

        .fi-page .fi-ta-header .fi-ta-actions {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0.5rem !important;
            margin: 0 !important;
            padding: 0 !important;
            flex-shrink: 0 !important;
            flex-wrap: nowrap !important;
        }

        .fi-page .fi-ta-header .dtr-period-select-wrap {
            width: auto !important;
            min-width: 200px !important;
            max-width: 280px !important;
            flex: 0 1 auto !important;
            margin: 0 !important;
        }

        .fi-page .fi-ta-header .dtr-native-select {
            width: 100% !important;
            min-height: 2.15rem !important;
            height: 2.15rem !important;
            box-sizing: border-box !important;
            padding: 0.25rem 1.75rem 0.25rem 2rem !important;
            border-radius: 0.45rem !important;
            font-size: 0.76rem !important;
            font-weight: 600 !important;
            line-height: 1.4 !important;
            border: 1px solid rgb(203, 213, 225) !important;
            background-color: #ffffff !important;
            color: rgb(15, 23, 42) !important;
        }

        .dark .fi-page .fi-ta-header .dtr-native-select {
            border-color: #1e293b !important;
            background-color: #070b12 !important;
            color: #ffffff !important;
        }

        .fi-page .fi-ta-header .dtr-print-btn,
        .fi-page .fi-ta-header .fi-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: auto !important;
            min-width: fit-content !important;
            max-width: none !important;
            min-height: 2.15rem !important;
            height: 2.15rem !important;
            padding: 0.35rem 0.85rem !important;
            font-size: 0.785rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.01em !important;
            border-radius: 0.45rem !important;
            gap: 0.4rem !important;
            white-space: nowrap !important;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            cursor: pointer !important;
            background-color: #2563eb !important;
            border: 1px solid #3b82f6 !important;
            color: #ffffff !important;
            box-sizing: border-box !important;
        }

        .fi-page .fi-ta-header .dtr-print-btn:hover,
        .fi-page .fi-ta-header .fi-btn:hover {
            background-color: #1d4ed8 !important;
            border-color: #2563eb !important;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35) !important;
            transform: translateY(-1px);
            color: #ffffff !important;
        }

        .fi-page .fi-ta-header .dtr-print-btn svg,
        .fi-page .fi-ta-header .dtr-print-btn .fi-icon,
        .fi-page .fi-ta-header .fi-btn svg,
        .fi-page .fi-ta-header .fi-btn .fi-icon {
            width: 1.05rem !important;
            height: 1.05rem !important;
            min-width: 1.05rem !important;
            min-height: 1.05rem !important;
            max-width: 1.05rem !important;
            max-height: 1.05rem !important;
            display: inline-block !important;
            flex-shrink: 0 !important;
            color: #ffffff !important;
            margin: 0 !important;
            fill: currentColor !important;
        }

        .fi-page .fi-ta-header .dtr-print-btn.fi-disabled,
        .fi-page .fi-ta-header .dtr-print-btn[aria-disabled="true"],
        .fi-page .fi-ta-header .dtr-print-btn[disabled],
        .fi-page .fi-ta-header .fi-btn.fi-disabled,
        .fi-page .fi-ta-header .fi-btn[aria-disabled="true"],
        .fi-page .fi-ta-header .fi-btn[disabled] {
            opacity: 0.45 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            box-shadow: none !important;
            transform: none !important;
        }

        .fi-page .fi-ta-header-toolbar {
            gap: 0.35rem;
        }

        .fi-page .fi-ta-header-toolbar .fi-btn {
            min-height: 1.85rem;
            padding: 0.25rem 0.55rem;
            font-size: 0.725rem;
            border-radius: 0.4rem;
        }

        .fi-page .fi-ta table {
            font-size: 0.8rem !important;
            line-height: 1.3 !important;
            border-collapse: collapse !important;
            width: 100% !important;
        }

        .fi-page .fi-ta-header-cell {
            padding: 0.42rem 0.6rem !important;
            font-size: 0.685rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.04em !important;
            color: rgb(71, 85, 105) !important;
            background: rgb(241, 245, 249) !important;
            border-bottom: 1px solid rgb(203, 213, 225) !important;
            white-space: nowrap !important;
            height: auto !important;
            min-height: 0 !important;
        }

        .dark .fi-page .fi-ta-header-cell {
            color: #93c5fd !important;
            background: #070b12 !important;
            border-bottom-color: #1e293b !important;
        }

        .fi-page .fi-ta-cell,
        .fi-page .fi-ta table tbody td {
            padding: 0.32rem 0.55rem !important;
            border-bottom: 1px solid rgb(226, 232, 240) !important;
            background: transparent !important;
            vertical-align: middle !important;
            height: auto !important;
            min-height: 0 !important;
        }

        .dark .fi-page .fi-ta-cell,
        .dark .fi-page .fi-ta table tbody td {
            border-bottom-color: #161f2e !important;
            color: #f8fafc !important;
        }

        .fi-page .fi-ta-row,
        .fi-page .fi-ta table tbody tr {
            height: auto !important;
            min-height: 0 !important;
        }

        .fi-page .fi-ta-col-wrp,
        .fi-page .fi-ta-text,
        .fi-page .fi-ta-cell > div,
        .fi-page .fi-ta-cell > * {
            min-height: 0 !important;
            height: auto !important;
            padding-block: 0 !important;
            margin-block: 0 !important;
            line-height: 1.3 !important;
        }

        .fi-page .fi-ta-text-item {
            font-size: 0.8rem !important;
            line-height: 1.3 !important;
            padding: 0 !important;
            margin: 0 !important;
            display: inline-block;
        }

        .fi-page .dtr-cell-index,
        .fi-page .fi-ta-cell-index {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            color: rgb(148, 163, 184) !important;
            text-align: center !important;
            width: 2.35rem !important;
            min-width: 2.35rem !important;
            max-width: 2.35rem !important;
            padding: 0.32rem 0.35rem !important;
        }

        .dark .fi-page .dtr-cell-index,
        .dark .fi-page .fi-ta-cell-index {
            color: rgb(100, 116, 139) !important;
        }

        .fi-page .fi-ta table tbody tr:nth-child(even) {
            background-color: rgba(248, 250, 252, 0.65) !important;
        }

        .dark .fi-page .fi-ta table tbody tr:nth-child(even) {
            background-color: #050811 !important;
        }

        .fi-page .fi-ta table tbody tr:hover {
            background-color: rgba(239, 246, 255, 0.8) !important;
        }

        .dark .fi-page .fi-ta table tbody tr:hover {
            background-color: #0c1424 !important;
        }

        .fi-page .fi-ta .fi-badge {
            padding: 0.12rem 0.45rem !important;
            font-size: 0.7rem !important;
            font-weight: 750 !important;
            line-height: 1.2 !important;
            min-height: 0 !important;
            height: auto !important;
            border-radius: 9999px !important;
            white-space: nowrap !important;
        }

        .fi-page tbody .fi-ta-actions,
        .fi-page .fi-ta-cell .fi-ta-actions {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.25rem;
            padding: 0.2rem 0.45rem !important;
            white-space: nowrap;
        }

        .fi-page tbody .fi-ta-actions .fi-btn,
        .fi-page tbody .fi-ta-actions .fi-icon-btn,
        .fi-page .fi-ta-cell .fi-ta-actions .fi-btn,
        .fi-page .fi-ta-cell .fi-ta-actions .fi-icon-btn {
            min-height: 1.75rem !important;
            height: 1.75rem !important;
            width: 1.75rem !important;
            padding: 0 !important;
            font-size: 0.725rem !important;
            border-radius: 0.35rem !important;
        }

        .fi-page .fi-pagination {
            padding: 0.45rem 0.85rem !important;
        }

        .fi-page .fi-pagination button,
        .fi-page .fi-pagination select,
        .fi-page .fi-pagination span {
            font-size: 0.75rem !important;
        }

        .dtr-empty-box {
            background: #ffffff;
            border: 1px dashed rgb(203, 213, 225);
            border-radius: 0.65rem;
            color: rgb(100, 116, 139);
            font-size: 0.825rem;
            font-weight: 600;
            padding: 1.5rem;
            text-align: center;
        }

        .dark .dtr-empty-box {
            background: rgb(15, 23, 42);
            border-color: rgb(51, 65, 85);
            color: rgb(148, 163, 184);
        }

        @media (max-width: 1024px) {
            .dtr-summary-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {
            .dtr-summary-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.5rem !important;
            }

            .dtr-stat-card {
                padding: 0.6rem 0.75rem !important;
            }

            .dtr-stat-label {
                font-size: 0.65rem !important;
            }

            .dtr-stat-value {
                font-size: 1.15rem !important;
            }

            .dtr-stat-meta {
                font-size: 0.65rem !important;
                flex-wrap: wrap !important;
            }

            .dtr-filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .dtr-filter-controls {
                flex-wrap: wrap;
                justify-content: flex-start;
            }

            .dtr-period-select-wrap {
                max-width: 100%;
                width: 100%;
                min-width: 0;
            }
        }

        @media (max-width: 640px) {
            .dtr-topbar {
                align-items: stretch;
                padding: 0.85rem 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .dtr-title {
                font-size: 1.3rem;
            }

            .dtr-actions-row {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.5rem !important;
                width: 100% !important;
            }

            .dtr-actions-row .dtr-btn {
                width: 100% !important;
                justify-content: center !important;
                min-height: 2.35rem !important;
                padding: 0.4rem 0.5rem !important;
                font-size: 0.775rem !important;
            }

            .fi-page .fi-ta-header {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.65rem !important;
                padding: 0.75rem 0.85rem !important;
            }

            .fi-page .fi-ta-header > div:first-child {
                width: 100% !important;
            }

            .fi-page .fi-ta-header .fi-ta-header-heading,
            .fi-page .fi-ta-header .fi-ta-heading {
                font-size: 0.825rem !important;
            }

            .fi-page .fi-ta-header .fi-ta-header-description,
            .fi-page .fi-ta-header .fi-ta-description {
                font-size: 0.7rem !important;
            }

            .fi-page .fi-ta-header .fi-ta-actions {
                width: 100% !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 0.45rem !important;
                margin: 0 !important;
                flex-wrap: nowrap !important;
            }

            .fi-page .fi-ta-header .dtr-period-select-wrap {
                flex: 1 1 0 !important;
                width: auto !important;
                min-width: 0 !important;
                max-width: none !important;
            }

            .fi-page .fi-ta-header .dtr-native-select {
                min-height: 2.15rem !important;
                height: 2.15rem !important;
                padding: 0.25rem 1.45rem 0.25rem 1.85rem !important;
                font-size: 0.735rem !important;
            }

            .fi-page .fi-ta-header .dtr-select-icon {
                left: 0.5rem !important;
                width: 0.85rem !important;
                height: 0.85rem !important;
            }

            .fi-page .fi-ta-header .dtr-select-chevron {
                right: 0.45rem !important;
                width: 0.75rem !important;
                height: 0.75rem !important;
            }

            .fi-page .fi-ta-header .dtr-print-btn,
            .fi-page .fi-ta-header .fi-btn {
                flex: 0 0 auto !important;
                min-height: 2.15rem !important;
                height: 2.15rem !important;
                padding: 0.35rem 0.65rem !important;
                font-size: 0.735rem !important;
                gap: 0.3rem !important;
            }

            .fi-page .fi-ta-header .dtr-print-btn svg,
            .fi-page .fi-ta-header .dtr-print-btn .fi-icon,
            .fi-page .fi-ta-header .fi-btn svg,
            .fi-page .fi-ta-header .fi-btn .fi-icon {
                width: 0.95rem !important;
                height: 0.95rem !important;
                min-width: 0.95rem !important;
                min-height: 0.95rem !important;
            }
        }
    </style>

    <div wire:poll.15s class="dtr-employee-workspace">
        <header class="dtr-topbar">
            <div class="dtr-topbar-copy">
                <p class="dtr-eyebrow">WORKFORCE RECORDS</p>
                <h2 class="dtr-title">Daily Time Record</h2>
            </div>

            <div class="dtr-actions-row">
                <a href="{{ $changeRequestsUrl }}" class="dtr-btn" title="View Change Requests">
                    <x-filament::icon icon="heroicon-m-document-text" class="dtr-btn-icon" />
                    <span>Change Requests</span>
                    @if ($unseenCount > 0)
                        <span class="dtr-btn-badge">{{ $unseenCount }}</span>
                    @endif
                </a>

                <a href="{{ $onFieldDtrUrl }}" class="dtr-btn dtr-btn-success" title="View / Submit On Field DTR">
                    <x-filament::icon icon="heroicon-m-map-pin" class="dtr-btn-icon" />
                    <span>On Field DTR</span>
                </a>
            </div>
        </header>

        @if (! $selectedPeriod)
            <div class="dtr-empty-box">Please select a payroll period to view attendance records.</div>
        @else
            {{-- Thematic Stat Summary Cards --}}
            <section class="dtr-summary-strip" aria-label="Employee DTR Period Summary">
                {{-- Card 1: Total Days & Entries (Blue Theme) --}}
                <div class="dtr-stat-card theme-blue">
                    <div class="dtr-stat-header">
                        <span class="dtr-stat-label">Days &amp; Entries</span>
                        <div class="dtr-stat-icon-wrapper">
                            <x-filament::icon icon="heroicon-o-calendar-days" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div>
                        <div class="dtr-stat-value">{{ $this->formatDayUnits($overview['present_entries']) }} Days</div>
                        <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                            <x-filament::icon icon="heroicon-m-check-circle" class="w-3 h-3 text-blue-600 dark:text-blue-400" />
                            <span>{{ number_format($overview['total_entries']) }} entries &bull; Leave: {{ $overview['leave_entries'] }} &bull; Absent: {{ $this->formatDayUnits($overview['absent_weekday_days']) }} days + {{ number_format($overview['saturday_absent_hours']) }} Saturday hrs</span>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Credited Work Hours (Emerald Theme) --}}
                <div class="dtr-stat-card theme-emerald">
                    <div class="dtr-stat-header">
                        <span class="dtr-stat-label">Credited Work Hours</span>
                        <div class="dtr-stat-icon-wrapper">
                            <x-filament::icon icon="heroicon-o-clock" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div>
                        <div class="dtr-stat-value">{{ number_format($overview['total_credited_work'] / 60, 1) }} hrs</div>
                        <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                            <x-filament::icon icon="heroicon-m-clock" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                            <span>{{ number_format($overview['total_credited_work']) }} total minutes</span>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Credited Overtime (Amber Theme) --}}
                <div class="dtr-stat-card theme-amber">
                    <div class="dtr-stat-header">
                        <span class="dtr-stat-label">Credited Overtime</span>
                        <div class="dtr-stat-icon-wrapper">
                            <x-filament::icon icon="heroicon-o-bolt" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div>
                        <div class="dtr-stat-value">{{ number_format($overview['total_credited_overtime'] / 60, 1) }} hrs</div>
                        <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                            <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 text-slate-400" />
                            <span>{{ number_format($overview['total_credited_overtime']) }} mins credited</span>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Late & Undertime (Purple Theme) --}}
                <div class="dtr-stat-card theme-purple">
                    <div class="dtr-stat-header">
                        <span class="dtr-stat-label">Late &amp; Undertime</span>
                        <div class="dtr-stat-icon-wrapper">
                            <x-filament::icon icon="heroicon-o-exclamation-circle" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div>
                        <div class="dtr-stat-value">
                            @if ($overview['total_late'] > 0 || $overview['total_undertime'] > 0)
                                {{ $overview['total_late'] }}m / {{ $overview['total_undertime'] }}m
                            @else
                                0 mins
                            @endif
                        </div>
                        <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                            <span>{{ $overview['total_late'] }}m Late &bull; {{ $overview['total_undertime'] }}m Undertime</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- DTR Table --}}
        <section class="dtr-table-container">
            {{ $this->table }}
        </section>
    </div>
</x-filament-panels::page>
