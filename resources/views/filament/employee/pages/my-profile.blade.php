<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')

    <style>
        .employee-profile-form {
            --emp-border: #e2e8f0;
            --emp-border-subtle: #f1f5f9;
            --emp-blue: #2563eb;
            --emp-blue-strong: #1d4ed8;
            --emp-blue-soft: #eff6ff;
            --emp-blue-ring: rgba(37, 99, 235, 0.15);
            --emp-surface: #ffffff;
            --emp-surface-muted: #f8fafc;
            --emp-text-primary: #0f172a;
            --emp-text-secondary: #475569;
            --emp-text-muted: #64748b;

            color: var(--emp-text-primary);
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            max-width: 1600px;
            margin-inline: auto;
            min-width: 0;
            width: 100%;
        }

        .dark .employee-profile-form {
            --emp-border: #1e293b;
            --emp-border-subtle: #161f2e;
            --emp-blue: #3b82f6;
            --emp-blue-strong: #60a5fa;
            --emp-blue-soft: rgba(59, 130, 246, 0.16);
            --emp-blue-ring: rgba(59, 130, 246, 0.3);
            --emp-surface: #000000;
            --emp-surface-muted: #070b12;
            --emp-text-primary: #f8fafc;
            --emp-text-secondary: #cbd5e1;
            --emp-text-muted: #94a3b8;
        }

        /* Keep header untouched */
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "MY WORKSPACE";
        }

        /* Modern Compact Tabs */
        .employee-profile-form .fi-tabs {
            background: var(--emp-surface-muted);
            border: 1px solid var(--emp-border);
            border-radius: 0.55rem;
            box-shadow: none;
            display: flex;
            flex-wrap: nowrap;
            gap: 0.2rem;
            max-width: 100%;
            overflow-x: auto;
            padding: 0.25rem;
            scrollbar-width: thin;
        }

        .employee-profile-form .fi-tabs-item {
            align-items: center;
            border-radius: 0.4rem;
            color: var(--emp-text-secondary);
            display: inline-flex;
            font-size: 0.76rem;
            font-weight: 700;
            gap: 0.35rem;
            min-height: 2.15rem;
            padding: 0.35rem 0.65rem;
            transition: all 120ms ease;
            white-space: nowrap;
        }

        .dark .employee-profile-form .fi-tabs {
            background: #070b12 !important;
            border-color: #1e293b !important;
        }

        .dark .employee-profile-form .fi-tabs-item {
            color: #94a3b8 !important;
        }

        .dark .employee-profile-form .fi-tabs-item:hover,
        .dark .employee-profile-form .fi-tabs-item:focus-visible {
            background: #0c1424 !important;
            color: #f8fafc !important;
        }

        .dark .employee-profile-form .fi-tabs-item.fi-active {
            background: #2563eb !important;
            color: #ffffff !important;
        }

        .dark .employee-profile-form :is(.fi-section, .fi-sc-section) {
            background: #000000 !important;
            border-color: #1e293b !important;
        }

        .dark .employee-profile-form :is(.fi-section-header, .fi-sc-section-header) {
            background: #070b12 !important;
            border-bottom-color: #1e293b !important;
        }

        .dark .employee-profile-form :is(.fi-section-header-heading, .fi-sc-section-header-title) {
            color: #f8fafc !important;
        }

        .dark .employee-profile-form :is(.fi-section-header-description, .fi-sc-section-header-description) {
            color: #94a3b8 !important;
        }

        .dark .employee-profile-form :is(.fi-fieldset, .fi-sc-fieldset) {
            background: #000000 !important;
            border-color: #1e293b !important;
        }

        .dark .employee-profile-form .fi-fo-field-wrp-label {
            color: #cbd5e1 !important;
        }

        .dark .employee-profile-form .fi-input-wrp {
            background: #000000 !important;
            border-color: #1e293b !important;
        }

        .dark .employee-profile-form .fi-input-wrp input,
        .dark .employee-profile-form .fi-input-wrp select {
            background: transparent !important;
            color: #f8fafc !important;
        }

        .dark .employee-profile-form .fi-input-wrp.fi-disabled {
            background: #070b12 !important;
            border-color: #1e293b !important;
        }

        .dark .employee-profile-form .fi-input-wrp.fi-disabled input {
            color: #cbd5e1 !important;
        }

        .dark .employee-profile-form textarea {
            background: #000000 !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
        }

        .employee-profile-form .fi-tabs-item:hover,
        .employee-profile-form .fi-tabs-item:focus-visible {
            background: var(--emp-surface);
            color: var(--emp-blue);
        }

        .employee-profile-form .fi-tabs-item.fi-active {
            background: var(--emp-blue);
            color: #ffffff !important;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
        }

        .employee-profile-form .fi-tabs-item.fi-active :is(svg, span) {
            color: #ffffff !important;
        }

        .employee-profile-form .fi-sc-tabs-tab.fi-active {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-top: 0.45rem;
        }

        /* Sections */
        .employee-profile-form :is(.fi-section, .fi-sc-section) {
            background: var(--emp-surface);
            border: 1px solid var(--emp-border);
            border-radius: 0.55rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            gap: 0.5rem;
            overflow: hidden;
        }

        .employee-profile-form :is(.fi-section-header, .fi-sc-section-header) {
            background: var(--emp-surface-muted);
            border-bottom: 1px solid var(--emp-border-subtle);
            padding: 0.5rem 0.85rem;
        }

        .employee-profile-form :is(.fi-section-header-heading, .fi-sc-section-header-title) {
            color: var(--emp-text-primary);
            font-size: 0.84rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .employee-profile-form :is(.fi-section-header-description, .fi-sc-section-header-description) {
            color: var(--emp-text-muted);
            font-size: 0.71rem;
            margin-top: 0.1rem;
        }

        .employee-profile-form :is(.fi-section-content, .fi-sc-section-content),
        .employee-profile-form .fi-section > .fi-section-content-ctn > .fi-section-content {
            gap: 0.6rem;
            padding: 0.75rem 0.85rem;
        }

        /* Fieldsets */
        .employee-profile-form :is(.fi-fieldset, .fi-sc-fieldset) {
            background: var(--emp-surface);
            border: 1px solid var(--emp-border);
            border-radius: 0.45rem;
            gap: 0.55rem;
            margin-top: 0.25rem;
            padding: 0.6rem 0.75rem 0.7rem;
        }

        .employee-profile-form :is(.fi-fieldset > legend, .fi-sc-fieldset-legend) {
            color: var(--emp-blue);
            font-size: 0.71rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            padding: 0 0.35rem;
            text-transform: uppercase;
        }

        .dark .employee-profile-form :is(.fi-fieldset > legend, .fi-sc-fieldset-legend) {
            color: var(--emp-blue-strong);
        }

        /* Field Wrappers and Labels */
        .employee-profile-form .fi-fo-field-wrp {
            min-width: 0;
        }

        .employee-profile-form .fi-fo-field-wrp-label {
            color: var(--emp-text-secondary);
            font-size: 0.72rem;
            font-weight: 750;
            margin-bottom: 0.18rem;
        }

        .employee-profile-form .fi-fo-field-wrp-helper-text {
            color: var(--emp-text-muted);
            font-size: 0.68rem;
            margin-top: 0.18rem;
        }

        /* Inputs & Selects */
        .employee-profile-form .fi-input-wrp {
            background: var(--emp-surface);
            border: 1px solid var(--emp-border);
            border-radius: 0.4rem;
            min-height: 2.15rem;
            transition: all 120ms ease;
        }

        .employee-profile-form .fi-input-wrp:focus-within {
            border-color: var(--emp-blue);
            box-shadow: 0 0 0 2px var(--emp-blue-ring);
        }

        .employee-profile-form .fi-input-wrp input,
        .employee-profile-form .fi-input-wrp select {
            color: var(--emp-text-primary);
            font-size: 0.8125rem;
            padding: 0.35rem 0.55rem;
        }

        .employee-profile-form .fi-input-wrp-prefix,
        .employee-profile-form .fi-input-wrp-suffix {
            color: var(--emp-text-muted);
            font-size: 0.75rem;
            padding-inline: 0.5rem;
        }

        /* Readable disabled fields */
        .employee-profile-form .fi-input-wrp.fi-disabled {
            background: var(--emp-surface-muted);
            border-color: var(--emp-border-subtle);
        }

        .employee-profile-form .fi-input-wrp.fi-disabled input {
            color: var(--emp-text-primary);
            font-weight: 600;
            opacity: 0.92;
        }

        .employee-profile-form textarea {
            border: 1px solid var(--emp-border);
            border-radius: 0.4rem;
            font-size: 0.8125rem;
            padding: 0.4rem 0.55rem;
        }

        .employee-profile-form textarea:focus {
            border-color: var(--emp-blue);
            box-shadow: 0 0 0 2px var(--emp-blue-ring);
        }

        /* File Upload */
        .employee-profile-form .fi-fo-file-upload {
            max-width: 100%;
        }

        .employee-profile-form .filepond--root {
            font-size: 0.76rem;
            margin-bottom: 0;
        }

        /* --- Account Card Partial --- */
        .emp-account-card {
            align-items: center;
            background: var(--emp-surface);
            border: 1px solid var(--emp-border);
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            gap: 1.5rem;
            padding: 1.15rem 1.4rem;
            width: 100%;
        }

        .dark .emp-account-card {
            background: #000000 !important;
            border-color: #1e293b !important;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5) !important;
        }

        .emp-avatar-block {
            align-items: center;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            width: 96px;
        }

        .emp-avatar-frame {
            align-items: center;
            background: var(--emp-blue-soft);
            border: 3px solid #ffffff;
            border-radius: 9999px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.06);
            display: flex;
            height: 96px;
            width: 96px;
            min-height: 96px;
            min-width: 96px;
            max-height: 96px;
            max-width: 96px;
            justify-content: center;
            overflow: hidden;
            position: relative;
            transition: all 180ms ease;
            flex-shrink: 0;
        }

        .dark .emp-avatar-frame {
            background: #070b12 !important;
            border-color: #1e293b !important;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
        }

        .emp-avatar-frame.emp-avatar-clickable {
            cursor: pointer;
            outline: none;
            padding: 0;
        }

        .emp-avatar-frame.emp-avatar-clickable:hover {
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.28), 0 0 0 2px var(--emp-blue);
            transform: scale(1.03);
        }

        .emp-avatar-frame.emp-avatar-clickable:focus-visible {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.4);
        }

        .emp-avatar-img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .emp-avatar-initials {
            color: var(--emp-blue);
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            user-select: none;
        }

        .emp-avatar-overlay {
            align-items: center;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(2px);
            border-radius: 9999px;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            height: 100%;
            inset: 0;
            justify-content: center;
            opacity: 0;
            position: absolute;
            transition: opacity 180ms ease;
            width: 100%;
        }

        .emp-avatar-frame.emp-avatar-clickable:hover .emp-avatar-overlay,
        .emp-avatar-frame.emp-avatar-clickable:focus-visible .emp-avatar-overlay {
            opacity: 1;
        }

        .emp-avatar-overlay-text {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .emp-account-details {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 0.4rem;
            min-width: 0;
        }

        .emp-account-header-row {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: space-between;
        }

        .emp-account-name {
            color: var(--emp-text-primary);
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.25;
            margin: 0;
        }

        .emp-badge-status {
            align-items: center;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.24);
            border-radius: 9999px;
            color: #059669;
            display: inline-flex;
            font-size: 0.7rem;
            font-weight: 650;
            gap: 0.4rem;
            letter-spacing: 0.01em;
            padding: 0.2rem 0.65rem;
            white-space: nowrap;
        }

        .emp-status-dot {
            background-color: #10b981;
            border-radius: 9999px;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            display: inline-block;
            height: 6px;
            width: 6px;
        }

        .dark .emp-badge-status {
            background: rgba(16, 185, 129, 0.12);
            border-color: rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .dark .emp-status-dot {
            background-color: #34d399;
            box-shadow: 0 0 0 2px rgba(52, 211, 153, 0.25);
        }

        .emp-account-badges-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-top: 0.2rem;
        }

        .emp-account-badges-row {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        /* Modernized Minimalist Badges (Neutral, No Clashing Colors) */
        .emp-chip {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            color: #475569;
            display: inline-flex;
            font-size: 0.72rem;
            font-weight: 550;
            gap: 0.35rem;
            line-height: 1.25;
            padding: 0.22rem 0.65rem;
            transition: all 140ms ease;
            user-select: none;
        }

        .emp-chip svg {
            color: #64748b;
            flex-shrink: 0;
            height: 13px;
            width: 13px;
        }

        .emp-chip:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .emp-chip:hover svg {
            color: #334155;
        }

        .dark .emp-chip {
            background: #070b12 !important;
            border-color: #1e293b !important;
            color: #cbd5e1 !important;
        }

        .dark .emp-chip svg {
            color: #94a3b8 !important;
        }

        .dark .emp-chip:hover {
            background: #0c1424 !important;
            border-color: #3b82f6 !important;
            color: #f8fafc !important;
        }

        .dark .emp-chip:hover svg {
            color: #e2e8f0 !important;
        }

        .emp-chip.emp-chip-id {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* Modal Photo Preview */
        .emp-modal-photo-content {
            align-items: center;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 0.6rem 0;
            width: 100%;
        }

        .emp-modal-photo-frame {
            align-items: center;
            background: #0f172a;
            border: 4px solid var(--emp-surface);
            border-radius: 9999px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.08);
            display: flex;
            height: 190px;
            justify-content: center;
            overflow: hidden;
            width: 190px;
        }

        .dark .emp-modal-photo-frame {
            border-color: #1e293b;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
        }

        .emp-modal-photo-img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .emp-modal-initials-frame {
            align-items: center;
            background: var(--emp-blue-soft);
            border: 4px solid var(--emp-blue);
            border-radius: 9999px;
            display: flex;
            height: 170px;
            justify-content: center;
            width: 170px;
        }

        .emp-modal-initials {
            color: var(--emp-blue);
            font-size: 3.5rem;
            font-weight: 800;
        }

        .emp-modal-meta-info {
            align-items: center;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            text-align: center;
        }

        .emp-modal-meta-name {
            color: var(--emp-text-primary);
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .emp-modal-meta-sub {
            color: var(--emp-text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
        }

        .emp-modal-footer {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
            width: 100%;
        }


        /* --- Salary Cards Partial --- */
        .emp-salary-grid {
            display: grid;
            gap: 0.55rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            width: 100%;
        }

        .emp-stat-card {
            align-items: flex-start;
            background: var(--emp-surface);
            border: 1px solid var(--emp-border);
            border-radius: 0.55rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            display: flex;
            gap: 0.65rem;
            padding: 0.65rem 0.85rem;
            transition: all 120ms ease;
        }

        .emp-stat-card:hover {
            border-color: var(--emp-blue);
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .emp-stat-card.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .emp-stat-card {
            background: #000000 !important;
            border-color: #1e293b !important;
        }

        .dark .emp-stat-card:hover {
            background: #0c1424 !important;
            border-color: #3b82f6 !important;
        }

        .dark .emp-stat-card.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(30, 58, 138, 0.08)) !important;
            border-color: rgba(59, 130, 246, 0.35) !important;
        }

        .dark .emp-stat-icon {
            background: #070b12 !important;
            border-color: #1e293b !important;
        }

        .dark .emp-stat-value {
            color: #f8fafc !important;
        }

        .emp-stat-icon {
            align-items: center;
            background: var(--emp-surface-muted);
            border: 1px solid var(--emp-border-subtle);
            border-radius: 0.45rem;
            display: flex;
            flex-shrink: 0;
            justify-content: center;
            padding: 0.4rem;
        }

        .emp-stat-content {
            display: flex;
            flex-direction: column;
            gap: 0.12rem;
            min-width: 0;
        }

        .emp-stat-label {
            color: var(--emp-text-muted);
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .emp-stat-value {
            color: var(--emp-text-primary);
            font-size: 1.1rem;
            font-weight: 850;
            line-height: 1.2;
        }

        .emp-stat-sub {
            color: var(--emp-text-muted);
            font-size: 0.67rem;
        }

        .emp-stat-badge {
            align-items: center;
            border-radius: 9999px;
            display: inline-flex;
            font-size: 0.64rem;
            font-weight: 750;
            margin-top: 0.1rem;
            padding: 0.08rem 0.4rem;
            width: fit-content;
        }

        .emp-stat-badge.blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .dark .emp-stat-badge.blue {
            background: rgba(59, 130, 246, 0.25);
            color: #93c5fd;
        }

        /* --- Deductions Tab Partial --- */
        .emp-deductions-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            width: 100%;
        }

        .emp-deductions-kpis {
            display: grid;
            gap: 0.55rem;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            width: 100%;
        }

        .emp-deduction-kpi {
            background: var(--emp-surface);
            border: 1px solid var(--emp-border);
            border-radius: 0.55rem;
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            padding: 0.6rem 0.8rem;
        }

        .emp-deduction-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.3);
        }

        .dark .emp-deduction-kpi {
            background: #000000 !important;
            border-color: #1e293b !important;
        }

        .dark .emp-deduction-kpi:hover {
            background: #0c1424 !important;
            border-color: #3b82f6 !important;
        }

        .dark .emp-deduction-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(30, 58, 138, 0.08)) !important;
            border-color: rgba(59, 130, 246, 0.35) !important;
        }

        .dark .emp-kpi-value {
            color: #f8fafc !important;
        }

        .emp-kpi-label {
            color: var(--emp-text-muted);
            font-size: 0.64rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .emp-kpi-value {
            font-size: 1.1rem;
            font-weight: 850;
            line-height: 1.2;
        }

        .emp-kpi-sub {
            color: var(--emp-text-muted);
            font-size: 0.67rem;
        }

        .emp-table-card {
            background: var(--emp-surface);
            border: 1px solid var(--emp-border);
            border-radius: 0.55rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .dark .emp-table-card {
            background: #000000 !important;
            border-color: #1e293b !important;
        }

        .emp-table-header {
            align-items: center;
            background: var(--emp-surface-muted);
            border-bottom: 1px solid var(--emp-border-subtle);
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            padding: 0.55rem 0.85rem;
        }

        .dark .emp-table-header {
            background: #070b12 !important;
            border-bottom-color: #1e293b !important;
        }

        .emp-table-title {
            color: var(--emp-text-primary);
            font-size: 0.82rem;
            font-weight: 800;
            margin: 0;
        }

        .dark .emp-table-title {
            color: #f8fafc !important;
        }

        .emp-table-subtitle {
            color: var(--emp-text-muted);
            font-size: 0.69rem;
            margin: 0.12rem 0 0;
        }

        .emp-table-scroll {
            overflow-x: auto;
            width: 100%;
        }

        .emp-compact-table {
            border-collapse: collapse;
            font-size: 0.77rem;
            width: 100%;
        }

        .emp-compact-table th {
            background: var(--emp-surface-muted);
            border-bottom: 1px solid var(--emp-border);
            color: var(--emp-text-secondary);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            padding: 0.42rem 0.7rem;
            text-transform: uppercase;
        }

        .dark .emp-compact-table th {
            background: #070b12 !important;
            border-bottom-color: #1e293b !important;
            color: #93c5fd !important;
        }

        .emp-compact-table td {
            border-bottom-color: var(--emp-border-subtle);
            padding: 0.42rem 0.7rem;
            vertical-align: middle;
        }

        .dark .emp-compact-table td {
            border-bottom-color: #161f2e !important;
            color: #f8fafc !important;
        }

        .emp-compact-table tr:hover {
            background: rgba(37, 99, 235, 0.02);
        }

        .dark .emp-compact-table tr:hover {
            background: #0c1424 !important;
        }

        .dark .emp-compact-table tbody tr:nth-child(even) {
            background: #050811 !important;
        }

        .emp-item-cell {
            display: flex;
            flex-direction: column;
        }

        .emp-item-name {
            color: var(--emp-text-primary);
            font-weight: 750;
        }

        .emp-item-desc {
            color: var(--emp-text-muted);
            font-size: 0.67rem;
        }

        .emp-cat-badge {
            border-radius: 9999px;
            display: inline-flex;
            font-size: 0.67rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
        }

        .emp-cat-badge.blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .dark .emp-cat-badge.blue {
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }

        .emp-cat-badge.amber {
            background: #fef3c7;
            color: #b45309;
        }

        .dark .emp-cat-badge.amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .emp-cat-badge.slate {
            background: #f1f5f9;
            color: #334155;
        }

        .dark .emp-cat-badge.slate {
            background: rgba(51, 65, 85, 0.5);
            color: #cbd5e1;
        }

        .emp-cat-badge.purple {
            background: #ede9fe;
            color: #6d28d9;
        }

        .dark .emp-cat-badge.purple {
            background: rgba(139, 92, 246, 0.2);
            color: #c4b5fd;
        }

        .emp-amount {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.81rem;
            font-weight: 800;
        }

        .emp-amount.active-amount {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.35rem;
            color: #1d4ed8;
            padding: 0.12rem 0.42rem;
        }

        .dark .emp-amount.active-amount {
            background: rgba(30, 64, 175, 0.3);
            border-color: rgba(96, 165, 250, 0.4);
            color: #93c5fd;
        }

        .emp-amount.zero-amount {
            color: var(--emp-text-muted);
        }

        .emp-status-chip {
            align-items: center;
            border-radius: 9999px;
            display: inline-flex;
            font-size: 0.66rem;
            font-weight: 750;
            gap: 0.28rem;
            padding: 0.1rem 0.42rem;
        }

        .emp-status-chip.active {
            background: #dcfce7;
            color: #15803d;
        }

        .dark .emp-status-chip.active {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .emp-status-chip.muted {
            background: #f1f5f9;
            color: #64748b;
        }

        .dark .emp-status-chip.muted {
            background: rgba(51, 65, 85, 0.4);
            color: #94a3b8;
        }

        .emp-dot-green {
            background: #22c55e;
            border-radius: 9999px;
            height: 6px;
            width: 6px;
        }

        .emp-dot-gray {
            background: #94a3b8;
            border-radius: 9999px;
            height: 6px;
            width: 6px;
        }

        .emp-compact-table tfoot td {
            background: var(--emp-surface-muted);
            border-top: 2px solid var(--emp-border);
            padding: 0.5rem 0.7rem;
        }

        .dark .emp-compact-table tfoot td {
            background: #070b12 !important;
            border-top-color: #1e293b !important;
        }

        .emp-tfoot-label {
            color: var(--emp-text-primary);
            font-size: 0.71rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .dark .emp-tfoot-label {
            color: #f8fafc !important;
        }

        .emp-tfoot-total {
            color: #1d4ed8;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.92rem;
            font-weight: 850;
        }

        .dark .emp-tfoot-total {
            color: #93c5fd;
        }

        .emp-tfoot-sub {
            color: var(--emp-text-muted);
            font-size: 0.69rem;
            font-weight: 700;
        }

        .emp-empty-td {
            color: var(--emp-text-muted);
            padding: 1.25rem !important;
            text-align: center;
        }

        .employee-profile-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.5rem;
            padding-top: 0.35rem;
        }

        .employee-profile-actions .fi-btn {
            min-height: 2.15rem;
            font-size: 0.78rem;
            font-weight: 700;
        }

        @media (max-width: 640px) {
            /* Reduce page-level padding so fields can stretch wider */
            body:has(.employee-profile-form) .fi-main,
            .fi-main:has(.employee-profile-form) {
                padding-inline: 0.5rem !important;
            }

            .employee-profile-form {
                gap: 0.45rem !important;
                width: 100% !important;
            }

            .employee-profile-form .fi-tabs {
                padding: 0.2rem !important;
                gap: 0.15rem !important;
                border-radius: 0.45rem !important;
            }

            .employee-profile-form .fi-tabs-item {
                min-height: 2rem !important;
                padding: 0.25rem 0.45rem !important;
                font-size: 0.72rem !important;
                white-space: nowrap;
            }

            .employee-profile-form .fi-sc-tabs-tab.fi-active {
                gap: 0.45rem !important;
                margin-top: 0.35rem !important;
                padding-inline: 0 !important;
            }

            /* Reduced Section paddings & margins on mobile */
            .employee-profile-form :is(.fi-section, .fi-sc-section) {
                border-radius: 0.45rem !important;
                margin-inline: 0 !important;
            }

            .employee-profile-form :is(.fi-section-header, .fi-sc-section-header) {
                padding: 0.4rem 0.55rem !important;
            }

            .employee-profile-form :is(.fi-section-header-heading, .fi-sc-section-header-title) {
                font-size: 0.8rem !important;
            }

            .employee-profile-form :is(.fi-section-header-description, .fi-sc-section-header-description) {
                font-size: 0.67rem !important;
            }

            .employee-profile-form :is(.fi-section-content, .fi-sc-section-content),
            .employee-profile-form .fi-section > .fi-section-content-ctn > .fi-section-content {
                padding: 0.45rem 0.45rem !important;
                gap: 0.45rem !important;
            }

            /* Reduced Fieldset paddings & margins on mobile */
            .employee-profile-form :is(.fi-fieldset, .fi-sc-fieldset) {
                border-radius: 0.4rem !important;
                padding: 0.35rem 0.45rem 0.45rem !important;
                margin-inline: 0 !important;
                margin-top: 0.2rem !important;
                gap: 0.45rem !important;
            }

            .employee-profile-form :is(.fi-fieldset > legend, .fi-sc-fieldset-legend) {
                font-size: 0.65rem !important;
                padding-inline: 0.25rem !important;
                margin-bottom: 0.2rem !important;
            }

            /* Expand input fields wider with compact height */
            .employee-profile-form .fi-fo-field-wrp {
                width: 100% !important;
                min-width: 0 !important;
            }

            .employee-profile-form .fi-fo-field-wrp-label {
                font-size: 0.7rem !important;
                margin-bottom: 0.12rem !important;
            }

            .employee-profile-form .fi-input-wrp {
                min-height: 2.1rem !important;
                border-radius: 0.35rem !important;
            }

            .employee-profile-form .fi-input-wrp input,
            .employee-profile-form .fi-input-wrp select {
                padding: 0.3rem 0.45rem !important;
                font-size: 0.79rem !important;
            }

            .employee-profile-form .fi-input-wrp-prefix,
            .employee-profile-form .fi-input-wrp-suffix {
                padding-inline: 0.4rem !important;
            }

            .employee-profile-form textarea {
                padding: 0.35rem 0.45rem !important;
                font-size: 0.79rem !important;
            }

            /* Streamline avatar card on mobile */
            .emp-account-card {
                align-items: center;
                flex-direction: column;
                gap: 0.55rem;
                justify-content: center;
                padding: 0.85rem 0.65rem !important;
                text-align: center;
                border-radius: 0.5rem !important;
            }

            .emp-avatar-block {
                align-items: center;
                display: flex;
                justify-content: center;
                margin-inline: auto;
                width: 100%;
            }

            .emp-avatar-frame {
                height: 84px !important;
                width: 84px !important;
                min-height: 84px !important;
                min-width: 84px !important;
                max-height: 84px !important;
                max-width: 84px !important;
                margin-inline: auto;
                border-radius: 9999px !important;
                overflow: hidden !important;
            }

            .emp-avatar-initials {
                font-size: 1.65rem;
            }

            .emp-account-details {
                align-items: center;
                display: flex;
                flex-direction: column;
                justify-content: center;
                text-align: center;
                width: 100%;
                gap: 0.25rem !important;
            }

            .emp-account-header-row {
                align-items: center;
                justify-content: center;
                text-align: center;
                width: 100%;
            }

            .emp-account-name {
                font-size: 1.1rem;
                text-align: center;
            }

            /* On mobile view, hide the heavy badge cluster */
            .emp-badge-status,
            .emp-account-badges-group {
                display: none !important;
            }

            /* 2x2 Salary & Deductions Cards on Mobile */
            .emp-salary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.45rem !important;
            }

            .emp-salary-grid > :last-child:nth-child(odd) {
                grid-column: 1 / -1;
            }

            .emp-deductions-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.45rem !important;
            }

            .emp-stat-card {
                padding: 0.5rem 0.6rem !important;
                gap: 0.4rem !important;
            }

            .emp-stat-icon {
                padding: 0.3rem !important;
            }

            .emp-stat-label {
                font-size: 0.58rem !important;
                line-height: 1.15 !important;
            }

            .emp-stat-value {
                font-size: 0.92rem !important;
            }

            .emp-stat-sub {
                font-size: 0.6rem !important;
            }

            .emp-deduction-kpi {
                padding: 0.5rem 0.6rem !important;
            }

            .emp-kpi-label {
                font-size: 0.58rem !important;
                line-height: 1.15 !important;
            }

            .emp-kpi-value {
                font-size: 0.92rem !important;
            }

            .emp-kpi-sub {
                font-size: 0.6rem !important;
            }

            /* Deductions table card */
            .emp-table-card {
                border-radius: 0.45rem !important;
            }

            .emp-table-header {
                padding: 0.45rem 0.6rem !important;
            }

            .emp-table-title {
                font-size: 0.78rem !important;
            }

            .emp-compact-table th,
            .emp-compact-table td {
                padding: 0.35rem 0.5rem !important;
            }

            .employee-profile-actions {
                justify-content: stretch;
                padding-top: 0.25rem !important;
            }

            .employee-profile-actions .fi-btn {
                width: 100%;
            }
        }
    </style>

    <form wire:submit.prevent="save" class="employee-profile-form">
        {{ $this->form }}

        <div class="employee-profile-actions">
            <x-filament::button type="submit" icon="heroicon-m-check">
                Save Changes
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
