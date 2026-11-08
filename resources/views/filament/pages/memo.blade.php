<x-filament-panels::page>
    @include('filament.pages.partials.memo-page-styles')

    <style>
        .notice-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.25rem;
        }

        @media (max-width: 1024px) {
            .notice-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .notice-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .notice-stat-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.45rem;
            padding: 0.85rem 1.05rem;
            border-radius: 0.7rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
            transition: transform 140ms ease, box-shadow 140ms ease;
        }

        .notice-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .dark .notice-stat-card {
            border-color: #1e293b;
            background: #000000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        .dark .notice-stat-card:hover {
            border-color: #334155;
            background: #050811;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.6);
        }

        .notice-stat-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 3.5px;
        }

        .notice-stat-card.theme-blue::before {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        }
        .notice-stat-card.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }
        .notice-stat-card.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        .notice-stat-card.theme-purple::before {
            background: linear-gradient(180deg, #a855f7 0%, #7c3aed 100%);
        }

        .notice-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .notice-stat-label {
            font-size: 0.68rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgb(100, 116, 139);
        }

        .dark .notice-stat-label {
            color: #94a3b8;
        }

        .notice-stat-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.45rem;
            flex-shrink: 0;
        }

        .theme-blue .notice-stat-icon-wrapper {
            background: rgb(239, 246, 255);
            color: #2563eb;
        }
        .dark .theme-blue .notice-stat-icon-wrapper {
            background: rgba(37, 99, 235, 0.18);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }

        .theme-amber .notice-stat-icon-wrapper {
            background: rgb(254, 243, 199);
            color: #d97706;
        }
        .dark .theme-amber .notice-stat-icon-wrapper {
            background: rgba(245, 158, 11, 0.18);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .theme-emerald .notice-stat-icon-wrapper {
            background: rgb(236, 253, 245);
            color: #059669;
        }
        .dark .theme-emerald .notice-stat-icon-wrapper {
            background: rgba(16, 185, 129, 0.18);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .theme-purple .notice-stat-icon-wrapper {
            background: rgb(250, 245, 255);
            color: #7c3aed;
        }
        .dark .theme-purple .notice-stat-icon-wrapper {
            background: rgba(168, 85, 247, 0.18);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.25);
        }

        .notice-stat-value {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1.2;
            color: rgb(15, 23, 42);
        }

        .dark .notice-stat-value {
            color: #ffffff;
        }

        .notice-stat-meta {
            font-size: 0.72rem;
            color: rgb(100, 116, 139);
            margin-top: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .dark .notice-stat-meta {
            color: #94a3b8;
        }
    </style>

    @php
        $metrics = $this->getNoticeMetrics();
    @endphp

    <div class="memo-workspace">
        @include('filament.pages.partials.memo-page-header', [
            'title' => 'Employee Notice',
            'subtitle' => 'Find employees and review their notice records.',
        ])

        <div class="notice-stats-grid">
            {{-- Card 1: Total Workforce --}}
            <div class="notice-stat-card theme-blue">
                <div class="notice-stat-header">
                    <span class="notice-stat-label">Total Workforce</span>
                    <div class="notice-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-users" class="w-4 h-4" />
                    </div>
                </div>
                <div>
                    <div class="notice-stat-value">{{ number_format($metrics['total_employees']) }} Staff</div>
                    <div class="notice-stat-meta">
                        <x-filament::icon icon="heroicon-m-check-circle" class="w-3.5 h-3.5 text-blue-500" />
                        <span>Monitored personnel</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Issued Notices --}}
            <div class="notice-stat-card theme-amber">
                <div class="notice-stat-header">
                    <span class="notice-stat-label">Issued Notices</span>
                    <div class="notice-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4" />
                    </div>
                </div>
                <div>
                    <div class="notice-stat-value">{{ number_format($metrics['total_notices']) }} Notices</div>
                    <div class="notice-stat-meta">
                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-3.5 h-3.5 text-amber-500" />
                        <span>Disciplinary records</span>
                    </div>
                </div>
            </div>

            {{-- Card 3: Clean Records --}}
            <div class="notice-stat-card theme-emerald">
                <div class="notice-stat-header">
                    <span class="notice-stat-label">Spotless Standing</span>
                    <div class="notice-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-shield-check" class="w-4 h-4" />
                    </div>
                </div>
                <div>
                    <div class="notice-stat-value">{{ number_format($metrics['clean_record_staff']) }} Staff</div>
                    <div class="notice-stat-meta">
                        <x-filament::icon icon="heroicon-m-check-badge" class="w-3.5 h-3.5 text-emerald-500" />
                        <span>Zero active notices</span>
                    </div>
                </div>
            </div>

            {{-- Card 4: Notice Categories --}}
            <div class="notice-stat-card theme-purple">
                <div class="notice-stat-header">
                    <span class="notice-stat-label">Notice Categories</span>
                    <div class="notice-stat-icon-wrapper">
                        <x-filament::icon icon="heroicon-o-tag" class="w-4 h-4" />
                    </div>
                </div>
                <div>
                    <div class="notice-stat-value">{{ number_format($metrics['notice_types']) }} Types</div>
                    <div class="notice-stat-meta">
                        <x-filament::icon icon="heroicon-m-clipboard-document-list" class="w-3.5 h-3.5 text-purple-500" />
                        <span>Configured templates</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="memo-content">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
