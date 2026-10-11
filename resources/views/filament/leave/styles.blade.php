@once
    <style>
        .leave-review {
            --lr-bg-card: #ffffff;
            --lr-bg-subtle: #f8fafc;
            --lr-bg-muted: #f1f5f9;
            --lr-border: #e2e8f0;
            --lr-border-strong: #cbd5e1;
            --lr-text-main: #0f172a;
            --lr-text-secondary: #334155;
            --lr-text-muted: #64748b;
            --lr-accent-blue: #2563eb;
            --lr-accent-blue-soft: rgba(37, 99, 235, 0.1);
            width: 100%;
            min-width: 0;
            color: var(--lr-text-main);
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .dark .leave-review {
            --lr-bg-card: #0f172a;
            --lr-bg-subtle: #1e293b;
            --lr-bg-muted: rgba(30, 41, 59, 0.65);
            --lr-border: #1e293b;
            --lr-border-strong: #334155;
            --lr-text-main: #f8fafc;
            --lr-text-secondary: #e2e8f0;
            --lr-text-muted: #94a3b8;
            --lr-accent-blue: #60a5fa;
            --lr-accent-blue-soft: rgba(59, 130, 246, 0.16);
        }

        .leave-review h2,
        .leave-review h3,
        .leave-review p,
        .leave-review dl,
        .leave-review dt,
        .leave-review dd {
            margin: 0;
        }

        .leave-review .muted {
            color: var(--lr-text-muted);
            font-size: 0.76rem;
        }

        /* 1. Employee & Status Overview Banner */
        .leave-review .overview {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.95rem 1.2rem;
            border-radius: 0.7rem;
            border: 1px solid var(--lr-border-strong);
            background: linear-gradient(135deg, var(--lr-bg-card) 0%, var(--lr-bg-subtle) 100%);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .leave-review .overview::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 4px;
            background: linear-gradient(180deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);
        }

        .leave-review .overview-identity {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .leave-review .overview-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.65rem;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.28);
        }

        .leave-review .overview-info {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            min-width: 0;
        }

        .leave-review .overview h2 {
            font-size: 1.08rem;
            font-weight: 800;
            color: var(--lr-text-main);
            line-height: 1.25;
            letter-spacing: -0.015em;
        }

        .leave-review .overview-chips {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
        }

        .leave-review .overview-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.14rem 0.52rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            background: var(--lr-bg-muted);
            color: var(--lr-text-secondary);
            border: 1px solid var(--lr-border-strong);
            white-space: nowrap;
        }

        .leave-review .overview-chip.code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            background: var(--lr-accent-blue-soft);
            color: var(--lr-accent-blue);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .leave-review .overview-chip svg {
            width: 0.78rem;
            height: 0.78rem;
            opacity: 0.8;
            flex-shrink: 0;
        }

        .leave-review .overview-status-box {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }

        .leave-review .overview-status-label {
            font-size: 0.64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--lr-text-muted);
        }

        /* 2. Key Metrics / Details Grid */
        .leave-review .details-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.65rem;
            margin: 0;
        }

        .leave-review .detail-card {
            position: relative;
            min-width: 0;
            padding: 0.75rem 0.95rem;
            border: 1px solid var(--lr-border-strong);
            border-radius: 0.65rem;
            background: var(--lr-bg-card);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.3rem;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }

        .leave-review .detail-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 3px;
        }

        .leave-review .detail-card.accent-blue::before { background: linear-gradient(180deg, #3b82f6, #2563eb); }
        .leave-review .detail-card.accent-indigo::before { background: linear-gradient(180deg, #6366f1, #4f46e5); }
        .leave-review .detail-card.accent-emerald::before { background: linear-gradient(180deg, #10b981, #059669); }
        .leave-review .detail-card.accent-purple::before { background: linear-gradient(180deg, #a855f7, #7c3aed); }
        .leave-review .detail-card.accent-sky::before { background: linear-gradient(180deg, #0ea5e9, #0284c7); }
        .leave-review .detail-card.accent-amber::before { background: linear-gradient(180deg, #f59e0b, #d97706); }

        .leave-review .detail-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
        }

        .leave-review dt {
            font-size: 0.66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.045em;
            color: var(--lr-text-muted);
        }

        .leave-review .detail-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 0.4rem;
            flex-shrink: 0;
        }

        .leave-review .detail-icon svg {
            width: 0.88rem;
            height: 0.88rem;
        }

        .leave-review .accent-blue .detail-icon { background: rgba(59, 130, 246, 0.14); color: #3b82f6; }
        .leave-review .accent-indigo .detail-icon { background: rgba(99, 102, 241, 0.14); color: #6366f1; }
        .leave-review .accent-emerald .detail-icon { background: rgba(16, 185, 129, 0.14); color: #10b981; }
        .leave-review .accent-purple .detail-icon { background: rgba(168, 85, 247, 0.14); color: #a855f7; }
        .leave-review .accent-sky .detail-icon { background: rgba(14, 165, 233, 0.14); color: #0ea5e9; }
        .leave-review .accent-amber .detail-icon { background: rgba(245, 158, 11, 0.14); color: #f59e0b; }

        .leave-review dd {
            font-size: 0.86rem;
            font-weight: 700;
            color: var(--lr-text-main);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .leave-review .detail-sub {
            font-size: 0.7rem;
            color: var(--lr-text-muted);
            line-height: 1.25;
        }

        /* 3. Two-Column Section Layout */
        .leave-review .columns {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(19rem, 1fr);
            align-items: start;
            gap: 0.85rem;
        }

        .leave-review .columns-stack {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            min-width: 0;
        }

        /* Section Cards (.band) */
        .leave-review .band {
            min-width: 0;
            border: 1px solid var(--lr-border-strong);
            border-radius: 0.65rem;
            background: var(--lr-bg-card);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
            padding: 0;
        }

        .leave-review .band-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.6rem 0.95rem;
            background: var(--lr-bg-subtle);
            border-bottom: 1px solid var(--lr-border-strong);
        }

        .leave-review .band-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .leave-review .band-title-wrap svg {
            width: 0.95rem;
            height: 0.95rem;
            color: var(--lr-accent-blue);
            flex-shrink: 0;
        }

        .leave-review .band h3 {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--lr-text-secondary);
        }

        .leave-review .band-meta {
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--lr-text-muted);
        }

        .leave-review .band-body {
            padding: 0.85rem 1rem;
        }

        .leave-review .reason-box {
            padding: 0.7rem 0.85rem;
            border-radius: 0.5rem;
            background: var(--lr-bg-subtle);
            border: 1px solid var(--lr-border);
            border-inline-start: 3px solid var(--lr-accent-blue);
        }

        .leave-review .prose {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            font-size: 0.82rem;
            line-height: 1.55;
            color: var(--lr-text-main);
        }

        .leave-review .empty-attachment {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.65rem 0.85rem;
            border-radius: 0.5rem;
            border: 1px dashed var(--lr-border-strong);
            background: var(--lr-bg-subtle);
            color: var(--lr-text-muted);
            font-size: 0.78rem;
        }

        .leave-review .empty-attachment svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
            opacity: 0.75;
        }

        .leave-review .attachment {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 0.65rem 0.85rem;
            border-radius: 0.5rem;
            border: 1px solid var(--lr-border-strong);
            background: var(--lr-bg-subtle);
        }

        .leave-review .attachment-file {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--lr-text-main);
            min-width: 0;
        }

        .leave-review .attachment-file svg {
            width: 1.1rem;
            height: 1.1rem;
            color: var(--lr-accent-blue);
            flex-shrink: 0;
        }

        /* 4. Approval Progress Timeline */
        .leave-review .timeline {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .leave-review .timeline li {
            position: relative;
            padding: 0.65rem 0.8rem;
            border-radius: 0.5rem;
            border: 1px solid var(--lr-border-strong);
            border-inline-start-width: 3px;
            border-inline-start-color: #64748b;
            background: var(--lr-bg-subtle);
            overflow-wrap: anywhere;
        }

        .leave-review .timeline li.current {
            border-color: rgba(59, 130, 246, 0.45);
            border-inline-start-color: #3b82f6;
            background: var(--lr-accent-blue-soft);
        }

        .leave-review .timeline li.is-approved {
            border-inline-start-color: #10b981;
        }

        .leave-review .timeline li.is-rejected {
            border-inline-start-color: #ef4444;
        }

        .leave-review .step-line {
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 0.25rem;
        }

        .leave-review .step-name {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--lr-text-main);
        }

        .leave-review .step-meta {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.74rem;
            color: var(--lr-text-muted);
        }

        .leave-review .step-meta svg {
            width: 0.8rem;
            height: 0.8rem;
            flex-shrink: 0;
        }

        .leave-review .step-remarks {
            margin-top: 0.4rem;
            padding: 0.4rem 0.6rem;
            border-radius: 0.38rem;
            background: var(--lr-bg-card);
            border: 1px solid var(--lr-border);
            font-size: 0.76rem;
            color: var(--lr-text-secondary);
        }

        /* 5. Activity History Table */
        .leave-review .scroll {
            overflow-x: auto;
            margin: 0;
        }

        .leave-review .audit {
            width: 100%;
            min-width: 36rem;
            border-collapse: collapse;
            font-size: 0.78rem;
        }

        .leave-review .audit thead th {
            text-align: left;
            padding: 0.55rem 0.95rem;
            font-size: 0.67rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--lr-text-muted);
            background: var(--lr-bg-subtle);
            border-bottom: 1px solid var(--lr-border-strong);
            white-space: nowrap;
        }

        .leave-review .audit tbody td {
            padding: 0.6rem 0.95rem;
            border-bottom: 1px solid var(--lr-border);
            color: var(--lr-text-main);
            vertical-align: middle;
        }

        .leave-review .audit tbody tr:last-child td {
            border-bottom: none;
        }

        .leave-review .audit tbody tr:hover td {
            background: var(--lr-bg-subtle);
        }

        .leave-review .audit-action-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.14rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            background: var(--lr-accent-blue-soft);
            color: var(--lr-accent-blue);
            border: 1px solid rgba(59, 130, 246, 0.28);
            white-space: nowrap;
        }

        .leave-review .route {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 12px;
        }

        @media (max-width: 960px) {
            .leave-review .columns {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 680px) {
            .leave-review .details-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .leave-review .overview-status-box {
                align-items: flex-start;
            }
        }

        @media (max-width: 440px) {
            .leave-review .details-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
@endonce
