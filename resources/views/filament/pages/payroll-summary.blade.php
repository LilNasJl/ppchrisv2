@php
    $rows = $this->summaryRows;
    $money = fn ($value) => number_format((float) $value, 2);
@endphp

<x-filament-panels::page>
    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .summary-workspace {
            display: grid;
            gap: 0.75rem;
            width: 100%;
        }

        .summary-topbar {
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

        .summary-topbar-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .summary-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .summary-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .summary-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .summary-header-actions {
            flex: 1 1 360px;
            min-width: 0;
        }

        .summary-header-actions > div {
            justify-content: flex-end;
        }

        .summary-toolbar {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            padding: 0.85rem 1.15rem;
            border: 1px solid rgb(226, 232, 240);
            border-radius: 0.75rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .dark .summary-toolbar {
            border-color: rgba(148, 163, 184, 0.16);
            background: rgb(15, 23, 42);
            box-shadow: none;
        }

        .summary-toolbar-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem 1rem;
        }

        .summary-toolbar-heading {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
        }

        .summary-toolbar-title {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin: 0;
            color: rgb(15, 23, 42);
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .summary-toolbar-title-icon {
            width: 1.05rem;
            height: 1.05rem;
            color: rgb(99, 102, 241);
        }

        .dark .summary-toolbar-title {
            color: rgb(241, 245, 249);
        }

        .summary-locked-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.18rem 0.55rem;
            border-radius: 9999px;
            border: 1px solid rgba(245, 158, 11, 0.35);
            background: rgba(245, 158, 11, 0.12);
            color: #d97706;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .dark .summary-locked-pill {
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.18);
            border-color: rgba(245, 158, 11, 0.4);
        }

        .summary-period-field {
            width: 100%;
        }

        .summary-period-field .fi-sc,
        .summary-period-field .fi-fo-component-ctn {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 0.75rem 1.25rem !important;
            align-items: flex-end !important;
        }

        .summary-period-field .fi-fo-component-ctn > * {
            flex: 0 1 auto !important;
            min-width: 16rem !important;
            max-width: 20rem !important;
            width: 20rem !important;
        }

        .summary-stats-pills {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
        }

        .summary-row-count {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: rgb(71, 85, 105);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dark .summary-row-count {
            color: rgb(203, 213, 225);
        }

        .summary-row-count strong {
            padding: 0.15rem 0.5rem;
            border: 1px solid rgba(96, 165, 250, 0.35);
            border-radius: 9999px;
            background: rgba(37, 99, 235, 0.12);
            color: #3b82f6;
            font-size: 0.72rem;
        }

        .dark .summary-row-count strong {
            color: #60a5fa;
        }

        /* Filter trigger field & button styling - Constrained width */
        .summary-filter-modal-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            width: 20rem;
            max-width: 100%;
        }

        .summary-filter-btn-container {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            width: 100%;
        }

        .summary-filter-field-label {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.5;
            color: rgb(17, 24, 39);
        }

        .dark .summary-filter-field-label {
            color: rgb(241, 245, 249);
        }

        .summary-filter-trigger-btn {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 2.375rem;
            padding: 0.45rem 0.85rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(209, 213, 219);
            background-color: #ffffff;
            color: rgb(17, 24, 39);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            text-align: left;
        }

        .summary-filter-trigger-btn:hover {
            border-color: rgb(156, 163, 175);
            background-color: rgb(249, 250, 251);
        }

        .dark .summary-filter-trigger-btn {
            border-color: rgb(51, 65, 85);
            background-color: rgb(30, 41, 59);
            color: rgb(241, 245, 249);
            box-shadow: none;
        }

        .dark .summary-filter-trigger-btn:hover {
            border-color: rgb(71, 85, 105);
            background-color: rgb(41, 53, 72);
        }

        .summary-filter-trigger-content {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }

        .summary-filter-trigger-icon {
            width: 1.15rem;
            height: 1.15rem;
            flex-shrink: 0;
            color: rgb(99, 102, 241);
        }

        .dark .summary-filter-trigger-icon {
            color: rgb(129, 140, 248);
        }

        .summary-filter-trigger-text {
            font-size: 0.8125rem;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .summary-filter-trigger-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            background: rgba(59, 130, 246, 0.15);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.35);
            flex-shrink: 0;
        }

        .dark .summary-filter-trigger-badge {
            color: #60a5fa;
            background: rgba(59, 130, 246, 0.22);
            border-color: rgba(96, 165, 250, 0.4);
        }

        .summary-filter-trigger-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 600;
            background: rgba(148, 163, 184, 0.12);
            color: #64748b;
            border: 1px solid rgba(148, 163, 184, 0.25);
            flex-shrink: 0;
        }

        .dark .summary-filter-trigger-pill {
            color: #94a3b8;
            background: rgba(148, 163, 184, 0.16);
            border-color: rgba(148, 163, 184, 0.28);
        }

        .summary-active-filters-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.25rem;
        }

        .summary-active-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.18rem 0.55rem;
            border-radius: 0.375rem;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .summary-active-chip-department {
            background: rgba(99, 102, 241, 0.1);
            color: #4338ca;
            border: 1px solid rgba(99, 102, 241, 0.25);
        }

        .dark .summary-active-chip-department {
            background: rgba(99, 102, 241, 0.18);
            color: #a5b4fc;
            border-color: rgba(99, 102, 241, 0.35);
        }

        .summary-active-chip-branch {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .dark .summary-active-chip-branch {
            background: rgba(16, 185, 129, 0.18);
            color: #6ee7b7;
            border-color: rgba(16, 185, 129, 0.35);
        }

        .summary-active-chip-type {
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            opacity: 0.85;
        }

        .summary-active-chip-name {
            font-weight: 600;
        }

        /* Modal Inner Styling */
        .summary-modal-title-row {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
        }

        .summary-modal-header-icon {
            width: 1.35rem;
            height: 1.35rem;
            color: rgb(99, 102, 241);
        }

        .summary-modal-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .summary-modal-locked-banner {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 0.9rem;
            border-radius: 0.5rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #d97706;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .dark .summary-modal-locked-banner {
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.18);
            border-color: rgba(245, 158, 11, 0.35);
        }

        .summary-modal-locked-icon {
            width: 1.15rem;
            height: 1.15rem;
            flex-shrink: 0;
        }

        .summary-modal-callout {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            background: rgba(59, 130, 246, 0.07);
            border: 1px solid rgba(59, 130, 246, 0.22);
        }

        .dark .summary-modal-callout {
            background: rgba(30, 58, 138, 0.2);
            border-color: rgba(59, 130, 246, 0.35);
        }

        .summary-modal-callout-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: #3b82f6;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        .summary-modal-callout-text {
            font-size: 0.76rem;
            line-height: 1.45;
        }

        .summary-modal-callout-title {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #1d4ed8;
            margin-bottom: 0.15rem;
        }

        .dark .summary-modal-callout-title {
            color: #93c5fd;
        }

        .summary-modal-callout-text p {
            margin: 0;
            color: #2563eb;
        }

        .dark .summary-modal-callout-text p {
            color: #bfdbfe;
        }

        .summary-modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
        }

        .summary-modal-footer-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        /* Interactive Employee Dropdown in Summary Table */
        .summary-clickable-group {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.15s ease;
        }

        .summary-clickable-group:hover {
            background-color: rgba(99, 102, 241, 0.08) !important;
        }

        .dark .summary-clickable-group:hover {
            background-color: rgba(99, 102, 241, 0.18) !important;
        }

        .summary-group-name-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .summary-expand-chevron {
            width: 0.95rem;
            height: 0.95rem;
            color: rgb(100, 116, 139);
            transition: transform 0.2s ease, color 0.15s ease;
            flex-shrink: 0;
        }

        .summary-expand-chevron.rotate-90 {
            transform: rotate(90deg);
            color: rgb(99, 102, 241);
        }

        .dark .summary-expand-chevron {
            color: rgb(148, 163, 184);
        }

        .dark .summary-expand-chevron.rotate-90 {
            color: rgb(165, 180, 252);
        }

        .summary-group-name-text {
            font-weight: 700;
        }

        .summary-employees-subrow {
            background: rgb(248, 250, 252) !important;
        }

        .dark .summary-employees-subrow {
            background: rgba(15, 23, 42, 0.92) !important;
        }

        .summary-employees-container-cell {
            padding: 0.6rem 1rem !important;
            background: inherit !important;
            border-top: none !important;
        }

        .summary-employee-dropdown-panel {
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 0.5rem;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .dark .summary-employee-dropdown-panel {
            border-color: rgba(71, 85, 105, 0.45);
            background: rgb(30, 41, 59);
            box-shadow: none;
        }

        .summary-employee-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.45rem 0.85rem;
            background: rgba(241, 245, 249, 0.7);
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            font-size: 0.72rem;
        }

        .dark .summary-employee-panel-header {
            background: rgba(15, 23, 42, 0.65);
            border-bottom-color: rgba(71, 85, 105, 0.35);
        }

        .summary-employee-panel-title {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: rgb(30, 41, 59);
            font-size: 0.74rem;
        }

        .dark .summary-employee-panel-title {
            color: rgb(241, 245, 249);
        }

        .summary-emp-panel-icon {
            width: 0.95rem;
            height: 0.95rem;
            color: rgb(99, 102, 241);
        }

        .summary-emp-count-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.1rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            background: rgba(99, 102, 241, 0.12);
            color: #4f46e5;
        }

        .dark .summary-emp-count-badge {
            background: rgba(99, 102, 241, 0.22);
            color: #a5b4fc;
        }

        .summary-employee-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .summary-employee-subtable {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.73rem;
        }

        .summary-employee-subtable th {
            padding: 0.38rem 0.75rem !important;
            background: rgba(241, 245, 249, 0.45) !important;
            color: rgb(71, 85, 105) !important;
            font-size: 0.65rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18) !important;
            border-right: 1px solid rgba(148, 163, 184, 0.1) !important;
        }

        .dark .summary-employee-subtable th {
            background: rgba(15, 23, 42, 0.45) !important;
            color: rgb(203, 213, 225) !important;
            border-color: rgba(71, 85, 105, 0.3) !important;
        }

        .summary-employee-subtable td {
            padding: 0.38rem 0.75rem !important;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12) !important;
            border-right: 1px solid rgba(148, 163, 184, 0.1) !important;
        }

        .dark .summary-employee-subtable td {
            border-color: rgba(71, 85, 105, 0.25) !important;
        }

        .summary-employee-subtable tr:last-child td {
            border-bottom: none !important;
        }

        .summary-employee-subtable .sub-col-idx {
            width: 3.5rem;
            text-align: center;
        }

        .summary-employee-subtable .sub-col-name {
            text-align: left;
            font-weight: 600;
        }

        .summary-employee-subtable .sub-col-gross {
            width: 10rem;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .summary-employee-subtable .sub-col-net {
            width: 10rem;
            text-align: right;
            font-weight: 700;
            color: #2563eb;
            font-variant-numeric: tabular-nums;
        }

        .dark .summary-employee-subtable .sub-col-net {
            color: #60a5fa;
        }

        .summary-employee-subtable .sub-col-empty {
            text-align: center;
            padding: 0.75rem !important;
            color: rgb(148, 163, 184);
        }

        .summary-group-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.45rem;
            border-radius: 0.35rem;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-right: 0.4rem;
        }

        .summary-group-badge-department {
            background: rgba(99, 102, 241, 0.14);
            color: #4f46e5;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .dark .summary-group-badge-department {
            color: #a5b4fc;
        }

        .summary-group-badge-branch {
            background: rgba(16, 185, 129, 0.14);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .dark .summary-group-badge-branch {
            color: #6ee7b7;
        }

        .summary-section-header td {
            background: rgb(241, 245, 249) !important;
            color: rgb(30, 41, 59) !important;
            font-size: 0.68rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.05em !important;
            text-transform: uppercase;
            padding: 0.42rem 0.65rem !important;
        }

        .dark .summary-section-header td {
            background: rgba(30, 41, 59, 0.85) !important;
            color: rgb(191, 219, 254) !important;
        }

        .summary-subtotal-row td {
            background: rgb(248, 250, 252) !important;
            font-weight: 800 !important;
            border-top: 1px solid rgba(148, 163, 184, 0.35) !important;
            border-bottom: 2px solid rgba(148, 163, 184, 0.35) !important;
        }

        .dark .summary-subtotal-row td {
            background: rgba(15, 23, 42, 0.9) !important;
            color: #f8fafc !important;
        }

        .summary-table {
            width: 100%;
            min-width: 820px;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.75rem;
        }

        .summary-table th,
        .summary-table td {
            border-right: 1px solid rgba(148, 163, 184, 0.14);
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            padding: 0.48rem 0.62rem;
            vertical-align: middle;
        }

        .summary-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: rgb(226, 232, 240);
            color: rgb(30, 41, 59);
            text-align: center;
            font-size: 0.63rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .summary-table .summary-report-heading th {
            position: static;
            padding: 0.58rem 0.65rem;
            background: rgb(241, 245, 249);
            color: rgb(15, 23, 42);
            white-space: normal;
        }

        .dark .summary-table .summary-report-heading th {
            background: #050505;
            color: #ffffff;
        }

        .summary-report-title {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .summary-report-period {
            margin: 0.2rem 0 0;
            color: rgb(71, 85, 105);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .dark .summary-report-period {
            color: rgb(191, 219, 254);
        }

        .summary-accessible-caption {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        .summary-table th:first-child,
        .summary-table td:first-child {
            border-left: 0;
        }

        .summary-table th:last-child,
        .summary-table td:last-child {
            border-right: 0;
        }

        .dark .summary-table th {
            background: rgb(30, 41, 59);
            color: rgb(226, 232, 240);
        }

        .summary-table td {
            color: rgb(15, 23, 42);
            font-variant-numeric: tabular-nums;
        }

        .dark .summary-table td {
            color: #e5e7eb;
        }

        .summary-table tbody tr {
            background: #ffffff;
            transition: background-color 120ms ease;
        }

        .summary-table tbody tr:nth-child(even) {
            background: rgb(248, 250, 252);
        }

        .summary-table tbody tr:hover {
            background: rgb(241, 245, 249);
        }

        .dark .summary-table tbody tr {
            background: transparent;
        }

        .dark .summary-table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        .dark .summary-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        .summary-table tbody td:nth-child(2) {
            min-width: 12rem;
            font-weight: 700;
        }

        .summary-table .summary-col-index {
            background: rgb(241, 245, 249);
            color: rgb(100, 116, 139);
            text-align: center;
        }

        .summary-table .summary-col-branch {
            background: rgb(248, 250, 252);
        }

        .summary-table .summary-col-workers {
            background: rgb(236, 254, 255);
        }

        .summary-table .summary-col-gross {
            background: rgb(248, 250, 252);
        }

        .summary-table .summary-col-deductions {
            background: rgb(255, 241, 242);
        }

        .summary-table .summary-col-net {
            background: rgb(236, 253, 245);
            font-weight: 800;
        }

        .summary-table .summary-col-previous {
            background: rgb(241, 245, 249);
        }

        .summary-table .summary-col-variance {
            background: rgb(255, 251, 235);
        }

        .summary-table thead .summary-col-index,
        .summary-table thead .summary-col-branch {
            background: rgb(203, 213, 225);
            color: rgb(51, 65, 85);
        }

        .summary-table thead .summary-col-workers {
            background: rgb(207, 250, 254);
            color: rgb(14, 116, 144);
        }

        .summary-table thead .summary-col-gross {
            background: rgb(203, 213, 225);
            color: rgb(51, 65, 85);
        }

        .summary-table thead .summary-col-deductions {
            background: rgb(255, 228, 230);
            color: rgb(190, 24, 93);
        }

        .summary-table thead .summary-col-net {
            background: rgb(209, 250, 229);
            color: rgb(4, 120, 87);
        }

        .summary-table thead .summary-col-previous {
            background: rgb(226, 232, 240);
            color: rgb(71, 85, 105);
        }

        .summary-table thead .summary-col-variance {
            background: rgb(254, 243, 199);
            color: rgb(180, 83, 9);
        }

        .dark .summary-table tbody .summary-col-index,
        .dark .summary-table tbody .summary-col-branch {
            background: transparent;
        }

        .dark .summary-table tbody .summary-col-workers {
            background: rgba(6, 182, 212, 0.12);
        }

        .dark .summary-table tbody .summary-col-gross {
            background: rgba(148, 163, 184, 0.08);
        }

        .dark .summary-table tbody .summary-col-deductions {
            background: rgba(244, 63, 94, 0.13);
        }

        .dark .summary-table tbody .summary-col-net {
            background: rgba(16, 185, 129, 0.14);
        }

        .dark .summary-table tbody .summary-col-previous {
            background: rgba(100, 116, 139, 0.16);
        }

        .dark .summary-table tbody .summary-col-variance {
            background: rgba(245, 158, 11, 0.13);
        }

        .dark .summary-table thead .summary-col-index,
        .dark .summary-table thead .summary-col-branch {
            background: rgb(51, 65, 85);
            color: rgb(226, 232, 240);
        }

        .dark .summary-table thead .summary-col-workers {
            background: rgba(6, 95, 115, 0.65);
            color: rgb(165, 243, 252);
        }

        .dark .summary-table thead .summary-col-gross {
            background: rgb(51, 65, 85);
            color: rgb(226, 232, 240);
        }

        .dark .summary-table thead .summary-col-deductions {
            background: rgba(136, 19, 55, 0.65);
            color: rgb(254, 205, 211);
        }

        .dark .summary-table thead .summary-col-net {
            background: rgba(6, 95, 70, 0.7);
            color: rgb(167, 243, 208);
        }

        .dark .summary-table thead .summary-col-previous {
            background: rgb(51, 65, 85);
            color: rgb(203, 213, 225);
        }

        .dark .summary-table thead .summary-col-variance {
            background: rgba(146, 64, 14, 0.7);
            color: rgb(253, 230, 138);
        }

        .summary-table tfoot .summary-col-workers {
            background: rgb(207, 250, 254);
        }

        .summary-table tfoot .summary-col-gross {
            background: rgb(226, 232, 240);
        }

        .summary-table tfoot .summary-col-deductions {
            background: rgb(255, 228, 230);
        }

        .summary-table tfoot .summary-col-net {
            background: rgb(209, 250, 229);
        }

        .summary-table tfoot .summary-col-previous {
            background: rgb(226, 232, 240);
        }

        .summary-table tfoot .summary-col-variance {
            background: rgb(254, 243, 199);
        }

        .dark .summary-table tfoot .summary-col-workers {
            background: rgba(6, 95, 115, 0.65);
        }

        .dark .summary-table tfoot .summary-col-gross {
            background: rgb(51, 65, 85);
        }

        .dark .summary-table tfoot .summary-col-deductions {
            background: rgba(136, 19, 55, 0.65);
        }

        .dark .summary-table tfoot .summary-col-net {
            background: rgba(6, 95, 70, 0.7);
        }

        .dark .summary-table tfoot .summary-col-previous {
            background: rgb(51, 65, 85);
        }

        .dark .summary-table tfoot .summary-col-variance {
            background: rgba(146, 64, 14, 0.7);
        }

        .summary-table .text-right {
            text-align: right;
            white-space: nowrap;
        }

        .summary-table tfoot td {
            border-top: 2px solid #3b82f6;
            border-bottom: 0;
            background: rgb(226, 232, 240);
            color: rgb(15, 23, 42);
            font-weight: 800;
        }

        .dark .summary-table tfoot td {
            background: #050505;
            color: #f8fafc;
        }

        .summary-scroll {
            overflow-x: auto;
            max-width: 100%;
            border: 1px solid rgb(203, 213, 225);
            border-radius: 0.65rem;
            background: #ffffff;
            -webkit-overflow-scrolling: touch;
        }

        .dark .summary-scroll {
            border-color: rgb(51, 65, 85);
            background: #050505;
        }

        .summary-empty {
            display: flex;
            min-height: 6rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: rgb(100, 116, 139);
            text-align: center;
        }

        .dark .summary-empty {
            color: rgb(148, 163, 184);
        }

        .summary-empty-icon {
            width: 1.5rem;
            height: 1.5rem;
            color: #60a5fa;
        }

        @media (max-width: 760px) {
            .summary-topbar {
                align-items: stretch;
                padding: 1rem;
            }

            .summary-title {
                font-size: 1.35rem;
            }

            .summary-header-actions {
                flex-basis: 100%;
            }

            .summary-header-actions > div {
                justify-content: flex-start;
            }

            .summary-row-count {
                width: 100%;
            }
        }

        @media print {
            @page {
                margin: 0;
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            body * {
                visibility: hidden !important;
            }

            .payroll-print-area,
            .payroll-print-area * {
                visibility: visible !important;
            }

            .payroll-print-area {
                background: #fff !important;
                color: #000 !important;
                position: absolute;
                inset: 0 auto auto 0;
                padding: 10mm;
                width: 100%;
            }

            .payroll-print-area * {
                color: #000 !important;
            }

            .summary-scroll {
                overflow: visible;
                border: 0;
            }

            .summary-table {
                min-width: 0;
                font-size: 10px;
            }

            .summary-table th,
            .summary-table td {
                background: #fff !important;
                border-color: #000 !important;
                color: #000 !important;
            }

            .summary-table th {
                position: static;
                background: #f3f4f6 !important;
                color: #000 !important;
            }

            .summary-table tfoot td {
                background: #f3f4f6 !important;
                color: #000 !important;
            }

            .summary-employees-subrow,
            .summary-expand-chevron {
                display: none !important;
            }
        }
    </style>

    <div class="summary-workspace">
        <header class="summary-topbar">
            <div class="summary-topbar-copy">
                <p class="summary-eyebrow">WORKFORCE RECORDS</p>
                <h1 class="summary-title">Payroll Summary</h1>
                <p class="summary-subtitle">Consolidated payroll totals for {{ $this->selectedPeriod?->title ?: 'the selected period' }}.</p>
            </div>

            <div class="summary-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        @php
            $departmentRows = $rows->where('group_type', 'department')->values();
            $branchRows = $rows->where('group_type', 'branch')->values();
            $hasBothTypes = $departmentRows->isNotEmpty() && $branchRows->isNotEmpty();
            $hasActiveFilter = ! empty($this->branch_ids) || ! empty($this->department_ids) || ! empty($this->included_ids);

            if ($hasBothTypes) {
                $groupColumnLabel = 'Departments / Branches';
            } elseif ($departmentRows->isNotEmpty() || (! empty($this->department_ids) && empty($this->branch_ids)) || $this->group_by === 'department') {
                $groupColumnLabel = 'Departments';
            } else {
                $groupColumnLabel = 'Branches';
            }
        @endphp

        <section class="summary-toolbar" aria-label="Payroll summary filters">
            <div class="summary-toolbar-head">
                <div class="summary-toolbar-heading">
                    <h2 class="summary-toolbar-title">
                        <x-filament::icon icon="heroicon-m-adjustments-horizontal" class="summary-toolbar-title-icon" />
                        Summary Filter Settings
                    </h2>
                    @if ($this->selectedPeriod?->is_locked)
                        <span class="summary-locked-pill" title="Locked period: summary settings are read-only">
                            <x-filament::icon icon="heroicon-m-lock-closed" class="w-3.5 h-3.5" />
                            Locked Period (Read-Only)
                        </span>
                    @endif
                </div>
            </div>

            <div class="summary-period-field">
                {{ $this->form }}
            </div>
        </section>

        <div class="payroll-print-area" style="display: grid; gap: 12px;">
            @include('filament.pages.partials.company-export-header')

            <div class="summary-scroll" role="region" aria-label="Payroll summary table" tabindex="0">
                <table class="summary-table">
                    <caption class="summary-accessible-caption">Payroll summary for {{ $this->selectedPeriod?->title ?: 'the selected period' }}</caption>
                    <thead>
                        <tr class="summary-report-heading">
                            <th colspan="8">
                                <h2 class="summary-report-title">Salaries &amp; Wages - Summary</h2>
                                <p class="summary-report-period">Payroll period: {{ $this->selectedPeriod?->title ?: '-' }}</p>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col" class="summary-col-index">#</th>
                            <th scope="col" class="summary-col-branch">{{ $groupColumnLabel }}</th>
                            <th scope="col" class="summary-col-workers">No. of Workers</th>
                            <th scope="col" class="summary-col-gross">Gross Pay</th>
                            <th scope="col" class="summary-col-deductions">Less: Total Deductions</th>
                            <th scope="col" class="summary-col-net">Net Pay</th>
                            <th scope="col" class="summary-col-previous">Previous Net Pay</th>
                            <th scope="col" class="summary-col-variance">Variance</th>
                        </tr>
                    </thead>
                    <tbody x-data="{ expanded: {} }">
                        @if ($rows->isEmpty())
                            <tr>
                                <td colspan="8">
                                    <div class="summary-empty">
                                        <x-filament::icon icon="heroicon-o-document-text" class="summary-empty-icon" />
                                        <span>No payroll summary available for this period.</span>
                                    </div>
                                </td>
                            </tr>
                        @elseif ($hasBothTypes)
                            <tr class="summary-section-header">
                                <td colspan="8">Department</td>
                            </tr>
                            @foreach ($departmentRows as $row)
                                <tr>
                                    <td class="summary-col-index">{{ $row['number'] }}</td>
                                    <td class="summary-col-branch summary-clickable-group"
                                        @click="expanded['{{ $row['group_key'] }}'] = !expanded['{{ $row['group_key'] }}']"
                                        title="Click to view employees in {{ $row['branch'] }}"
                                    >
                                        <div class="summary-group-name-wrap">
                                            <x-filament::icon
                                                icon="heroicon-m-chevron-right"
                                                class="summary-expand-chevron"
                                                ::class="{ 'rotate-90': expanded['{{ $row['group_key'] }}'] }"
                                            />
                                            <span class="summary-group-badge summary-group-badge-department">Dept</span>
                                            <span class="summary-group-name-text">{{ $row['branch'] }}</span>
                                        </div>
                                    </td>
                                    <td class="summary-col-workers text-right">{{ $row['workers'] }}</td>
                                    <td class="summary-col-gross text-right">{{ $money($row['gross_pay']) }}</td>
                                    <td class="summary-col-deductions text-right">{{ $money($row['total_deductions']) }}</td>
                                    <td class="summary-col-net text-right">{{ $money($row['net_pay']) }}</td>
                                    <td class="summary-col-previous text-right">{{ $money($row['previous_net_pay']) }}</td>
                                    <td class="summary-col-variance text-right">{{ $money($row['variance']) }}</td>
                                </tr>
                                @include('filament.pages.partials.payroll-summary-employee-subtable', ['row' => $row, 'money' => $money])
                            @endforeach
                            <tr class="summary-subtotal-row">
                                <td colspan="2">DEPARTMENTS SUBTOTAL</td>
                                <td class="summary-col-workers text-right">{{ $departmentRows->sum('workers') }}</td>
                                <td class="summary-col-gross text-right">{{ $money($departmentRows->sum('gross_pay')) }}</td>
                                <td class="summary-col-deductions text-right">{{ $money($departmentRows->sum('total_deductions')) }}</td>
                                <td class="summary-col-net text-right">{{ $money($departmentRows->sum('net_pay')) }}</td>
                                <td class="summary-col-previous text-right">{{ $money($departmentRows->sum('previous_net_pay')) }}</td>
                                <td class="summary-col-variance text-right">{{ $money($departmentRows->sum('variance')) }}</td>
                            </tr>

                            <tr class="summary-section-header">
                                <td colspan="8">Branch</td>
                            </tr>
                            @foreach ($branchRows as $row)
                                <tr>
                                    <td class="summary-col-index">{{ $row['number'] }}</td>
                                    <td class="summary-col-branch summary-clickable-group"
                                        @click="expanded['{{ $row['group_key'] }}'] = !expanded['{{ $row['group_key'] }}']"
                                        title="Click to view employees in {{ $row['branch'] }}"
                                    >
                                        <div class="summary-group-name-wrap">
                                            <x-filament::icon
                                                icon="heroicon-m-chevron-right"
                                                class="summary-expand-chevron"
                                                ::class="{ 'rotate-90': expanded['{{ $row['group_key'] }}'] }"
                                            />
                                            <span class="summary-group-badge summary-group-badge-branch">Branch</span>
                                            <span class="summary-group-name-text">{{ $row['branch'] }}</span>
                                        </div>
                                    </td>
                                    <td class="summary-col-workers text-right">{{ $row['workers'] }}</td>
                                    <td class="summary-col-gross text-right">{{ $money($row['gross_pay']) }}</td>
                                    <td class="summary-col-deductions text-right">{{ $money($row['total_deductions']) }}</td>
                                    <td class="summary-col-net text-right">{{ $money($row['net_pay']) }}</td>
                                    <td class="summary-col-previous text-right">{{ $money($row['previous_net_pay']) }}</td>
                                    <td class="summary-col-variance text-right">{{ $money($row['variance']) }}</td>
                                </tr>
                                @include('filament.pages.partials.payroll-summary-employee-subtable', ['row' => $row, 'money' => $money])
                            @endforeach
                            <tr class="summary-subtotal-row">
                                <td colspan="2">BRANCHES SUBTOTAL</td>
                                <td class="summary-col-workers text-right">{{ $branchRows->sum('workers') }}</td>
                                <td class="summary-col-gross text-right">{{ $money($branchRows->sum('gross_pay')) }}</td>
                                <td class="summary-col-deductions text-right">{{ $money($branchRows->sum('total_deductions')) }}</td>
                                <td class="summary-col-net text-right">{{ $money($branchRows->sum('net_pay')) }}</td>
                                <td class="summary-col-previous text-right">{{ $money($branchRows->sum('previous_net_pay')) }}</td>
                                <td class="summary-col-variance text-right">{{ $money($branchRows->sum('variance')) }}</td>
                            </tr>
                        @else
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="summary-col-index">{{ $row['number'] }}</td>
                                    <td class="summary-col-branch summary-clickable-group"
                                        @click="expanded['{{ $row['group_key'] }}'] = !expanded['{{ $row['group_key'] }}']"
                                        title="Click to view employees in {{ $row['branch'] }}"
                                    >
                                        <div class="summary-group-name-wrap">
                                            <x-filament::icon
                                                icon="heroicon-m-chevron-right"
                                                class="summary-expand-chevron"
                                                ::class="{ 'rotate-90': expanded['{{ $row['group_key'] }}'] }"
                                            />
                                            @if (($row['group_type'] ?? '') === 'department')
                                                <span class="summary-group-badge summary-group-badge-department">Dept</span>
                                            @endif
                                            <span class="summary-group-name-text">{{ $row['branch'] }}</span>
                                        </div>
                                    </td>
                                    <td class="summary-col-workers text-right">{{ $row['workers'] }}</td>
                                    <td class="summary-col-gross text-right">{{ $money($row['gross_pay']) }}</td>
                                    <td class="summary-col-deductions text-right">{{ $money($row['total_deductions']) }}</td>
                                    <td class="summary-col-net text-right">{{ $money($row['net_pay']) }}</td>
                                    <td class="summary-col-previous text-right">{{ $money($row['previous_net_pay']) }}</td>
                                    <td class="summary-col-variance text-right">{{ $money($row['variance']) }}</td>
                                </tr>
                                @include('filament.pages.partials.payroll-summary-employee-subtable', ['row' => $row, 'money' => $money])
                            @endforeach
                        @endif
                    </tbody>

                    @if ($rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td class="summary-col-workers text-right">{{ $rows->sum('workers') }}</td>
                                <td class="summary-col-gross text-right">{{ $money($rows->sum('gross_pay')) }}</td>
                                <td class="summary-col-deductions text-right">{{ $money($rows->sum('total_deductions')) }}</td>
                                <td class="summary-col-net text-right">{{ $money($rows->sum('net_pay')) }}</td>
                                <td class="summary-col-previous text-right">{{ $money($rows->sum('previous_net_pay')) }}</td>
                                <td class="summary-col-variance text-right">{{ $money($rows->sum('variance')) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @include('filament.pages.partials.payroll-signatories', [
                'preparedBy' => $this->prepared_by,
                'checkedBy' => $this->checked_by,
                'approvedBy' => $this->approved_by,
            ])

            @include('filament.pages.partials.export-generated-at')
        </div>
    </div>
</x-filament-panels::page>
