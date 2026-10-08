<x-filament-panels::page>
    @php
        $periods = $this->payrollPeriods();
        $branches = $this->branches();
        $selectedPeriod = $this->selectedPeriod();
        $selectedBranch = $this->selectedBranch();
        $rows = $this->filteredEmployeeRows();
        $summary = $this->periodSummary($rows);
    @endphp

    <style>
        .dtr-workspace {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            width: 100%;
        }

        /* Topbar Header */
        .dtr-topbar {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem 1rem;
            padding: 0.85rem 1.15rem;
            border-radius: 0.75rem;
            border: 1px solid rgb(226, 232, 240);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .dtr-topbar::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inline-size: 100%;
            block-size: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);
        }

        .dark .dtr-topbar {
            border-color: rgb(51, 65, 85);
            background: linear-gradient(180deg, rgb(30, 41, 59) 0%, rgb(15, 23, 42) 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .dtr-topbar-copy {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }


        .dtr-title {
            color: rgb(15, 23, 42);
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .dark .dtr-title {
            color: #ffffff;
        }

        .dtr-subtitle {
            color: rgb(100, 116, 139);
            font-size: 0.775rem;
            line-height: 1.3;
        }

        .dark .dtr-subtitle {
            color: rgb(148, 163, 184);
        }

        .dtr-actions-row {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.4rem;
        }

        .dtr-btn {
            position: relative;
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.7rem;
            border-radius: 0.45rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            color: #ffffff;
            background: #2563eb;
            border: 1px solid transparent;
            box-shadow: 0 1px 2px 0 rgba(37, 99, 235, 0.15);
            transition: all 130ms ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .dtr-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .dtr-btn.secondary {
            background: rgb(15, 23, 42);
        }

        .dtr-btn.secondary:hover {
            background: rgb(30, 41, 59);
        }

        .dark .dtr-btn.secondary {
            background: rgb(51, 65, 85);
            color: #f8fafc;
        }

        .dark .dtr-btn.secondary:hover {
            background: rgb(71, 85, 105);
        }

        .dtr-btn.outline {
            color: rgb(30, 41, 59);
            background: #ffffff;
            border-color: rgb(203, 213, 225);
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
        }

        .dark .dtr-btn.outline:hover {
            background: rgb(51, 65, 85);
            border-color: rgb(100, 116, 139);
        }

        .dtr-btn-icon {
            width: 0.85rem;
            height: 0.85rem;
            flex-shrink: 0;
        }

        .dtr-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.1rem;
            height: 1.1rem;
            padding: 0 0.3rem;
            border-radius: 9999px;
            background: #ef4444;
            color: #ffffff;
            font-size: 0.625rem;
            font-weight: 800;
            line-height: 1;
        }

        /* Filter Card */
        .dtr-filter-card {
            display: grid;
            grid-template-columns: minmax(13rem, 1.2fr) minmax(12rem, 1fr) minmax(13rem, 1.3fr) auto;
            gap: 0.65rem;
            align-items: end;
            padding: 0.75rem 1rem;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }

        .dark .dtr-filter-card {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .dtr-field {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .dtr-label {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.675rem;
            font-weight: 700;
            color: rgb(71, 85, 105);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .dark .dtr-label {
            color: rgb(203, 213, 225);
        }

        .dtr-label-icon {
            width: 0.75rem;
            height: 0.75rem;
            color: rgb(100, 116, 139);
        }

        .dtr-input,
        .dtr-select {
            width: 100%;
            height: 2.15rem;
            padding: 0.25rem 0.55rem;
            border-radius: 0.4rem;
            border: 1px solid rgb(203, 213, 225);
            background: #ffffff;
            color: rgb(15, 23, 42);
            font-size: 0.785rem;
            font-weight: 500;
            transition: all 120ms ease;
            outline: none;
        }

        .dtr-input:focus,
        .dtr-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.18);
        }

        .dark .dtr-input,
        .dark .dtr-select {
            border-color: rgb(71, 85, 105);
            background: rgb(30, 41, 59);
            color: #ffffff;
        }

        .dark .dtr-input:focus,
        .dark .dtr-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25);
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
            gap: 0.35rem;
            padding: 0.75rem 0.95rem;
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
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
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
        .dtr-stat-card.theme-purple::before {
            background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);
        }
        .dtr-stat-card.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        .dtr-stat-card.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }

        .dtr-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .dtr-stat-label {
            font-size: 0.65rem;
            font-weight: 700;
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
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 0.4rem;
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

        .theme-purple .dtr-stat-icon-wrapper {
            background: rgb(245, 243, 255);
            color: #7c3aed;
        }
        .dark .theme-purple .dtr-stat-icon-wrapper {
            background: rgba(124, 58, 237, 0.18);
            color: #a78bfa;
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

        .dtr-stat-value {
            font-size: 1.1rem;
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
            font-size: 0.7rem;
            color: rgb(100, 116, 139);
            font-weight: 500;
        }

        .dark .dtr-stat-meta {
            color: rgb(148, 163, 184);
        }

        /* Modern Ultra-Compact Table Card */
        .table-card {
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .table-card {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.55rem 0.85rem;
            background: rgb(248, 250, 252);
            border-bottom: 1px solid rgb(226, 232, 240);
        }

        .dark .table-card-header {
            background: rgb(30, 41, 59);
            border-bottom-color: rgb(51, 65, 85);
        }

        .table-card-title {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.725rem;
            font-weight: 700;
            color: rgb(51, 65, 85);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .dark .table-card-title {
            color: rgb(203, 213, 225);
        }

        .table-wrapper {
            overflow-x: auto;
            max-inline-size: 100%;
            max-block-size: min(640px, 78vh);
            container-type: inline-size;
        }

        .dtr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.765rem;
            line-height: 1.25;
            --label-0: "#";
            --label-1: "Employee";
            --label-2: "Company ID";
            --label-3: "Branch / Station";
            --label-4: "Designation";
            --label-5: "Synchronized DTR";
            --label-6: "Overtime Status";
            --label-7: "Actions";
        }

        .dtr-table thead {
            position: sticky;
            inset-block-start: 0;
            z-index: 5;
            background: rgb(241, 245, 249);
        }

        .dark .dtr-table thead {
            background: rgb(30, 41, 59);
        }

        .dtr-table th,
        .dtr-table td {
            padding: 0.38rem 0.6rem;
            text-align: left;
            border-bottom: 1px solid rgb(226, 232, 240);
            vertical-align: middle;
            background: transparent;
        }

        .dark .dtr-table th,
        .dark .dtr-table td {
            border-color: rgb(51, 65, 85);
        }

        .dtr-table thead th {
            font-size: 0.675rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(71, 85, 105);
            border-bottom: 1px solid rgb(203, 213, 225);
            white-space: nowrap;
        }

        .dark .dtr-table thead th {
            color: rgb(203, 213, 225);
            border-bottom-color: rgb(71, 85, 105);
        }

        .dtr-table tbody tr {
            transition: background-color 100ms ease;
        }

        .dtr-table tbody tr:nth-child(even) {
            background-color: rgba(248, 250, 252, 0.6);
        }

        .dark .dtr-table tbody tr:nth-child(even) {
            background-color: rgba(30, 41, 59, 0.3);
        }

        .dtr-table tbody tr:hover {
            background-color: rgba(239, 246, 255, 0.75) !important;
        }

        .dark .dtr-table tbody tr:hover {
            background-color: rgba(51, 65, 85, 0.45) !important;
        }

        /* Compact Index Column */
        .dtr-col-idx {
            width: 2.25rem;
            min-width: 2.25rem;
            max-width: 2.25rem;
            text-align: center !important;
        }

        .dtr-idx-text {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.7rem;
            font-weight: 700;
            color: rgb(148, 163, 184);
        }

        .dark .dtr-idx-text {
            color: rgb(100, 116, 139);
        }

        /* Compact Employee Identity Layout */
        .emp-identity {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .emp-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            color: #ffffff;
            font-size: 0.625rem;
            font-weight: 800;
            flex-shrink: 0;
            overflow: hidden;
        }

        .emp-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .emp-info {
            display: flex;
            flex-direction: column;
            gap: 0.05rem;
        }

        .emp-name {
            font-size: 0.775rem;
            font-weight: 700;
            color: rgb(15, 23, 42);
            line-height: 1.15;
            white-space: nowrap;
        }

        .dark .emp-name {
            color: #f8fafc;
        }

        .emp-meta {
            font-size: 0.65rem;
            color: rgb(100, 116, 139);
            white-space: nowrap;
        }

        .dark .emp-meta {
            color: rgb(148, 163, 184);
        }

        /* Compact Column Badges & Pills */
        .dtr-badge-code {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.38rem;
            border-radius: 0.3rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.685rem;
            font-weight: 700;
            background: rgb(241, 245, 249);
            color: rgb(51, 65, 85);
            border: 1px solid rgb(226, 232, 240);
            white-space: nowrap;
        }

        .dark .dtr-badge-code {
            background: rgb(30, 41, 59);
            color: rgb(226, 232, 240);
            border-color: rgb(51, 65, 85);
        }

        .dtr-text-plain {
            font-size: 0.765rem;
            color: rgb(51, 65, 85);
            white-space: nowrap;
        }

        .dark .dtr-text-plain {
            color: rgb(226, 232, 240);
        }

        .dtr-tag-branch {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.12rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.685rem;
            font-weight: 600;
            background: rgb(240, 249, 255);
            color: rgb(3, 105, 161);
            border: 1px solid rgb(186, 230, 253);
            white-space: nowrap;
        }

        .dark .dtr-tag-branch {
            background: rgba(3, 105, 161, 0.16);
            color: rgb(125, 211, 252);
            border-color: rgba(3, 105, 161, 0.35);
        }

        .dtr-tag-designation {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.685rem;
            font-weight: 600;
            background: rgb(245, 243, 255);
            color: rgb(109, 40, 217);
            border: 1px solid rgb(221, 214, 254);
            white-space: nowrap;
        }

        .dark .dtr-tag-designation {
            background: rgba(109, 40, 217, 0.16);
            color: rgb(196, 181, 253);
            border-color: rgba(109, 40, 217, 0.35);
        }

        .dtr-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.12rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.685rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .dtr-pill.success {
            background: rgb(236, 253, 245);
            color: rgb(4, 120, 87);
            border: 1px solid rgb(167, 243, 208);
        }

        .dark .dtr-pill.success {
            background: rgba(4, 120, 87, 0.2);
            color: rgb(110, 231, 183);
            border-color: rgba(4, 120, 87, 0.4);
        }

        .dtr-pill.warning {
            background: rgb(254, 243, 199);
            color: rgb(180, 83, 9);
            border: 1px solid rgb(253, 230, 138);
        }

        .dark .dtr-pill.warning {
            background: rgba(180, 83, 9, 0.2);
            color: rgb(252, 211, 77);
            border-color: rgba(180, 83, 9, 0.4);
        }

        .dtr-pill.neutral {
            background: rgb(241, 245, 249);
            color: rgb(100, 116, 139);
            border: 1px solid rgb(226, 232, 240);
        }

        .dark .dtr-pill.neutral {
            background: rgb(30, 41, 59);
            color: rgb(148, 163, 184);
            border-color: rgb(51, 65, 85);
        }

        .dtr-pill-icon {
            width: 0.75rem;
            height: 0.75rem;
            flex-shrink: 0;
        }

        .dtr-hours-sub {
            display: inline-block;
            font-size: 0.65rem;
            color: rgb(100, 116, 139);
            margin-left: 0.2rem;
        }

        .dark .dtr-hours-sub {
            color: rgb(148, 163, 184);
        }

        /* Horizontal Action Buttons inside Table Cell */
        .dtr-cell-actions {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.35rem;
            white-space: nowrap;
        }

        .dtr-cell-btn {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.45rem;
            border-radius: 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-decoration: none;
            color: #ffffff;
            background: #2563eb;
            border: 1px solid transparent;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.15);
            transition: all 120ms ease;
            white-space: nowrap;
            line-height: 1.2;
        }

        .dtr-cell-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .dtr-cell-btn.outline {
            color: rgb(30, 41, 59);
            background: #ffffff;
            border-color: rgb(203, 213, 225);
            box-shadow: none;
        }

        .dtr-cell-btn.outline:hover {
            background: rgb(248, 250, 252);
            border-color: rgb(148, 163, 184);
            color: rgb(15, 23, 42);
        }

        .dark .dtr-cell-btn.outline {
            color: rgb(226, 232, 240);
            background: rgb(30, 41, 59);
            border-color: rgb(71, 85, 105);
        }

        .dark .dtr-cell-btn.outline:hover {
            background: rgb(51, 65, 85);
            border-color: rgb(100, 116, 139);
        }

        .dtr-cell-btn-icon {
            width: 0.75rem;
            height: 0.75rem;
            flex-shrink: 0;
        }

        /* Responsive Layout */
        @media (max-width: 1024px) {
            .dtr-filter-card {
                grid-template-columns: 1fr 1fr;
            }
            .dtr-summary-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .dtr-filter-card {
                grid-template-columns: 1fr;
            }
            .dtr-summary-strip {
                grid-template-columns: 1fr;
            }
        }

        @container (width < 640px) {
            .dtr-table,
            .dtr-table thead,
            .dtr-table tbody,
            .dtr-table tr,
            .dtr-table th,
            .dtr-table td {
                display: block;
            }

            .dtr-table thead {
                display: none;
            }

            .dtr-table tr {
                margin: 0.5rem;
                padding: 0.55rem;
                border: 1px solid rgb(226, 232, 240);
                border-radius: 0.55rem;
                background: #ffffff;
            }

            .dark .dtr-table tr {
                border-color: rgb(51, 65, 85);
                background: rgb(30, 41, 59);
            }

            .dtr-table th,
            .dtr-table td {
                border: none;
                padding: 0.25rem 0;
            }

            .dtr-col-idx {
                text-align: left !important;
                font-size: 0.7rem;
                font-weight: 700;
                color: rgb(100, 116, 139);
            }

            .dtr-col-idx::before {
                content: "#: ";
            }

            .dtr-table td:nth-child(3)::before {
                content: var(--label-2) ": ";
                font-weight: 700;
                color: rgb(100, 116, 139);
            }
            .dtr-table td:nth-child(4)::before {
                content: var(--label-3) ": ";
                font-weight: 700;
                color: rgb(100, 116, 139);
            }
            .dtr-table td:nth-child(5)::before {
                content: var(--label-4) ": ";
                font-weight: 700;
                color: rgb(100, 116, 139);
            }
            .dtr-table td:nth-child(6)::before {
                content: var(--label-5) ": ";
                font-weight: 700;
                color: rgb(100, 116, 139);
            }
            .dtr-table td:nth-child(7)::before {
                content: var(--label-6) ": ";
                font-weight: 700;
                color: rgb(100, 116, 139);
            }
            .dtr-table td:nth-child(8)::before {
                content: var(--label-7) ": ";
                font-weight: 700;
                color: rgb(100, 116, 139);
            }
        }
    </style>

    <div class="dtr-workspace">
        {{-- Topbar Header --}}
        <header class="dtr-topbar">
            <div class="dtr-topbar-copy">
                <h2 class="dtr-title">Daily Time Record Management</h2>
                <p class="dtr-subtitle">
                    Live synchronized station and HR attendance records with instant payroll period, branch, and employee filters.
                </p>
            </div>

            <div class="dtr-actions-row">
                <a href="{{ $this->dtrChangeRequestsUrl() }}" class="dtr-btn">
                    <x-filament::icon icon="heroicon-o-document-text" class="dtr-btn-icon" />
                    <span>Change Requests</span>
                    @if ($this->dtrChangeRequestCount() > 0)
                        <span class="dtr-badge">{{ $this->dtrChangeRequestCount() }}</span>
                    @endif
                </a>

                <a href="{{ $this->dtrProofSubmissionsUrl() }}" class="dtr-btn">
                    <x-filament::icon icon="heroicon-o-map-pin" class="dtr-btn-icon" />
                    <span>On Field DTR</span>
                    @if ($this->dtrProofSubmissionCount() > 0)
                        <span class="dtr-badge">{{ $this->dtrProofSubmissionCount() }}</span>
                    @endif
                </a>

                <a href="{{ $this->dtrImporterUrl() }}" class="dtr-btn secondary">
                    <x-filament::icon icon="heroicon-o-arrow-up-tray" class="dtr-btn-icon" />
                    <span>DTR Importer</span>
                </a>
            </div>
        </header>

        {{-- Filter Card --}}
        <section class="dtr-filter-card" aria-label="DTR Filters">
            <div class="dtr-field">
                <label class="dtr-label" for="dtr-period-filter">
                    <x-filament::icon icon="heroicon-m-calendar" class="dtr-label-icon" />
                    Payroll Period
                </label>
                <select id="dtr-period-filter" wire:model.live="periodId" class="dtr-select">
                    <option value="">Select Payroll Period</option>
                    @foreach ($periods as $periodOption)
                        <option value="{{ $periodOption->id }}">
                            {{ $periodOption->title }} ({{ $periodOption->is_locked ? 'Locked' : 'Open' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="dtr-field">
                <label class="dtr-label" for="dtr-branch-filter">
                    <x-filament::icon icon="heroicon-m-building-storefront" class="dtr-label-icon" />
                    Branch / Station
                </label>
                <select id="dtr-branch-filter" wire:model.live="branchId" class="dtr-select">
                    <option value="">All Branches / Stations</option>
                    @foreach ($branches as $branchOption)
                        <option value="{{ $branchOption->id }}">{{ $branchOption->branch_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="dtr-field">
                <label class="dtr-label" for="dtr-employee-search">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="dtr-label-icon" />
                    Search Employee
                </label>
                <input
                    id="dtr-employee-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name, company ID, or designation..."
                    class="dtr-input"
                />
            </div>

            <div style="display: flex; flex-direction: row; flex-wrap: nowrap; gap: 0.35rem; align-items: flex-end;">
                <button
                    type="button"
                    wire:click="syncStationRecords"
                    wire:loading.attr="disabled"
                    class="dtr-btn outline"
                    style="height: 2.15rem;"
                    title="Synchronize pending station DTR records"
                >
                    <x-filament::icon
                        icon="heroicon-o-arrow-path"
                        class="dtr-btn-icon"
                        wire:loading.class="animate-spin"
                        wire:target="syncStationRecords"
                    />
                    <span>Sync Station Records</span>
                    @if ($this->unsyncedStationRecordCount() > 0)
                        <span class="dtr-badge">{{ $this->unsyncedStationRecordCount() }}</span>
                    @endif
                </button>

                <button
                    type="button"
                    wire:click="resetFilters"
                    class="dtr-btn outline"
                    style="height: 2.15rem;"
                    title="Reset all filters"
                >
                    Reset
                </button>
            </div>
        </section>

        {{-- Color-Coded Thematic Stat Cards --}}
        <section class="dtr-summary-strip" aria-label="Selected Period Summary">
            {{-- Card 1: Selected Period (Blue Theme) --}}
            <div class="dtr-stat-card theme-blue">
                <div class="dtr-stat-header">
                    <span class="dtr-stat-label">Selected Period</span>
                    <div class="dtr-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div>
                    <div class="dtr-stat-value">{{ $selectedPeriod?->title ?? 'None Selected' }}</div>
                    <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                        @if ($selectedPeriod)
                            @if ($selectedPeriod->is_locked)
                                <span class="dtr-pill warning" style="font-size: 0.625rem; padding: 0.08rem 0.3rem;">Locked</span>
                            @else
                                <span class="dtr-pill success" style="font-size: 0.625rem; padding: 0.08rem 0.3rem;">Open</span>
                            @endif
                            <span>{{ $selectedBranch ? $selectedBranch->branch_name : 'All Stations' }}</span>
                        @else
                            <span>Select a period above</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card 2: Matching Employees (Purple Theme) --}}
            <div class="dtr-stat-card theme-purple">
                <div class="dtr-stat-header">
                    <span class="dtr-stat-label">Matching Employees</span>
                    <div class="dtr-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-users" class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div>
                    <div class="dtr-stat-value">{{ number_format($summary['total_employees']) }}</div>
                    <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                        <x-filament::icon icon="heroicon-m-check-circle" class="w-3 h-3 text-purple-600 dark:text-purple-400" />
                        <span>{{ number_format($summary['employees_with_dtr']) }} with active DTR</span>
                    </div>
                </div>
            </div>

            {{-- Card 3: Synchronized D.T.R Rows (Emerald Theme) --}}
            <div class="dtr-stat-card theme-emerald">
                <div class="dtr-stat-header">
                    <span class="dtr-stat-label">Synchronized Rows</span>
                    <div class="dtr-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-arrow-path-rounded-square" class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div>
                    <div class="dtr-stat-value">{{ number_format($summary['total_dtr_records']) }}</div>
                    <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                        <x-filament::icon icon="heroicon-m-arrow-path" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                        <span>Live synced between Station &amp; HR</span>
                    </div>
                </div>
            </div>

            {{-- Card 4: Pending Overtime Review (Amber Theme) --}}
            <div class="dtr-stat-card theme-amber">
                <div class="dtr-stat-header">
                    <span class="dtr-stat-label">Pending Overtime</span>
                    <div class="dtr-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-clock" class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div>
                    <div class="dtr-stat-value">{{ number_format($summary['pending_ot_records']) }}</div>
                    <div class="dtr-stat-meta" style="margin-top: 0.2rem;">
                        @if ($summary['pending_ot_records'] > 0)
                            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-3 h-3 text-amber-600 dark:text-amber-400" />
                            <span style="color: #d97706; font-weight: 700;">Early / Post OT pending</span>
                        @else
                            <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 text-slate-400" />
                            <span>All overtime reviewed</span>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Table Card --}}
        <div class="table-card">
            <div class="table-card-header">
                <div class="table-card-title">
                    <x-filament::icon icon="heroicon-m-table-cells" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                    <span>Employee Attendance Overview</span>
                </div>
                <div class="dtr-subtitle" style="font-size: 0.7rem;">
                    Showing <strong>{{ count($rows) }}</strong> {{ Str::plural('employee', count($rows)) }}
                </div>
            </div>

            <div class="table-wrapper">
                <table class="dtr-table">
                    <thead>
                        <tr>
                            <th scope="col" class="dtr-col-idx">#</th>
                            <th scope="col" style="min-width: 12.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.25rem;">
                                    <x-filament::icon icon="heroicon-m-user" class="w-3 h-3 text-slate-400" />
                                    <span>Employee</span>
                                </div>
                            </th>
                            <th scope="col" style="min-width: 6.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.25rem;">
                                    <x-filament::icon icon="heroicon-m-identification" class="w-3 h-3 text-slate-400" />
                                    <span>Company ID</span>
                                </div>
                            </th>
                            <th scope="col" style="min-width: 8.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.25rem;">
                                    <x-filament::icon icon="heroicon-m-building-storefront" class="w-3 h-3 text-slate-400" />
                                    <span>Branch / Station</span>
                                </div>
                            </th>
                            <th scope="col" style="min-width: 8.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.25rem;">
                                    <x-filament::icon icon="heroicon-m-briefcase" class="w-3 h-3 text-slate-400" />
                                    <span>Designation</span>
                                </div>
                            </th>
                            <th scope="col" style="min-width: 8.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.25rem;">
                                    <x-filament::icon icon="heroicon-m-clock" class="w-3 h-3 text-slate-400" />
                                    <span>Synchronized DTR</span>
                                </div>
                            </th>
                            <th scope="col" style="min-width: 8.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.25rem;">
                                    <x-filament::icon icon="heroicon-m-bolt" class="w-3 h-3 text-slate-400" />
                                    <span>Overtime Status</span>
                                </div>
                            </th>
                            <th scope="col" style="min-width: 8rem; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $index => $row)
                            @php
                                /** @var \App\Models\Employee $emp */
                                $emp = $row['employee'];
                                $initials = strtoupper(substr((string) ($emp->firstname ?? 'E'), 0, 1) . substr((string) ($emp->lastname ?? 'M'), 0, 1));
                            @endphp
                            <tr>
                                <td class="dtr-col-idx">
                                    <span class="dtr-idx-text">{{ $loop->iteration }}</span>
                                </td>
                                <td>
                                    <div class="emp-info">
                                        <span class="emp-name">{{ $emp->full_name }}</span>
                                        <span class="emp-meta">{{ $emp->department?->name ?? 'No Department' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="dtr-badge-code">{{ $emp->company_id ?? $emp->uid ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="dtr-text-plain">{{ $emp->branch?->branch_name ?? 'Unassigned' }}</span>
                                </td>
                                <td>
                                    <span class="dtr-text-plain">{{ $emp->designation?->title ?? '—' }}</span>
                                </td>
                                <td>
                                    @if ($row['dtr_count'] > 0)
                                        <span class="dtr-pill success">
                                            {{ $row['dtr_count'] }} {{ Str::plural('entry', $row['dtr_count']) }}
                                        </span>
                                        @if ($row['credited_work_hrs'] > 0)
                                            <span class="dtr-hours-sub">
                                                ({{ number_format($row['credited_work_hrs'] / 60, 1) }} hrs)
                                            </span>
                                        @endif
                                    @else
                                        <span class="dtr-pill neutral">
                                            0 entries
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['pending_ot_count'] > 0)
                                        <span class="dtr-pill warning">
                                            {{ $row['pending_ot_count'] }} Pending OT
                                        </span>
                                    @elseif ($row['approved_ot_count'] > 0)
                                        <span class="dtr-pill success">
                                            {{ $row['approved_ot_count'] }} Approved OT
                                        </span>
                                    @else
                                        <span class="dtr-pill neutral">
                                            None
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dtr-cell-actions">
                                        @if ($selectedPeriod)
                                            <a href="{{ $row['manage_url'] }}" class="dtr-cell-btn">
                                                <x-filament::icon icon="heroicon-m-adjustments-horizontal" class="dtr-cell-btn-icon" />
                                                <span>Manage</span>
                                            </a>
                                            @if ($row['print_url'])
                                                <a href="{{ $row['print_url'] }}" target="_blank" class="dtr-cell-btn outline" title="Print D.T.R">
                                                    <x-filament::icon icon="heroicon-m-printer" class="dtr-cell-btn-icon" />
                                                    <span>Print</span>
                                                </a>
                                            @endif
                                        @else
                                            <span class="dtr-subtitle" style="font-size: 0.7rem;">Select period</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2.25rem 1rem;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.4rem;">
                                        <div style="width: 2.25rem; height: 2.25rem; border-radius: 9999px; background: rgb(241, 245, 249); display: flex; align-items: center; justify-content: center; color: rgb(100, 116, 139);">
                                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-4 h-4" />
                                        </div>
                                        <div style="font-size: 0.825rem; font-weight: 700; color: rgb(15, 23, 42);">No matching employees found</div>
                                        <div class="dtr-subtitle" style="max-width: 20rem; font-size: 0.725rem;">
                                            Try adjusting your search terms, changing the branch filter, or selecting a different payroll period.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
