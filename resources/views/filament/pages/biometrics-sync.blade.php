<x-filament-panels::page>
    @php
        $isDetailsPage = $this instanceof \App\Filament\Pages\BiometricsSyncDetails;
        $devices = $isDetailsPage ? [] : $this->devices();
        $selected = collect($devices)->first(fn($d) => (string)$d['id'] === $this->device);
        $history = ! $isDetailsPage && $this->device ? $this->history() : collect();
    @endphp

    <style>
        /* Biometrics Workspace */
        .bio-workspace {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
            width: 100%;
        }

        /* Reference Page Header (matching system style from dashboard/dtr) */
        .bio-header-banner {
            background: linear-gradient(110deg, #1257b8 0%, #173c70 65%, #102a50 100%);
            border: 1px solid rgba(147, 197, 253, 0.35);
            border-radius: 12px;
            display: flex;
            align-items: center;
            flex-direction: row;
            gap: 1.25rem;
            justify-content: space-between;
            min-height: 4.75rem;
            padding: 1rem 1.35rem;
            width: 100%;
            box-shadow: 0 4px 14px -3px rgba(18, 87, 184, 0.28), 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .bio-header-copy {
            min-width: 0;
            flex: 1 1 auto;
        }

        .bio-header-eyebrow-wrap {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.2rem;
        }

        .bio-header-eyebrow {
            color: #93c5fd;
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .bio-header-title {
            color: #ffffff;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.2;
            margin: 0.1rem 0 0.25rem;
            letter-spacing: -0.015em;
        }

        .bio-header-description {
            color: #dbeafe;
            font-size: 0.8125rem;
            line-height: 1.4;
            margin: 0;
            max-width: 52rem;
        }

        .bio-header-actions {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
            flex: 0 0 auto;
        }

        .bio-header-btn {
            align-items: center;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 auto;
            gap: 0.4rem;
            height: 2.25rem;
            padding: 0 0.85rem;
            font-size: 0.785rem;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 150ms ease, border-color 150ms ease, transform 150ms ease, box-shadow 150ms ease;
            cursor: pointer;
            line-height: 1;
        }

        .bio-header-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.24);
            border-color: rgba(255, 255, 255, 0.5);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .bio-header-btn-primary {
            background: #2563eb;
            border-color: #60a5fa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .bio-header-btn-primary:hover:not(:disabled) {
            background: #1d4ed8;
            border-color: #93c5fd;
        }

        .bio-header-btn:disabled, .bio-header-btn[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Notice / Info Banner */
        .bio-info-card {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 1.15rem;
            border-radius: 0.65rem;
            background: rgba(30, 58, 138, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #cbd5e1;
        }
        .dark .bio-info-card {
            background: rgba(30, 58, 138, 0.18);
            border-color: rgba(59, 130, 246, 0.32);
            color: #cbd5e1;
        }
        .bio-info-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: #60a5fa;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
        .bio-info-desc {
            font-size: 0.8125rem;
            line-height: 1.45;
            margin: 0;
            color: inherit;
        }

        .bio-alert-card {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            padding: 0.85rem 1.15rem;
            border-radius: 0.65rem;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fbbf24;
        }
        .bio-alert-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: #f59e0b;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        /* Modern Device Cards Grid */
        .bio-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(295px, 1fr));
            gap: 1.15rem;
        }

        /* Modern Device Card */
        .bio-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            padding: 1.15rem 1.25rem;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            transition: all 180ms cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }

        .dark .bio-card {
            background: #0f172a;
            border-color: #334155;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.25);
        }

        .bio-card:hover {
            transform: translateY(-2px);
            border-color: #3b82f6;
            box-shadow: 0 10px 22px -4px rgba(18, 87, 184, 0.18), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .dark .bio-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.6), 0 0 16px -2px rgba(59, 130, 246, 0.25);
        }

        .bio-card.is-offline:hover {
            border-color: #ef4444;
            box-shadow: 0 10px 22px -4px rgba(220, 38, 38, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .dark .bio-card.is-offline:hover {
            border-color: #f87171;
            box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.6), 0 0 16px -2px rgba(239, 68, 68, 0.3);
        }

        .bio-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #94a3b8;
            transition: background 180ms ease, height 180ms ease;
        }

        .bio-card.is-online::before {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        }

        .bio-card.is-offline::before {
            background: #ef4444;
        }

        .bio-card:hover::before {
            height: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
        }

        .bio-card.is-offline:hover::before {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
        }

        /* Card Header */
        .bio-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .bio-device-identity {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .bio-device-icon-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 0.55rem;
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            flex-shrink: 0;
            transition: all 180ms ease;
        }

        .dark .bio-device-icon-wrap {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(96, 165, 250, 0.25);
            color: #60a5fa;
        }

        .bio-card:hover .bio-device-icon-wrap {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .bio-card.is-offline:hover .bio-device-icon-wrap {
            background: #dc2626;
            border-color: #dc2626;
        }

        .bio-device-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        .bio-device-titles {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .bio-device-name {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            letter-spacing: -0.01em;
        }

        .dark .bio-device-name {
            color: #f8fafc;
        }

        .bio-device-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.725rem;
            color: #64748b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .bio-device-code {
            color: #94a3b8;
        }

        /* Status Badges */
        .bio-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
            flex-shrink: 0;
            text-transform: capitalize;
        }

        .bio-status-badge.status-online {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .dark .bio-status-badge.status-online {
            background: rgba(16, 185, 129, 0.18);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.35);
        }

        .bio-status-badge.status-offline {
            background: rgba(220, 38, 38, 0.1);
            color: #b91c1c;
            border: 1px solid rgba(220, 38, 38, 0.28);
        }

        .dark .bio-status-badge.status-offline {
            background: rgba(239, 68, 68, 0.16);
            color: #fca5a5;
            border-color: rgba(248, 113, 113, 0.36);
        }

        .bio-status-dot {
            position: relative;
            display: inline-block;
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 9999px;
        }

        .status-online .bio-status-dot {
            background: #10b981;
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.7);
        }

        .status-offline .bio-status-dot {
            background: #ef4444;
        }

        /* Firmware Tag */
        .bio-firmware-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 0.45rem;
            font-size: 0.725rem;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            margin-bottom: 0.85rem;
            width: fit-content;
            max-width: 100%;
        }

        .dark .bio-firmware-pill {
            color: #cbd5e1;
            background: #1e293b;
            border-color: #334155;
        }

        .bio-firmware-icon {
            width: 0.85rem;
            height: 0.85rem;
            color: #64748b;
        }

        .dark .bio-firmware-icon {
            color: #94a3b8;
        }

        .bio-firmware-divider {
            color: #94a3b8;
        }

        .bio-firmware-ver {
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-weight: 600;
            color: #2563eb;
        }

        .dark .bio-firmware-ver {
            color: #93c5fd;
        }

        /* Metrics List */
        .bio-metrics {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            padding: 0.75rem 0;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 0.75rem;
        }

        .dark .bio-metrics {
            border-top-color: #1e293b;
            border-bottom-color: #1e293b;
        }

        .bio-metric-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 0.75rem;
        }

        .bio-metric-label {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: #64748b;
            font-weight: 600;
            flex-shrink: 0;
        }

        .dark .bio-metric-label {
            color: #94a3b8;
        }

        .bio-metric-icon {
            width: 0.85rem;
            height: 0.85rem;
        }

        .bio-metric-content {
            text-align: right;
            min-width: 0;
        }

        .bio-metric-val {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.75rem;
        }

        .dark .bio-metric-val {
            color: #f1f5f9;
        }

        .bio-metric-val.text-muted {
            color: #94a3b8;
            font-weight: 500;
        }

        .bio-metric-sub {
            display: block;
            font-size: 0.6875rem;
            color: #94a3b8;
            line-height: 1.2;
        }

        .dark .bio-metric-sub {
            color: #64748b;
        }

        /* Sync Status Pill */
        .bio-sync-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.45rem;
            border-radius: 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .bio-sync-pill.sync-success {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }
        .dark .bio-sync-pill.sync-success {
            background: rgba(16, 185, 129, 0.18);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.35);
        }

        .bio-sync-pill.sync-failed {
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }
        .dark .bio-sync-pill.sync-failed {
            background: rgba(239, 68, 68, 0.18);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.35);
        }

        .bio-sync-pill.sync-pending, .bio-sync-pill.sync-running {
            background: rgba(245, 158, 11, 0.12);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }
        .dark .bio-sync-pill.sync-pending, .dark .bio-sync-pill.sync-running {
            background: rgba(245, 158, 11, 0.18);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.35);
        }

        .bio-sync-dot {
            width: 0.35rem;
            height: 0.35rem;
            border-radius: 9999px;
        }
        .sync-success .bio-sync-dot { background: #10b981; }
        .sync-failed .bio-sync-dot { background: #ef4444; }
        .sync-pending .bio-sync-dot, .sync-running .bio-sync-dot { background: #f59e0b; }

        /* Card Footer */
        .bio-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #3b82f6;
        }

        .dark .bio-card-footer {
            color: #60a5fa;
        }

        .bio-footer-arrow {
            width: 0.875rem;
            height: 0.875rem;
            transition: transform 180ms ease;
        }

        .bio-card:hover .bio-footer-arrow {
            transform: translateX(3px);
        }

        .bio-card.is-offline:hover .bio-card-footer {
            color: #dc2626;
        }

        .dark .bio-card.is-offline:hover .bio-card-footer {
            color: #fca5a5;
        }

        /* Empty State Card */
        .bio-empty-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
            border: 1px dashed #cbd5e1;
            border-radius: 0.875rem;
            background: rgba(241, 245, 249, 0.5);
        }
        .dark .bio-empty-card {
            border-color: #334155;
            background: rgba(15, 23, 42, 0.5);
        }
        .bio-empty-icon {
            width: 2.5rem;
            height: 2.5rem;
            color: #94a3b8;
            margin-bottom: 0.75rem;
        }
        .bio-empty-text {
            color: #64748b;
            font-size: 0.875rem;
            margin: 0;
        }

        /* Selected Device Banner / Card */
        .bio-selected-device-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            margin-bottom: 1.15rem;
        }
        .dark .bio-selected-device-card {
            background: #0f172a;
            border-color: #334155;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.25);
        }

        .bio-selected-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
        }

        .bio-selected-tile {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .bio-selected-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .dark .bio-selected-label {
            color: #94a3b8;
        }

        .bio-selected-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
        }
        .dark .bio-selected-value {
            color: #f8fafc;
        }

        .bio-selected-latest-sync {
            margin-top: 1rem;
            padding-top: 0.85rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .dark .bio-selected-latest-sync {
            border-top-color: #1e293b;
        }

        .bio-selected-sync-row {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .bio-selected-sync-msg {
            font-size: 0.8125rem;
            color: #64748b;
        }
        .dark .bio-selected-sync-msg {
            color: #94a3b8;
        }

        /* History Table */
        .bio-table-wrapper {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            background: #ffffff;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }
        .dark .bio-table-wrapper {
            border-color: #334155;
            background: #0f172a;
        }
        .bio-table {
            width: 100%;
            min-width: 1000px;
            text-align: left;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }
        .bio-table thead th {
            padding: 0.75rem 1rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .bio-table thead th {
            color: #94a3b8;
            background: #1e293b;
            border-bottom-color: #334155;
        }
        .bio-table tbody td {
            padding: 0.85rem 1rem;
            border-top: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .dark .bio-table tbody td {
            border-top-color: #1e293b;
        }
        .bio-table tbody tr:hover {
            background: rgba(248, 250, 252, 0.8);
        }
        .dark .bio-table tbody tr:hover {
            background: rgba(30, 41, 59, 0.45);
        }

        .bio-counts-list {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            font-size: 0.75rem;
            line-height: 1.35;
        }

        .bio-details-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.45rem;
            color: #3b82f6;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
            cursor: pointer;
            transition: color 150ms ease;
        }
        .bio-details-btn:hover {
            color: #1d4ed8;
        }
        .dark .bio-details-btn {
            color: #93c5fd;
        }
        .dark .bio-details-btn:hover {
            color: #bfdbfe;
        }

        /* Synchronization Details Modal */
        .bio-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .bio-modal-overlay {
            position: absolute;
            inset: 0;
            background: rgba(3, 7, 18, 0.78);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        .bio-modal-dialog {
            position: relative;
            display: flex;
            flex-direction: column;
            width: min(1120px, 100%);
            max-height: 92vh;
            border-radius: 1rem;
            background: #0f172a;
            border: 1px solid #334155;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.05);
            color: #f8fafc;
            overflow: hidden;
        }

        .bio-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.15rem 1.5rem;
            border-bottom: 1px solid #1e293b;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }

        .bio-modal-header-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .bio-modal-header-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.65rem;
            height: 2.65rem;
            border-radius: 0.65rem;
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            flex-shrink: 0;
        }

        .bio-modal-eyebrow {
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #60a5fa;
            line-height: 1.2;
            margin-bottom: 0.15rem;
        }

        .bio-modal-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.015em;
        }

        .bio-modal-subtitle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.35rem;
            font-size: 0.785rem;
            color: #cbd5e1;
        }

        .bio-modal-close-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #94a3b8;
            cursor: pointer;
            transition: all 150ms ease;
            flex-shrink: 0;
        }

        .bio-modal-close-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.35);
            color: #f87171;
            transform: scale(1.05);
        }

        .bio-modal-tabs-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 1.5rem;
            border-bottom: 1px solid #1e293b;
            background: #0f172a;
            flex-wrap: wrap;
        }

        .bio-modal-tab-group {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem;
            border-radius: 0.65rem;
            background: #1e293b;
            border: 1px solid #334155;
        }

        .bio-modal-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.95rem;
            border-radius: 0.5rem;
            font-size: 0.785rem;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 150ms ease;
            color: #94a3b8;
            background: transparent;
        }

        .bio-modal-tab-btn:hover {
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.05);
        }

        .bio-modal-tab-btn.is-active {
            background: #2563eb;
            color: #ffffff;
            font-weight: 700;
            border-color: #3b82f6;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
        }

        .bio-modal-body {
            overflow-y: auto;
            min-height: 0;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .bio-dtr-table-scroll {
            max-height: min(56vh, 620px);
            overflow: auto;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
            border: 1px solid #334155;
            border-radius: 0.75rem;
        }

        .bio-dtr-table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #1e293b;
        }

        .bio-dtr-table .bio-dtr-value {
            font-variant-numeric: tabular-nums;
            color: #e2e8f0;
        }

        .bio-dtr-table th[class^="bio-dtr-heading--"] { color: #94a3b8; }

        .bio-dtr-table .bio-dtr-heading--schedule,
        .bio-dtr-table .bio-dtr-value--schedule { color: #93c5fd; }

        .bio-dtr-table .bio-dtr-heading--shift,
        .bio-dtr-table .bio-dtr-value--shift { color: #c4b5fd; }

        .bio-dtr-table .bio-dtr-heading--late,
        .bio-dtr-table .bio-dtr-value--late,
        .bio-dtr-table .bio-dtr-value--pending { color: #fbbf24; }

        .bio-dtr-table .bio-dtr-heading--undertime,
        .bio-dtr-table .bio-dtr-value--undertime,
        .bio-dtr-table .bio-dtr-value--rejected { color: #fb7185; }

        .bio-dtr-table .bio-dtr-heading--overtime,
        .bio-dtr-table .bio-dtr-value--overtime { color: #67e8f9; }

        .bio-dtr-table .bio-dtr-heading--station,
        .bio-dtr-table .bio-dtr-value--station { color: #5eead4; }

        .bio-dtr-table .bio-dtr-value--approved { color: #4ade80; }

        .bio-dtr-table .bio-dtr-value--late,
        .bio-dtr-table .bio-dtr-value--undertime,
        .bio-dtr-table .bio-dtr-value--overtime,
        .bio-dtr-table .bio-dtr-value--approved,
        .bio-dtr-table .bio-dtr-value--pending,
        .bio-dtr-table .bio-dtr-value--rejected { font-weight: 700; }

        .bio-dtr-table .bio-dtr-value--muted { color: #94a3b8; font-weight: 400; }

        @media (max-width: 640px) {
            .bio-dtr-table-scroll {
                max-height: 54vh;
            }
        }

        .bio-modal-result-box {
            padding: 1.15rem 1.25rem;
            border-radius: 0.75rem;
            background: #1e293b;
            border: 1px solid #334155;
            overflow-wrap: anywhere;
        }

        .bio-modal-result-box.result-failed {
            border-color: rgba(239, 68, 68, 0.35);
            background: linear-gradient(180deg, rgba(239, 68, 68, 0.08) 0%, rgba(30, 41, 59, 0.6) 100%);
        }

        .bio-modal-result-box.result-success {
            border-color: rgba(16, 185, 129, 0.35);
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.08) 0%, rgba(30, 41, 59, 0.6) 100%);
        }

        .bio-modal-result-box.result-pending {
            border-color: rgba(245, 158, 11, 0.35);
            background: linear-gradient(180deg, rgba(245, 158, 11, 0.08) 0%, rgba(30, 41, 59, 0.6) 100%);
        }

        .bio-modal-result-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .bio-modal-result-title {
            font-size: 0.9375rem;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }

        .bio-modal-result-text {
            color: #cbd5e1;
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0;
        }

        .bio-modal-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            padding: 0.75rem 0.95rem;
            border-radius: 0.55rem;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fde68a;
            font-size: 0.8125rem;
            line-height: 1.45;
            margin-top: 0.85rem;
        }

        /* Stat Cards */
        .bio-modal-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
        }

        .bio-modal-stat-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.35rem;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            background: #1e293b;
            border: 1px solid #334155;
            overflow: hidden;
            transition: transform 150ms ease, border-color 150ms ease;
        }

        .bio-modal-stat-card:hover {
            transform: translateY(-1px);
        }

        .bio-modal-stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card-imported::before { background: linear-gradient(90deg, #10b981, #059669); }
        .stat-card-skipped::before { background: #64748b; }
        .stat-card-invalid::before { background: linear-gradient(90deg, #ef4444, #dc2626); }
        .stat-card-pending::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .stat-card-review::before { background: linear-gradient(90deg, #3b82f6, #2563eb); }

        .bio-modal-stat-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
        }

        .bio-modal-stat-val {
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .stat-card-imported .bio-modal-stat-val { color: #34d399; }
        .stat-card-skipped .bio-modal-stat-val { color: #cbd5e1; }
        .stat-card-invalid .bio-modal-stat-val { color: #f87171; }
        .stat-card-pending .bio-modal-stat-val { color: #fbbf24; }
        .stat-card-review .bio-modal-stat-val { color: #60a5fa; }

        /* Panels for Request Info & Export */
        .bio-modal-panel {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.75rem;
            padding: 1.15rem 1.25rem;
        }

        .bio-modal-panel-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.85rem;
        }

        .bio-modal-panel-title {
            font-size: 0.8125rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #cbd5e1;
            margin: 0;
        }

        .bio-modal-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 0.85rem 1.25rem;
        }

        .bio-modal-info-item dt {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 0.2rem;
        }

        .bio-modal-info-item dd {
            font-size: 0.8125rem;
            color: #f1f5f9;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .bio-modal-info-badge {
            display: inline-block;
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 0.75rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 0.35rem;
            padding: 0.15rem 0.45rem;
            color: #93c5fd;
        }

        /* Modal Footer */
        .bio-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1.5rem;
            border-top: 1px solid #1e293b;
            background: #0f172a;
            gap: 1rem;
        }

        .bio-modal-dialog-sm {
            width: min(500px, 100%) !important;
            max-height: 90vh !important;
        }

        .bio-modal-field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .bio-modal-label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #f1f5f9;
        }

        .bio-modal-select {
            width: 100%;
            padding: 0.65rem 0.85rem;
            font-size: 0.875rem;
            border-radius: 0.6rem;
            border: 1px solid #334155;
            background: #1e293b;
            color: #f8fafc;
            outline: none;
            transition: all 150ms ease;
        }

        .bio-modal-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }

        .bio-detail-page .bio-modal-dialog {
            width: 100%;
            max-height: none;
            overflow: visible;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .bio-detail-page .bio-modal-header {
            flex-wrap: wrap;
            margin-bottom: 1rem;
            border: 1px solid #3b82f6;
            border-radius: 12px;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            padding: 1rem 1.35rem;
        }

        .bio-detail-page .bio-modal-eyebrow {
            border: 0;
            background: transparent;
            color: #d9f722;
            padding: 0;
        }

        .bio-detail-page .bio-modal-body {
            overflow: visible;
            padding: 0;
        }

        .bio-detail-page .bio-modal-tabs-bar {
            margin-bottom: 1rem;
            border: 1px solid #334155;
            border-radius: 8px;
        }

        @media (max-width: 640px) {
            .bio-detail-page .bio-header-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    @unless($isDetailsPage)
    <div wire:poll.10s class="bio-workspace">
        {{-- Reference Header from Image 2 --}}
        <header class="bio-header-banner">
            <div class="bio-header-copy">
                <div class="bio-header-eyebrow-wrap">
                    <span class="bio-header-eyebrow">{{ $this->device ? 'Device Management' : 'Biometrics Synchronization' }}</span>
                </div>
                <h2 class="bio-header-title">{{ $this->device ? ($selected['name'] ?? 'Device '.$this->device) : 'Biometrics Devices' }}</h2>
                <p class="bio-header-description">{{ $this->canSyncEmployees() ? 'Payroll DTR and device employee synchronization' : 'Payroll DTR synchronization' }} · Processing continues after you leave this page.</p>
            </div>
            <div class="bio-header-actions">
                <a href="{{ $this->device ? static::getUrl() : $this->returnUrl() }}" class="bio-header-btn">
                    <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" />
                    <span>Return</span>
                </a>
                @if($this->device)
                    <button type="button" wire:click="openSync" @disabled($this->active() || !$selected || !$selected['online']) class="bio-header-btn bio-header-btn-primary">
                        <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" />
                        <span>Sync Device</span>
                    </button>
                    @if($this->canSyncEmployees())
                        <button type="button" wire:click="openEmployeeSync" @disabled($this->active() || !$selected || !$selected['online']) class="bio-header-btn bio-header-btn-primary">
                            <x-filament::icon icon="heroicon-m-users" class="h-4 w-4" />
                            <span>Sync Employee</span>
                        </button>
                    @endif
                @endif
            </div>
        </header>


        @if($this->connectionError)
            <div class="bio-alert-card" role="alert">
                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="bio-alert-icon" />
                <p class="bio-info-desc">{{ $this->connectionError }}</p>
            </div>
        @endif

        @if($this->device && $selected && $this->active())
            <div class="bio-alert-card" role="status" aria-live="polite">
                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="bio-alert-icon" />
                <p class="bio-info-desc">{{ \App\Services\Biometrics\BiometricsSyncService::DEVICE_UNAVAILABLE_MESSAGE }}</p>
            </div>
        @endif

        @if(!$this->device)
            {{-- Device Cards Grid --}}
            <div class="bio-cards-grid">
                @forelse($devices as $item)
                    @php
                        $lastSeen = !empty($item['last_seen']) ? rescue(fn () => \Illuminate\Support\Carbon::parse($item['last_seen']), null) : null;
                        $latest = $this->latestSyncForDevice((string) $item['id']);
                        $isOnline = !empty($item['online']);
                    @endphp
                    <a href="{{ static::getUrl(['device' => (string)$item['id']]) }}" class="bio-card {{ $isOnline ? 'is-online' : 'is-offline' }}">
                        <div>
                            <div class="bio-card-header">
                                <div class="bio-device-identity">
                                    <div class="bio-device-icon-wrap">
                                        <x-filament::icon icon="heroicon-m-finger-print" class="bio-device-icon" />
                                    </div>
                                    <div class="bio-device-titles">
                                        <h3 class="bio-device-name" title="{{ $item['name'] }}">{{ $item['name'] }}</h3>
                                        <span class="bio-device-code">{{ $item['code'] }}</span>
                                    </div>
                                </div>
                                <span class="bio-status-badge {{ $isOnline ? 'status-online' : 'status-offline' }}">
                                    <span class="bio-status-dot"></span>
                                    <span>{{ $isOnline ? 'Online' : 'Offline' }}</span>
                                </span>
                            </div>

                            <div class="bio-firmware-pill">
                                <x-filament::icon icon="heroicon-m-cpu-chip" class="bio-firmware-icon" />
                                <span class="bio-firmware-type">{{ ucwords(str_replace('_', ' ', $item['firmware_type'] ?? 'Unknown firmware')) }}</span>
                                <span class="bio-firmware-divider">·</span>
                                <span class="bio-firmware-ver">{{ $item['firmware_version'] ?? 'Not reported' }}</span>
                            </div>

                            <div class="bio-metrics">
                                <div class="bio-metric-item">
                                    <span class="bio-metric-label">
                                        <x-filament::icon icon="heroicon-m-clock" class="bio-metric-icon" />
                                        <span>Last seen:</span>
                                    </span>
                                    <div class="bio-metric-content">
                                        @if($lastSeen)
                                            <span class="bio-metric-val">{{ $lastSeen->format('M d, Y · h:i A') }}</span>
                                            <span class="bio-metric-sub">{{ $lastSeen->diffForHumans() }}</span>
                                        @else
                                            <span class="bio-metric-val text-muted">{{ $item['last_seen'] ?? 'Never' }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="bio-metric-item">
                                    <span class="bio-metric-label">
                                        <x-filament::icon icon="heroicon-m-arrow-path" class="bio-metric-icon" />
                                        <span>Latest sync:</span>
                                    </span>
                                    <div class="bio-metric-content">
                                        @if($latest)
                                            @php $syncStatus = strtolower($latest->status); @endphp
                                            <span class="bio-sync-pill sync-{{ $syncStatus }}">
                                                <span class="bio-sync-dot"></span>
                                                <span>{{ ucfirst($latest->status) }}</span>
                                                <span class="bio-sync-time">· {{ $latest->created_at->format('M d H:i') }}</span>
                                            </span>
                                        @else
                                            <span class="bio-metric-val text-muted">No synchronization yet</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bio-card-footer">
                            <span class="bio-footer-text">View history & sync</span>
                            <x-filament::icon icon="heroicon-m-chevron-right" class="bio-footer-arrow" />
                        </div>
                    </a>
                @empty
                    <div class="bio-empty-card" style="grid-column: 1 / -1;">
                        <x-filament::icon icon="heroicon-o-cpu-chip" class="bio-empty-icon" />
                        <p class="bio-empty-text">{{ $this->canSyncEmployees() ? 'No devices available.' : 'No biometrics devices are assigned to your active approval workflows.' }}</p>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Selected Device Overview --}}
            @if($selected)
                @php
                    $selLastSeen = !empty($selected['last_seen']) ? rescue(fn () => \Illuminate\Support\Carbon::parse($selected['last_seen']), null) : null;
                    $latestRecord = $history->first();
                    $selOnline = !empty($selected['online']);
                @endphp
                <div class="bio-selected-device-card">
                    <div class="bio-selected-grid">
                        <div class="bio-selected-tile">
                            <span class="bio-selected-label">Device Code</span>
                            <span class="bio-selected-value font-mono">{{ $selected['code'] }}</span>
                        </div>
                        <div class="bio-selected-tile">
                            <span class="bio-selected-label">Hardware & Firmware</span>
                            <span class="bio-selected-value">
                                {{ ucwords(str_replace('_', ' ', $selected['firmware_type'] ?? 'Unknown firmware')) }} · {{ $selected['firmware_version'] ?? 'Not reported' }}
                            </span>
                        </div>
                        <div class="bio-selected-tile">
                            <span class="bio-selected-label">Connection Status</span>
                            <div style="margin-top: 0.2rem;">
                                <span class="bio-status-badge {{ $selOnline ? 'status-online' : 'status-offline' }}">
                                    <span class="bio-status-dot"></span>
                                    <span>{{ $selOnline ? 'Online' : 'Offline' }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="bio-selected-tile">
                            <span class="bio-selected-label">Last seen:</span>
                            <span class="bio-selected-value">
                                @if($selLastSeen)
                                    {{ $selLastSeen->format('M d, Y · h:i A') }}
                                    <span class="bio-metric-sub">({{ $selLastSeen->diffForHumans() }})</span>
                                @else
                                    {{ $selected['last_seen'] ?? 'Never' }}
                                @endif
                            </span>
                        </div>
                    </div>
                    @if($latestRecord)
                        <div class="bio-selected-latest-sync">
                            <span class="bio-selected-label">Latest sync:</span>
                            <div class="bio-selected-sync-row">
                                @php $sStatus = strtolower($latestRecord->status); @endphp
                                <span class="bio-sync-pill sync-{{ $sStatus }}">
                                    <span class="bio-sync-dot"></span>
                                    <span>{{ ucfirst($latestRecord->status) }}</span>
                                </span>
                                <span class="bio-selected-sync-msg">— {{ $latestRecord->message }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- History Table --}}
            <div class="bio-table-wrapper">
                <table class="bio-table">
                    <thead>
                        <tr>
                            @foreach(['Requested','Requested By','Command / Period','Status','Counts','Message','Received','Executed','Completed'] as $heading)
                                <th>{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $sync)
                            <tr>
                                <td>
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $sync->created_at->format('M d, Y') }}</div>
                                    <div class="font-mono text-xs text-slate-500">{{ $sync->created_at->format('H:i:s') }}</div>
                                </td>
                                <td>
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $sync->requestedBy?->name ?: ($sync->requestedBy?->employee?->full_name ?? 'Account unavailable') }}</div>
                                    @if($sync->requestedBy?->role)
                                        <div class="text-xs text-slate-500">{{ $sync->requestedBy->username }} · {{ strtoupper($sync->requestedBy->role) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($sync->sync_type === 'employee')
                                        <span class="font-bold text-slate-900 dark:text-slate-100">Device Employees</span>
                                        <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold">Registered employee snapshot</div>
                                    @else
                                        <span class="font-bold text-slate-900 dark:text-slate-100">DTR Payroll</span>
                                        <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold">{{ $sync->period?->title }}</div>
                                        <small class="text-slate-500">{{ $sync->date_from->toDateString() }} – {{ $sync->date_to->toDateString() }}</small>
                                    @endif
                                </td>
                                <td>
                                    <x-filament::badge :color="match($sync->status){'success'=>'success','failed'=>'danger',default=>'warning'}">
                                        {{ ucfirst($sync->status) }}
                                    </x-filament::badge>
                                </td>
                                <td>
                                    @if($sync->sync_type === 'employee')
                                        <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ isset($sync->result['employee_count']) ? $sync->result['employee_count'].' employees' : 'Awaiting device list' }}</span>
                                    @else
                                    <div class="bio-counts-list">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $sync->result['successful'] ?? 0 }} imported</span>
                                        <span class="text-slate-500">
                                            {{ $sync->result['skipped'] ?? 0 }} skipped
                                            @if(($sync->result['inactive_skipped'] ?? 0) > 0)
                                                · {{ $sync->result['inactive_skipped'] }} inactive
                                            @endif
                                            @if(($sync->result['unmatched_skipped'] ?? 0) > 0)
                                                · {{ $sync->result['unmatched_skipped'] }} unmatched fingerprint
                                            @endif
                                            @if(($sync->result['missing_fingerprint_skipped'] ?? 0) > 0)
                                                · {{ $sync->result['missing_fingerprint_skipped'] }} missing fingerprint
                                            @endif
                                        </span>
                                        <span class="text-rose-600 dark:text-rose-400 font-semibold">{{ $sync->result['failed'] ?? 0 }} invalid</span>
                                        <span class="text-amber-600 dark:text-amber-400">{{ $sync->result['pending'] ?? 0 }} pending</span>
                                        <span class="text-blue-600 dark:text-blue-400">{{ $sync->result['conflicts'] ?? 0 }} for review</span>
                                    </div>
                                    @endif
                                </td>
                                <td style="max-width:320px;overflow-wrap:anywhere">
                                    <div class="text-slate-700 dark:text-slate-300">{{ \Illuminate\Support\Str::limit((string) $sync->message, 150) }}</div>
                                    <a href="{{ $this->detailsUrl((string) $sync->id) }}" class="bio-details-btn">
                                        <x-filament::icon icon="heroicon-m-document-magnifying-glass" class="w-3.5 h-3.5" />
                                        <span>View details</span>
                                    </a>
                                </td>
                                @foreach([$sync->received_at,$sync->executed_at,$sync->completed_at] as $time)
                                    <td class="font-mono text-xs text-slate-600 dark:text-slate-400">{{ $time?->format('M d H:i:s') ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-6 text-slate-500">
                                    No synchronization requests yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endunless

    {{-- Synchronization details page --}}
    @if($isDetailsPage && ($detailSync = $this->detailSync()))
        @php
            $detailResult = $detailSync->result ?? [];
            $detailErrors = $detailResult['errors'] ?? [];
            $detailNotices = $detailResult['notices'] ?? [];
            $detailIssues = collect($detailErrors)->map(fn($issue) => ['row' => (int) ($issue['row'] ?? 0), 'kind' => 'Error', 'message' => $issue['message'] ?? 'No details provided.'])
                ->concat(collect($detailNotices)->map(fn($issue) => ['row' => (int) ($issue['row'] ?? 0), 'kind' => 'Notice', 'message' => $issue['message'] ?? 'No details provided.']))
                ->groupBy('row');
            $hasSkippedExportRowMap = ! empty(array_merge(
                $detailResult['inactive_skipped_rows'] ?? [],
                $detailResult['unmatched_fingerprint_skipped_rows'] ?? [],
                $detailResult['missing_fingerprint_skipped_rows'] ?? [],
                $detailResult['import_skipped_export_rows'] ?? [],
                array_keys($detailResult['skipped_row_reasons'] ?? []),
            ));
            $availableRowDetails = array_replace($detailResult['row_details'] ?? [], $this->archivedRowDetails);
            $needsArchivedRows = $detailIssues->keys()->contains(fn($row) => (int) $row > 0 && !array_key_exists((int) $row, $availableRowDetails));
            $allFingerprintsUnmatched = $detailSync->status === 'failed' && count($detailErrors) > 0
                && count($detailErrors) === ($detailResult['total'] ?? 0)
                && collect($detailErrors)->every(fn($error) => str_contains((string) ($error['message'] ?? ''), 'does not match an active employee with a branch'));
            $isFailed = $detailSync->status === 'failed';
            $isSuccess = $detailSync->status === 'success';
        @endphp
        <div wire:poll.10s class="bio-detail-page">
            <div class="bio-modal-dialog">
                <div class="bio-modal-header">
                    <div class="bio-modal-header-left">
                        <div class="bio-modal-header-icon">
                            <x-filament::icon icon="heroicon-m-document-magnifying-glass" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="bio-modal-eyebrow">Synchronization Audit</div>
                            <h3 id="sync-detail-title" class="bio-modal-title">Synchronization details</h3>
                            <div class="bio-modal-subtitle">
                                <span class="bio-modal-info-badge">
                                    {{ $detailSync->device_name }}
                                </span>
                                <span>·</span>
                                <span>{{ $detailSync->sync_type === 'employee' ? 'Device employee snapshot' : ($detailSync->period?->title ?? 'Payroll period unavailable') }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ $this->detailsReturnUrl() }}" class="bio-header-btn">
                        <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" />
                        <span>Return to device</span>
                    </a>
                </div>

                @if($detailSync->sync_type === 'employee')
                    <div class="bio-modal-body">
                        <section class="bio-modal-result-box {{ $isFailed ? 'result-failed' : ($isSuccess ? 'result-success' : 'result-pending') }}">
                            <div class="bio-modal-result-header">
                                <x-filament::icon icon="heroicon-m-users" class="w-4 h-4" />
                                <h4 class="bio-modal-result-title">Device employees · {{ ucfirst($detailSync->status) }}</h4>
                            </div>
                            <p class="bio-modal-result-text">{{ $detailSync->message ?: 'Waiting for device employee export.' }}</p>
                        </section>
                        <section class="bio-modal-panel">
                            <div class="bio-modal-panel-header">
                                <x-filament::icon icon="heroicon-m-table-cells" class="w-4 h-4 text-blue-400" />
                                <h4 class="bio-modal-panel-title">Registered employees ({{ $detailResult['employee_count'] ?? 0 }})</h4>
                            </div>
                            @if($detailSync->status === 'success')
                                <label for="employee-snapshot-search" class="bio-modal-label">Search device employees</label>
                                <input id="employee-snapshot-search" type="search" wire:model.live.debounce.250ms="employeeSearch" class="bio-modal-select" placeholder="Name, UID, fingerprint ID, schedule…" style="margin:.4rem 0 1rem" />
                                <div style="overflow-x:auto">
                                    <table class="bio-table">
                                        <thead><tr><th>Device ID</th><th>UID</th><th>Name</th><th>Type</th><th>Schedule</th><th>Fingerprint IDs</th><th>Count</th><th>Created</th></tr></thead>
                                        <tbody>
                                            @forelse($this->employeeRows() as $employee)
                                                <tr>
                                                    <td>{{ $employee['id'] }}</td>
                                                    <td>{{ $employee['uid'] }}</td>
                                                    <td>{{ $employee['name'] }}</td>
                                                    <td>{{ $employee['type'] }}</td>
                                                    <td>{{ $employee['schedule'] }}</td>
                                                    <td>{{ implode(', ', $employee['fingerprint_ids'] ?? []) ?: 'None' }}</td>
                                                    <td>{{ $employee['fingerprint_count'] ?? 0 }}</td>
                                                    <td>{{ $employee['created'] ?: 'Unknown' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8">{{ $this->employeeSearch === '' ? 'No employees are registered on this device.' : 'No device employees match this search.' }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>
                        <section class="bio-modal-panel">
                            <div class="bio-modal-panel-header"><h4 class="bio-modal-panel-title">Request information</h4></div>
                            <dl class="bio-modal-info-grid">
                                <div class="bio-modal-info-item"><dt>Request ID</dt><dd>{{ $detailSync->id }}</dd></div>
                                <div class="bio-modal-info-item"><dt>Command ID</dt><dd>{{ $detailSync->command_id ?? 'Not sent' }}</dd></div>
                                <div class="bio-modal-info-item"><dt>Requested</dt><dd>{{ $detailSync->created_at?->format('M d, Y H:i:s') ?? '—' }}</dd></div>
                                <div class="bio-modal-info-item"><dt>Completed</dt><dd>{{ $detailSync->completed_at?->format('M d, Y H:i:s') ?? '—' }}</dd></div>
                            </dl>
                        </section>
                    </div>
                @else
                <div class="bio-modal-tabs-bar">
                    <div role="tablist" aria-label="Synchronization detail sections" class="bio-modal-tab-group">
                        <button type="button" role="tab" aria-selected="{{ $this->detailTab === 'overview' ? 'true' : 'false' }}" wire:click="selectDetailTab('overview')" class="bio-modal-tab-btn {{ $this->detailTab === 'overview' ? 'is-active' : '' }}">
                            <x-filament::icon icon="heroicon-m-chart-pie" class="w-3.5 h-3.5" />
                            <span>Overview</span>
                        </button>
                        <button type="button" role="tab" aria-selected="{{ $this->detailTab === 'entries' ? 'true' : 'false' }}" wire:click="selectDetailTab('entries')" class="bio-modal-tab-btn {{ $this->detailTab === 'entries' ? 'is-active' : '' }}">
                            <x-filament::icon icon="heroicon-m-table-cells" class="w-3.5 h-3.5" />
                            <span>DTR Entries{{ isset($detailResult['export']['record_count']) ? ' ('.$detailResult['export']['record_count'].')' : '' }}</span>
                        </button>
                    </div>
                    <div style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap">
                        <x-filament::badge :color="match($detailSync->status){'success'=>'success','failed'=>'danger',default=>'warning'}">
                            {{ ucfirst($detailSync->status) }}
                        </x-filament::badge>
                        <span style="font-size:0.75rem;color:#94a3b8;font-family:ui-monospace,SFMono-Regular,monospace">
                            DTR Payroll · {{ $detailSync->date_from->toDateString() }} – {{ $detailSync->date_to->toDateString() }}
                        </span>
                    </div>
                </div>

                <div class="bio-modal-body">
                    @if($this->detailTab === 'overview')
                        {{-- Result Card --}}
                        <section class="bio-modal-result-box {{ $isFailed ? 'result-failed' : ($isSuccess ? 'result-success' : 'result-pending') }}">
                            <div class="bio-modal-result-header">
                                <x-filament::icon icon="{{ $isFailed ? 'heroicon-m-x-circle' : ($isSuccess ? 'heroicon-m-check-circle' : 'heroicon-m-clock') }}" class="w-4 h-4 {{ $isFailed ? 'text-rose-400' : ($isSuccess ? 'text-emerald-400' : 'text-amber-400') }}" />
                                <h4 class="bio-modal-result-title">Result</h4>
                            </div>
                            <p class="bio-modal-result-text">{{ $detailSync->message ?: 'The request is waiting for an update.' }}</p>
                            @if($allFingerprintsUnmatched)
                                <div role="alert" class="bio-modal-alert">
                                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-4 h-4 flex-shrink-0 text-amber-400 mt-0.5" />
                                    <span>All exported fingerprints are unmatched in HRIS. Check the employees' fingerprint IDs and assigned branches, then start a new sync.</span>
                                </div>
                            @endif
                            @if($detailSync->status === 'pending' && !$detailSync->command_id && $detailSync->created_at->lt(now()->subMinute()))
                                <div role="alert" class="bio-modal-alert">
                                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-4 h-4 flex-shrink-0 text-amber-400 mt-0.5" />
                                    <span>The command has not reached BiometricsWeb. Check the PPCHRISV2 biometrics queue worker and recovery timer.</span>
                                </div>
                            @endif
                        </section>

                        {{-- Stat Summary Cards --}}
                        <section class="bio-modal-stats-grid">
                            @php
                                $statConfigs = [
                                    'Imported' => ['count' => $detailResult['successful'] ?? 0, 'class' => 'stat-card-imported', 'icon' => 'heroicon-m-check-badge'],
                                    'Skipped' => ['count' => $detailResult['skipped'] ?? 0, 'class' => 'stat-card-skipped', 'icon' => 'heroicon-m-forward'],
                                    'Invalid' => ['count' => $detailResult['failed'] ?? 0, 'class' => 'stat-card-invalid', 'icon' => 'heroicon-m-x-circle'],
                                    'Pending' => ['count' => $detailResult['pending'] ?? 0, 'class' => 'stat-card-pending', 'icon' => 'heroicon-m-clock'],
                                    'For review' => ['count' => $detailResult['conflicts'] ?? 0, 'class' => 'stat-card-review', 'icon' => 'heroicon-m-eye'],
                                ];
                            @endphp
                            @foreach($statConfigs as $label => $config)
                                <div class="bio-modal-stat-card {{ $config['class'] }}">
                                    <div class="bio-modal-stat-label">
                                        <span>{{ $label }}</span>
                                        <x-filament::icon icon="{{ $config['icon'] }}" class="w-3.5 h-3.5 opacity-60" />
                                    </div>
                                    <strong class="bio-modal-stat-val">{{ $config['count'] }}</strong>
                                </div>
                            @endforeach
                        </section>

                        {{-- Request Information & Device Export --}}
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem">
                            <section class="bio-modal-panel">
                                <div class="bio-modal-panel-header">
                                    <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 text-blue-400" />
                                    <h4 class="bio-modal-panel-title">Request information</h4>
                                </div>
                                <dl class="bio-modal-info-grid">
                                    <div class="bio-modal-info-item">
                                        <dt>Request ID</dt>
                                        <dd><span class="bio-modal-info-badge">{{ $detailSync->id }}</span></dd>
                                    </div>
                                    <div class="bio-modal-info-item">
                                        <dt>Command ID</dt>
                                        <dd><span class="bio-modal-info-badge" style="color:#60a5fa">{{ $detailSync->command_id ?? 'Not sent' }}</span></dd>
                                    </div>
                                    <div class="bio-modal-info-item">
                                        <dt>Requested</dt>
                                        <dd class="font-mono text-xs">{{ $detailSync->created_at?->format('M d, Y H:i:s') ?? '—' }}</dd>
                                    </div>
                                    <div class="bio-modal-info-item">
                                        <dt>Requested by</dt>
                                        <dd>{{ $detailSync->requestedBy?->name ?: ($detailSync->requestedBy?->employee?->full_name ?? 'Account unavailable') }}@if($detailSync->requestedBy?->username) · {{ $detailSync->requestedBy->username }}@endif</dd>
                                    </div>
                                    <div class="bio-modal-info-item">
                                        <dt>Received</dt>
                                        <dd class="font-mono text-xs">{{ $detailSync->received_at?->format('M d, Y H:i:s') ?? '—' }}</dd>
                                    </div>
                                    <div class="bio-modal-info-item">
                                        <dt>Executed</dt>
                                        <dd class="font-mono text-xs">{{ $detailSync->executed_at?->format('M d, Y H:i:s') ?? '—' }}</dd>
                                    </div>
                                    <div class="bio-modal-info-item">
                                        <dt>Completed</dt>
                                        <dd class="font-mono text-xs">{{ $detailSync->completed_at?->format('M d, Y H:i:s') ?? '—' }}</dd>
                                    </div>
                                </dl>
                            </section>

                            @if(!empty($detailResult['export']))
                                <section class="bio-modal-panel">
                                    <div class="bio-modal-panel-header">
                                        <x-filament::icon icon="heroicon-m-archive-box" class="w-4 h-4 text-blue-400" />
                                        <h4 class="bio-modal-panel-title">Device export</h4>
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:0.65rem">
                                        <div>
                                            <span style="font-size:0.6875rem;font-weight:700;text-transform:uppercase;color:#94a3b8;display:block;margin-bottom:0.2rem">Export File</span>
                                            <span class="bio-modal-info-badge" style="display:block;word-break:break-all">File: {{ $detailResult['export']['file_name'] ?? '—' }}</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:1.5rem;margin-top:0.35rem">
                                            <div>
                                                <span style="font-size:0.6875rem;font-weight:700;text-transform:uppercase;color:#94a3b8;display:block;margin-bottom:0.15rem">Record Count</span>
                                                <span style="font-size:0.875rem;font-weight:700;color:#f8fafc">Records: {{ $detailResult['export']['record_count'] ?? '—' }}</span>
                                            </div>
                                            <div>
                                                <span style="font-size:0.6875rem;font-weight:700;text-transform:uppercase;color:#94a3b8;display:block;margin-bottom:0.15rem">File Size</span>
                                                <span style="font-size:0.875rem;font-weight:700;color:#f8fafc">Size: {{ $detailResult['export']['size'] ?? '—' }} bytes</span>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            @endif
                        </div>

                        {{-- DTR Records and Findings Section --}}
                        <section class="bio-modal-panel">
                            <div class="bio-modal-panel-header">
                                <x-filament::icon icon="heroicon-m-document-text" class="w-4 h-4 text-blue-400" />
                                <h4 class="bio-modal-panel-title">DTR records and findings</h4>
                            </div>
                            @if($detailIssues->isEmpty())
                                <p style="color:#cbd5e1;margin:0.25rem 0 0">No DTR rows need review.</p>
                            @else
                                @if($needsArchivedRows && !$this->archiveLookupAttempted)
                                    <p wire:init="loadArchivedRows" role="status" style="margin-bottom:.75rem;color:#cbd5e1">Loading original DTR fields from the archived device export…</p>
                                @elseif($needsArchivedRows && $this->archiveLookupFailed)
                                    <p role="alert" style="margin-bottom:.75rem;color:#fbbf24">The original DTR fields for this older sync are not available yet. Update BiometricsWeb to allow signed archived-export access, then retry. The saved error reasons remain available below. <button type="button" wire:click="retryArchivedRows" style="color:#93c5fd;text-decoration:underline">Retry loading</button></p>
                                @endif
                                <div style="display:grid;gap:.85rem">
                                    @foreach($detailIssues as $rowNumber => $issues)
                                        @php
                                            $sourceRow = $availableRowDetails[$rowNumber] ?? null;
                                            $hasError = $issues->contains(fn($issue) => $issue['kind'] === 'Error');
                                        @endphp
                                        <article style="padding:1rem;border:1px solid {{ $hasError ? 'rgba(239,68,68,0.45)' : 'rgba(245,158,11,0.45)' }};border-radius:.75rem;background:{{ $hasError ? 'rgba(127,29,29,.2)' : 'rgba(146,64,14,.2)' }};overflow-wrap:anywhere">
                                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                                                <h5 style="font-weight:700;font-size:1.05rem;margin:0;color:{{ $hasError ? '#fca5a5' : '#fde68a' }}">
                                                    {{ $rowNumber > 0 ? 'DTR row '.$rowNumber : 'General import issue' }}
                                                </h5>
                                                <span style="font-size:0.6875rem;font-weight:700;text-transform:uppercase;padding:0.2rem 0.5rem;border-radius:0.35rem;background:{{ $hasError ? 'rgba(239,68,68,0.25)' : 'rgba(245,158,11,0.25)' }};color:{{ $hasError ? '#f87171' : '#fbbf24' }};border:1px solid {{ $hasError ? 'rgba(239,68,68,0.35)' : 'rgba(245,158,11,0.35)' }}">
                                                    {{ $hasError ? 'Error' : 'Notice' }}
                                                </span>
                                            </div>
                                            @if(is_array($sourceRow))
                                                <div style="padding:.85rem;border:1px solid #334155;border-radius:.6rem;background:rgba(15,23,42,.7);margin-bottom:.75rem">
                                                    <h6 style="font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;margin-bottom:.65rem">Complete device DTR record</h6>
                                                    <dl style="display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:.65rem 1rem">
                                                        @foreach($sourceRow as $field => $value)
                                                            @php
                                                                $fieldLabel = match ($field) {
                                                                    'uid' => 'Fingerprint ID',
                                                                    'sched' => 'Schedule type',
                                                                    'sched_start' => 'Schedule start',
                                                                    'sched_end' => 'Schedule end',
                                                                    default => \Illuminate\Support\Str::headline((string) $field),
                                                                };
                                                                $fieldValue = is_bool($value) ? ($value ? 'Yes' : 'No') : (is_scalar($value) ? (string) $value : (is_null($value) ? '—' : json_encode($value)));
                                                            @endphp
                                                            <div style="min-width:0">
                                                                <dt style="color:#94a3b8;font-size:.7rem;font-weight:700;text-transform:uppercase">{{ $fieldLabel }}</dt>
                                                                <dd style="color:#f8fafc;font-size:0.8125rem;font-family:ui-monospace,SFMono-Regular,monospace;overflow-wrap:anywhere">{{ $fieldValue === '' ? '—' : $fieldValue }}</dd>
                                                            </div>
                                                        @endforeach
                                                    </dl>
                                                </div>
                                            @elseif($rowNumber > 0 && $this->archiveLookupAttempted && $this->archiveLookupFailed)
                                                <p style="color:#cbd5e1;margin-bottom:.75rem">The source DTR fields could not be loaded for this row.</p>
                                            @endif
                                            <div>
                                                <h6 style="font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:{{ $hasError ? '#fca5a5' : '#fde68a' }};margin-bottom:.3rem">
                                                    {{ $hasError ? 'Why this row failed' : 'Review notice' }}
                                                </h6>
                                                @foreach($issues as $issue)
                                                    <p style="margin-top:.25rem;color:{{ $issue['kind'] === 'Error' ? '#fecaca' : '#fde68a' }};font-size:0.8125rem">{{ $issue['kind'] }}: {{ $issue['message'] }}</p>
                                                @endforeach
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @else
                        {{-- Entries Tab Content --}}
                        <section aria-label="Exported DTR entries">
                            <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start;margin-bottom:1rem">
                                <div>
                                    <h4 style="font-size:1.15rem;font-weight:700;color:#ffffff;margin:0">Exported DTR entries</h4>
                                    <p style="color:#94a3b8;font-size:0.8125rem;margin:0.2rem 0 0">These are the device's original export rows, not a list of saved HRIS records. Check the Overview counts for the import result.</p>
                                </div>
                                @if($this->exportDtrLoaded)
                                    <span class="bio-modal-info-badge">{{ number_format($this->exportDtrTotal) }} {{ $this->exportDtrTotal === 1 ? 'record' : 'records' }}</span>
                                @endif
                                @if((int) ($detailResult['skipped'] ?? 0) > 0)
                                    <span role="status" style="display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .65rem;border:1px solid #b45309;border-radius:999px;background:#451a03;color:#fde68a;font-size:.75rem;font-weight:700">
                                        {{ number_format((int) $detailResult['skipped']) }} skipped @if($hasSkippedExportRowMap)· flagged below @else · row-level details were not saved for this sync @endif
                                    </span>
                                @endif
                            </div>
                            @if($this->exportDtrSource === 'saved_rejected')
                                <div role="status" class="bio-modal-alert" style="margin-bottom:1rem">
                                    <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-4 h-4 flex-shrink-0 text-amber-400 mt-0.5" />
                                    <span>Showing the complete rejected-row snapshot saved by HRIS. The live BiometricsWeb archive is still unavailable for this failed sync. <button type="button" wire:click="retryExportDtrEntries" style="color:#93c5fd;text-decoration:underline">Try archive again</button></span>
                                </div>
                            @endif
                            @if(!$this->exportDtrLoaded && !$this->exportDtrFailed)
                                <div wire:init="loadExportDtrEntries" role="status" style="padding:1.5rem;text-align:center;border:1px dashed #334155;border-radius:.65rem;color:#cbd5e1">
                                    <p style="margin:0">Loading and verifying the archived device export…</p>
                                </div>
                            @elseif($this->exportDtrFailed)
                                <div role="alert" class="bio-modal-alert">
                                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-4 h-4 flex-shrink-0 text-amber-400 mt-0.5" />
                                    <span>{{ $this->exportDtrFailureMessage }} <button type="button" wire:click="retryExportDtrEntries" style="color:#93c5fd;text-decoration:underline">Retry</button></span>
                                </div>
                            @elseif($this->exportDtrRows === [])
                                <div style="padding:1.5rem;text-align:center;border:1px dashed #334155;border-radius:.65rem;color:#94a3b8">
                                    <p style="margin:0">No DTR entries were included in this export.</p>
                                </div>
                            @else
                                <p style="margin:.25rem 0 .75rem;color:#94a3b8;font-size:.8rem">Device Day Count is the original export value. In HRIS, a Saturday absence is 3 hours; an overtime-only row adds 0 workdays.</p>
                                @php
                                    $dtrColumns = [
                                        ['field' => 'uid', 'label' => 'Fingerprint ID', 'tone' => 'identity'],
                                        ['field' => 'name', 'label' => 'Name', 'tone' => 'identity'],
                                        ['field' => 'date_in', 'label' => 'Date In', 'tone' => 'date'],
                                        ['field' => 'time_in', 'label' => 'Time In', 'tone' => 'clock'],
                                        ['field' => 'date_out', 'label' => 'Date Out', 'tone' => 'date'],
                                        ['field' => 'time_out', 'label' => 'Time Out', 'tone' => 'clock'],
                                        ['field' => 'sched', 'label' => 'Schedule Type', 'tone' => 'schedule'],
                                        ['field' => 'sched_start', 'label' => 'Schedule Start', 'tone' => 'schedule'],
                                        ['field' => 'sched_end', 'label' => 'Schedule End', 'tone' => 'schedule'],
                                        ['field' => 'day_part', 'label' => 'Day Part', 'tone' => 'shift'],
                                        ['field' => 'day_count', 'label' => 'Device Day Count', 'tone' => 'shift'],
                                        ['field' => 'late', 'label' => 'Late', 'tone' => 'late'],
                                        ['field' => 'undertime', 'label' => 'Undertime', 'tone' => 'undertime'],
                                        ['field' => 'overtime', 'label' => 'Overtime', 'tone' => 'overtime'],
                                        ['field' => 'early', 'label' => 'Early Clock In', 'tone' => 'overtime'],
                                        ['field' => 'ot_status', 'label' => 'OT Status', 'tone' => 'status'],
                                        ['field' => 'branch', 'label' => 'Source Station', 'tone' => 'station'],
                                        ['field' => 'branch_id', 'label' => 'Device Code', 'tone' => 'station'],
                                        ['field' => 'session_id', 'label' => 'Session ID', 'tone' => 'identity'],
                                    ];
                                @endphp
                                <div class="bio-dtr-table-scroll" aria-label="Scrollable DTR entries table">
                                    <table class="bio-dtr-table" style="width:100%;min-width:2100px;text-align:left;border-collapse:collapse;font-size:.85rem">
                                        <thead style="background:#1e293b"><tr>
                                            @foreach(['#','Row Check'] as $heading)
                                                <th scope="col" style="padding:.7rem;white-space:nowrap;font-size:0.6875rem;font-weight:700;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #334155">{{ $heading }}</th>
                                            @endforeach
                                            @foreach($dtrColumns as $column)
                                                <th scope="col" class="bio-dtr-heading--{{ $column['tone'] }}" style="padding:.7rem;white-space:nowrap;font-size:0.6875rem;font-weight:700;text-transform:uppercase;border-bottom:1px solid #334155">{{ $column['label'] }}</th>
                                            @endforeach
                                            <th scope="col" style="padding:.7rem;white-space:nowrap;font-size:0.6875rem;font-weight:700;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #334155">Finding</th>
                                        </tr></thead>
                                        <tbody>
                                            @foreach($this->exportDtrRows as $entry)
                                                @php
                                                    $row = $entry['record'];
                                                    $findings = $entry['import_row'] ? ($detailIssues->get($entry['import_row']) ?? collect()) : collect();
                                                    $hasError = $findings->contains(fn($issue) => $issue['kind'] === 'Error');
                                                    $inactiveSkipped = $entry['inactive_skipped'] ?? false;
                                                    $unmatchedSkipped = $entry['unmatched_skipped'] ?? false;
                                                    $missingFingerprintSkipped = $entry['missing_fingerprint_skipped'] ?? false;
                                                    $importSkipped = $entry['import_skipped'] ?? false;
                                                    $outcome = $inactiveSkipped ? 'Skipped (Inactive)' : ($unmatchedSkipped ? 'Skipped (Unmatched Fingerprint)' : ($missingFingerprintSkipped ? 'Skipped (Missing Fingerprint ID)' : ($importSkipped ? 'Skipped' : ($entry['deferred'] ? 'Deferred' : ($hasError ? 'Invalid' : ($findings->isNotEmpty() ? 'Review' : 'Not flagged'))))));
                                                    $outcomeColor = match ($outcome) { 'Invalid' => '#fecaca', 'Review', 'Deferred', 'Skipped', 'Skipped (Inactive)', 'Skipped (Unmatched Fingerprint)', 'Skipped (Missing Fingerprint ID)' => '#fde68a', default => '#cbd5e1' };
                                                    $outcomeBackground = match ($outcome) { 'Invalid' => '#450a0a', 'Review', 'Deferred', 'Skipped', 'Skipped (Inactive)', 'Skipped (Unmatched Fingerprint)', 'Skipped (Missing Fingerprint ID)' => '#451a03', default => '#1e293b' };
                                                @endphp
                                                <tr style="border-top:1px solid #1e293b">
                                                    <td style="padding:.7rem;color:#cbd5e1">{{ $entry['source_row'] }}</td>
                                                    <td style="padding:.7rem;white-space:nowrap"><span style="display:inline-flex;padding:.2rem .55rem;border:1px solid {{ $outcomeColor }};border-radius:999px;background:{{ $outcomeBackground }};color:{{ $outcomeColor }};font-size:.72rem;font-weight:700">{{ $outcome }}</span></td>
                                                    @foreach($dtrColumns as $column)
                                                        @php
                                                            $field = $column['field'];
                                                            $value = $row[$field] ?? null;
                                                            $tone = $column['tone'];
                                                            if (! filled($value) || (in_array($field, ['late', 'undertime', 'overtime', 'early', 'day_count'], true) && is_numeric($value) && (float) $value === 0.0)) {
                                                                $tone = 'muted';
                                                            } elseif ($field === 'sched') {
                                                                $schedule = strtolower(trim((string) $value));
                                                                $tone = str_contains($schedule, 'absent') || str_contains($schedule, 'forgot') ? 'undertime' : (str_contains($schedule, 'overtime') ? 'overtime' : 'schedule');
                                                            } elseif ($field === 'ot_status') {
                                                                $status = strtolower(trim((string) $value));
                                                                $tone = match (true) {
                                                                    str_contains($status, 'reject'), str_contains($status, 'denied'), str_contains($status, 'disapprov'), str_contains($status, 'unapprov'), str_contains($status, 'not approv') => 'rejected',
                                                                    str_contains($status, 'pend'), str_contains($status, 'request') => 'pending',
                                                                    str_contains($status, 'approv') => 'approved',
                                                                    default => 'muted',
                                                                };
                                                            }
                                                        @endphp
                                                        <td class="bio-dtr-value bio-dtr-value--{{ $tone }}" style="padding:.7rem;white-space:nowrap">{{ filled($value) ? $value : '—' }}</td>
                                                    @endforeach
                                                    <td style="padding:.7rem;min-width:260px;max-width:400px;overflow-wrap:anywhere">
                                                        @foreach($findings as $finding)
                                                            @if(!$importSkipped || empty($entry['skip_reason']))
                                                                <div style="color:{{ $finding['kind'] === 'Error' ? '#f87171' : '#fbbf24' }}">{{ $finding['message'] }}</div>
                                                            @endif
                                                        @endforeach
                                                        @if(!empty($entry['skip_reason']))
                                                            <span style="color:#fbbf24">{{ $entry['skip_reason'] }}</span>
                                                        @endif
                                                        @if($importSkipped && empty($entry['skip_reason']))
                                                            <span style="color:#fbbf24">An existing or previously imported DTR was preserved; this row was skipped.</span>
                                                        @endif
                                                        @if($inactiveSkipped && empty($entry['skip_reason']))
                                                            <span style="color:#94a3b8">Employee is inactive or employment has ended; this entry was skipped.</span>
                                                        @endif
                                                        @if($entry['deferred'])
                                                            <span style="color:#fbbf24">Awaiting a completed Time Out; not imported on this sync.</span>
                                                        @endif
                                                        @if($findings->isEmpty() && !$entry['deferred'] && !$inactiveSkipped && empty($entry['skip_reason']))
                                                            <span style="color:#64748b">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:1rem">
                                    @php
                                        $exportDtrPageSize = $this->exportDtrShowAll ? max(1, $this->exportDtrTotal) : 25;
                                        $exportDtrPageCount = max(1, (int) ceil($this->exportDtrTotal / $exportDtrPageSize));
                                        $exportDtrFirst = (($this->exportDtrPage - 1) * $exportDtrPageSize) + 1;
                                        $exportDtrLast = min($this->exportDtrPage * $exportDtrPageSize, $this->exportDtrTotal);
                                    @endphp
                                    <span style="font-size:0.75rem;color:#94a3b8">Showing {{ $exportDtrFirst }}–{{ $exportDtrLast }} of {{ number_format($this->exportDtrTotal) }}@if($this->exportDtrShowAll) · all entries shown @endif</span>
                                    <div style="display:flex;align-items:center;gap:.5rem">
                                        @if($this->exportDtrTotal > 25)
                                            @if($this->exportDtrShowAll)
                                                <button type="button" wire:click="showPagedExportDtrEntries" wire:loading.attr="disabled" wire:target="showPagedExportDtrEntries" class="bio-modal-tab-btn" style="background:#1e293b;border:1px solid #334155;color:#f8fafc">Show 25 per page</button>
                                            @else
                                                <button type="button" wire:click="showAllExportDtrEntries" wire:loading.attr="disabled" wire:target="showAllExportDtrEntries" class="bio-modal-tab-btn" style="background:#1d4ed8;border:1px solid #3b82f6;color:#ffffff">Show all</button>
                                            @endif
                                        @endif
                                        @unless($this->exportDtrShowAll)
                                            <button type="button" wire:click="changeExportDtrPage({{ $this->exportDtrPage - 1 }})" wire:loading.attr="disabled" wire:target="changeExportDtrPage" @disabled($this->exportDtrPage <= 1) class="bio-modal-tab-btn" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;opacity:{{ $this->exportDtrPage <= 1 ? '.4' : '1' }}">Previous</button>
                                            <span style="font-size:0.75rem;color:#94a3b8;padding:0 0.25rem">Page {{ $this->exportDtrPage }} of {{ $exportDtrPageCount }}</span>
                                            <button type="button" wire:click="changeExportDtrPage({{ $this->exportDtrPage + 1 }})" wire:loading.attr="disabled" wire:target="changeExportDtrPage" @disabled($this->exportDtrPage >= $exportDtrPageCount) class="bio-modal-tab-btn" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;opacity:{{ $this->exportDtrPage >= $exportDtrPageCount ? '.4' : '1' }}">Next</button>
                                        @endunless
                                    </div>
                                </div>
                            @endif
                        </section>
                    @endif
                </div>

                @endif

            </div>
        </div>
    @endif

    {{-- Sync Employee Modal --}}
    @if($this->canSyncEmployees() && $showEmployeeSyncModal)
        <div role="dialog" aria-modal="true" aria-labelledby="employee-sync-dialog-title" wire:keydown.escape.window="$set('showEmployeeSyncModal', false)" class="bio-modal-backdrop">
            <div aria-hidden="true" wire:click="$set('showEmployeeSyncModal', false)" class="bio-modal-overlay"></div>
            <div class="bio-modal-dialog bio-modal-dialog-sm">
                <div class="bio-modal-header">
                    <div class="bio-modal-header-left">
                        <div class="bio-modal-header-icon"><x-filament::icon icon="heroicon-m-users" class="w-5 h-5" /></div>
                        <div>
                            <div class="bio-modal-eyebrow">Device Employee Export</div>
                            <h3 id="employee-sync-dialog-title" class="bio-modal-title">Sync Employee</h3>
                            <div class="bio-modal-subtitle">{{ $selected['name'] ?? 'Selected device' }}</div>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showEmployeeSyncModal', false)" aria-label="Close employee sync modal" class="bio-modal-close-btn"><x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" /></button>
                </div>
                <div class="bio-modal-body">
                    <p class="bio-modal-result-text">Download the employees registered on this device. The result is a read-only snapshot; no HRIS employee data will be changed.</p>
                    @error('device')<p role="alert" class="text-rose-400 text-sm">{{ $message }}</p>@enderror
                </div>
                <div class="bio-modal-footer">
                    <x-filament::button color="gray" wire:click="$set('showEmployeeSyncModal', false)" wire:loading.attr="disabled" wire:target="submitEmployeeSync">Cancel</x-filament::button>
                    <x-filament::button wire:click="submitEmployeeSync" wire:loading.attr="disabled" wire:target="submitEmployeeSync">
                        <span wire:loading.remove wire:target="submitEmployeeSync">Sync Employee</span>
                        <span wire:loading wire:target="submitEmployeeSync">Queuing…</span>
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Sync Device Modal --}}
    @if($showSyncModal)
        <div role="dialog" aria-modal="true" aria-labelledby="sync-dialog-title" wire:keydown.escape.window="$set('showSyncModal', false)" class="bio-modal-backdrop">
            <div aria-hidden="true" wire:click="$set('showSyncModal', false)" class="bio-modal-overlay"></div>
            <div class="bio-modal-dialog bio-modal-dialog-sm">
                <div class="bio-modal-header">
                    <div class="bio-modal-header-left">
                        <div class="bio-modal-header-icon" style="background: rgba(37, 99, 235, 0.15); border-color: rgba(59, 130, 246, 0.3); color: #60a5fa;">
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="bio-modal-eyebrow">Payroll DTR Dispatch</div>
                            <h3 id="sync-dialog-title" class="bio-modal-title" style="font-size: 1.2rem;">Sync Device</h3>
                            <div class="bio-modal-subtitle">
                                <span class="bio-modal-info-badge">
                                    {{ $selected['name'] ?? ($this->device ? 'Device '.$this->device : 'Selected Device') }}
                                </span>
                                <span>·</span>
                                <span style="color:#94a3b8">Command: DTR Payroll</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showSyncModal', false)" aria-label="Close sync modal" class="bio-modal-close-btn">
                        <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                    </button>
                </div>

                <div class="bio-modal-body" style="gap: 1rem; padding: 1.25rem 1.5rem;">
                    <div class="bio-modal-field">
                        <label for="sync-period" class="bio-modal-label">
                            <x-filament::icon icon="heroicon-m-calendar-days" class="w-4 h-4 text-blue-400" />
                            <span>Payroll Period</span>
                        </label>
                        <select id="sync-period" wire:model="periodId" class="bio-modal-select">
                            <option value="">Select payroll period</option>
                            @foreach(\App\Models\PayrollPeriod::where('is_locked',false)->newestFirst()->get() as $period)
                                <option value="{{ $period->id }}">{{ $period->title }} ({{ $period->date_start->toDateString() }} – {{ $period->date_end->toDateString() }})</option>
                            @endforeach
                        </select>
                        @error('periodId')<p role="alert" style="color:#f87171;font-size:0.8rem;margin-top:0.25rem">{{ $message }}</p>@enderror
                        @error('device')<p role="alert" style="color:#f87171;font-size:0.8rem;margin-top:0.25rem">{{ $message }}</p>@enderror
                    </div>

                    <div class="bio-modal-alert" style="margin-top: 0.25rem;">
                        <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 flex-shrink-0 text-blue-400 mt-0.5" />
                        <span style="color: #cbd5e1; font-size: 0.8125rem;">The selected payroll period sets the export date range.</span>
                    </div>
                </div>

                <div class="bio-modal-footer">
                    <x-filament::button color="gray" wire:click="$set('showSyncModal', false)" wire:loading.attr="disabled" wire:target="submitSync">Cancel</x-filament::button>
                    <x-filament::button wire:click="submitSync" wire:loading.attr="disabled" wire:target="submitSync">
                        <span wire:loading.remove wire:target="submitSync" style="display:inline-flex;align-items:center;gap:0.35rem">
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4" />
                            <span>Sync</span>
                        </span>
                        <span wire:loading wire:target="submitSync">Queuing…</span>
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
