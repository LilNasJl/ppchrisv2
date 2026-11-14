<x-filament-panels::page>
    @php
        $announcements = $this->announcements;
        $upcomingHolidays = $this->upcomingHolidays;
        $upcomingActivities = $this->upcomingActivities;
        $employee = auth()->user()?->employee;
        $userName = filled($employee?->firstname) ? $employee->firstname : (auth()->user()?->name ?? 'Employee');
        $birthdate = $employee?->birthdate;
        $hasBirthdayToday = $birthdate
            && (int) $birthdate->format('m') === (int) now()->format('m')
            && (int) $birthdate->format('d') === (int) now()->format('d');
        $shoutOutSamples = [
            ['name' => 'Lorem Ipsum', 'role' => 'Sample employee', 'initials' => 'LI', 'message' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'sent_at' => now()->copy()->subMinutes(18)],
            ['name' => 'Dolor Sit', 'role' => 'Sample employee', 'initials' => 'DS', 'message' => 'Sed do eiusmod tempor incididunt ut labore.', 'sent_at' => now()->copy()->subMinutes(42)],
            ['name' => 'Amet Consectetur', 'role' => 'Sample employee', 'initials' => 'AC', 'message' => 'Ut enim ad minim veniam, quis nostrud exercitation.', 'sent_at' => now()->copy()->subHours(3)],
            ['name' => 'Adipiscing Elit', 'role' => 'Sample employee', 'initials' => 'AE', 'message' => 'Duis aute irure dolor in reprehenderit.', 'sent_at' => now()->copy()->subDay()],
            ['name' => 'Tempor Incididunt', 'role' => 'Sample employee', 'initials' => 'TI', 'message' => 'Excepteur sint occaecat cupidatat non proident.', 'sent_at' => now()->copy()->subDays(2)],
        ];
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        /* Full Width Container Enforcement */
        .fi-page,
        .fi-page-header-main-ctn,
        .fi-page-main,
        .fi-page-content,
        .employee-dashboard,
        .employee-dashboard-master-grid {
            width: 100% !important;
            max-width: 100% !important;
        }

        .employee-dashboard {
            --ed-surface: #000000;
            --ed-surface-muted: #0a1122;
            --ed-card-surface: #0a1122;
            --ed-text: #f8fafc;
            --ed-muted: #94a3b8;
            --ed-border: #1e293b;
            --ed-blue: #60a5fa;
            --ed-blue-dark: #93c5fd;
            --ed-blue-soft: rgba(30, 64, 175, 0.2);

            color: #f8fafc;
            display: block;
            width: 100%;
        }

        .dark .employee-dashboard {
            --ed-surface: #000000;
            --ed-surface-muted: #0a1122;
            --ed-card-surface: #0a1122;
            --ed-text: #f8fafc;
            --ed-muted: #94a3b8;
            --ed-border: #1e293b;
            --ed-blue: #60a5fa;
            --ed-blue-dark: #93c5fd;
            --ed-blue-soft: rgba(30, 64, 175, 0.2);
        }

        .employee-dashboard *,
        .employee-holiday-modal * {
            box-sizing: border-box;
        }

        /* Master Split Layout */
        .employee-dashboard-master-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            align-items: start;
            width: 100%;
        }

        .employee-dashboard-left-col {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-width: 0;
            width: 100%;
        }

        .employee-dashboard-right-col {
            min-width: 0;
            width: 100%;
        }

        /* Desktop 2-Column Split & Sticky Side Panel (>= 1280px) */
        @media (min-width: 1280px) {
            .employee-dashboard-master-grid {
                grid-template-columns: minmax(0, 8fr) minmax(0, 4fr);
            }

            .employee-dashboard-right-col,
            .employee-dashboard-shoutout {
                position: sticky !important;
                top: 5rem !important;
                align-self: start !important;
                height: fit-content !important;
            }

            .employee-dashboard-shoutout-feed {
                max-height: calc(100vh - 12rem);
                min-height: 500px;
            }
        }

        /* Tablet & Stacked Layout (<= 1279px) */
        @media (max-width: 1279px) {
            .employee-dashboard-right-col,
            .employee-dashboard-shoutout {
                position: static !important;
                top: auto !important;
                align-self: auto !important;
            }

            .employee-dashboard-shoutout-feed {
                max-height: 340px;
            }
        }

        /* Gradient Header Banner */

        .dark .employee-dashboard-header {
            border-color: #1e293b !important;
            background: linear-gradient(110deg, #0f274a, #08152b 68%, #030814) !important;
        }
        .employee-dashboard-header {
            align-items: center;
            background: linear-gradient(110deg, #1257b8 0%, #173c70 65%, #102a50 100%);
            border: 1px solid rgba(147, 197, 253, 0.35);
            border-radius: 10px;
            color: #fff;
            display: flex;
            flex-wrap: wrap;
            gap: 12px 20px;
            justify-content: space-between;
            padding: 14px 20px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.4);
        }

        .employee-dashboard-header-copy {
            min-width: 0;
        }

        .employee-dashboard-header-eyebrow {
            color: #93c5fd;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.05em;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .employee-dashboard-header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.2;
            margin: 0;
        }

        .employee-dashboard-header-description {
            color: #dbeafe;
            font-size: 13px;
            margin: 4px 0 0;
        }

        .employee-dashboard-header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .employee-dashboard-header-actions button {
            align-items: center;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 6px;
            color: #ffffff;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
            min-height: 32px;
            padding: 6px 12px;
            transition: background-color 150ms ease, border-color 150ms ease, transform 150ms ease;
        }

        .employee-dashboard-header-actions button:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.24);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
        }

        .employee-dashboard-header-actions button:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }

        .employee-dashboard-header-actions svg {
            color: #93c5fd;
            height: 15px;
            width: 15px;
        }

        /* Summary Cards */
        .employee-dashboard-summary {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .employee-dashboard-summary-item {
            appearance: none;
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-radius: 8px;
            color: inherit;
            display: grid;
            gap: 6px;
            min-height: 84px;
            padding: 12px 14px;
            text-align: left;
            width: 100%;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.4);
            cursor: pointer;
            transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .employee-dashboard-summary-item:hover,
        .employee-dashboard-summary-item:focus-visible {
            border-color: #3b82f6 !important;
            outline: none;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px -1px rgba(0, 0, 0, 0.6);
        }

        .employee-dashboard-summary-top {
            align-items: center;
            color: #60a5fa;
            display: flex;
            justify-content: space-between;
        }

        .employee-dashboard-summary-top svg {
            height: 18px;
            width: 18px;
        }

        .employee-dashboard-summary-item strong {
            color: #f8fafc;
            font-size: 24px;
            font-weight: 850;
            line-height: 1;
        }

        .employee-dashboard-summary-item span {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
        }

        /* Birthday Card */
        .employee-dashboard-birthday {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-left: 4px solid #3b82f6 !important;
            border-radius: 8px;
            align-items: center;
            display: flex;
            gap: 14px;
            padding: 16px 20px;
            width: 100%;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.4);
        }

        .employee-dashboard-birthday h3 {
            color: #f8fafc;
            font-size: clamp(18px, 2.4vw, 24px);
            font-weight: 850;
            line-height: 1.15;
            margin: 0;
        }

        .employee-dashboard-birthday p {
            color: #94a3b8;
            font-size: 13px;
            margin: 4px 0 0;
        }

        .employee-dashboard-icon {
            align-items: center;
            background: rgba(30, 64, 175, 0.2);
            border-radius: 8px;
            color: #60a5fa;
            display: inline-flex;
            flex: 0 0 38px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .employee-dashboard-icon svg {
            height: 20px;
            width: 20px;
        }

        /* Chart Grid Layout */
        .employee-dashboard-charts {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (max-width: 1100px) {
            .employee-dashboard-charts {
                grid-template-columns: 1fr;
            }
        }

        /* Chart Widgets Dark Theme */
        .employee-dashboard .fi-wi-chart {
            min-width: 0;
            width: 100%;
        }

        .employee-dashboard .fi-wi-chart .fi-section {
            border: 1px solid #1e293b !important;
            border-radius: 10px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.4);
            overflow: hidden;
            background: #000000 !important;
        }

        .employee-dashboard .fi-wi-chart .fi-section-header {
            background: #0a1122 !important;
            border-bottom: 1px solid #1e293b !important;
            min-height: 2.35rem;
            padding: 0.5rem 0.85rem;
            align-items: center;
        }

        .employee-dashboard .fi-wi-chart .fi-section-header-heading {
            color: #93c5fd !important;
            font-size: 0.875rem !important;
            font-weight: 750 !important;
        }

        .employee-dashboard .fi-wi-chart .fi-section-header-description {
            font-size: 0.75rem !important;
            color: #94a3b8 !important;
            margin-top: 0.05rem !important;
        }

        .employee-dashboard .fi-wi-chart .fi-section-content {
            background: #000000 !important;
            padding: 0.75rem;
        }

        .employee-dashboard .fi-wi-chart .fi-section-header-actions .fi-input-wrp {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            color: #f8fafc !important;
            min-height: 1.85rem !important;
            height: 1.85rem !important;
            border-radius: 6px !important;
            font-size: 0.725rem !important;
        }

        .employee-dashboard .fi-wi-chart .fi-section-header-actions .fi-input-wrp input,
        .employee-dashboard .fi-wi-chart .fi-section-header-actions .fi-input-wrp select {
            font-size: 0.725rem !important;
            padding-block: 0.1rem !important;
        }

        /* Shout Out Hall Side Panel */
        .employee-dashboard-shoutout {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .employee-dashboard-shoutout-header {
            align-items: center;
            background: #0a1122 !important;
            border-bottom: 1px solid #1e293b !important;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 12px;
            flex: 0 0 auto;
        }

        .employee-dashboard-shoutout-header-title {
            align-items: center;
            display: flex;
            gap: 6px;
        }

        .employee-dashboard-shoutout-header-title > svg {
            color: #60a5fa;
            height: 16px;
            width: 16px;
        }

        .employee-dashboard-shoutout-header h2 {
            color: #93c5fd !important;
            font-size: 0.8125rem;
            font-weight: 750;
            margin: 0;
        }

        .employee-dashboard-shoutout-tag {
            background: rgba(59, 130, 246, 0.2) !important;
            color: #60a5fa !important;
            font-size: 0.625rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .employee-dashboard-shoutout-action {
            align-items: center;
            background: #1d4ed8;
            border: 1px solid #2563eb;
            border-radius: 6px;
            color: #fff;
            display: inline-flex;
            font-size: 0.6875rem;
            font-weight: 750;
            gap: 5px;
            min-height: 24px;
            height: 24px;
            padding: 2px 8px;
            opacity: 0.9;
            transition: opacity 150ms ease;
        }

        .employee-dashboard-shoutout-action:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }

        .employee-dashboard-shoutout-action svg {
            color: currentColor;
            height: 11px;
            width: 11px;
        }

        .employee-dashboard-shoutout-feed {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 10px;
            overflow-y: auto;
        }

        .employee-dashboard-shoutout-feed::-webkit-scrollbar {
            width: 5px;
        }

        .employee-dashboard-shoutout-feed::-webkit-scrollbar-track {
            background: transparent;
        }

        .employee-dashboard-shoutout-feed::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 9999px;
        }

        .employee-dashboard-shoutout-item {
            background: #0a1122 !important;
            border: 1px solid #1e293b !important;
            border-radius: 8px;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: border-color 150ms ease;
        }

        .employee-dashboard-shoutout-item:hover {
            border-color: rgba(59, 130, 246, 0.4) !important;
        }

        .employee-dashboard-shoutout-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .employee-dashboard-shoutout-person {
            align-items: center;
            display: flex;
            gap: 8px;
            min-width: 0;
        }

        .employee-dashboard-shoutout-avatar {
            align-items: center;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            border: 1px solid rgba(147, 197, 253, 0.5);
            border-radius: 50%;
            color: #fff;
            display: inline-flex;
            flex: 0 0 24px;
            font-size: 9px;
            font-weight: 750;
            height: 24px;
            width: 24px;
            justify-content: center;
        }

        .employee-dashboard-shoutout-meta {
            min-width: 0;
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .employee-dashboard-shoutout-meta strong {
            color: #f8fafc !important;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .employee-dashboard-shoutout-meta small {
            color: #94a3b8 !important;
            font-size: 0.65rem;
        }

        .employee-dashboard-shoutout-sent-at {
            align-items: center;
            color: #94a3b8 !important;
            display: flex;
            font-size: 0.625rem;
            gap: 3px;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .employee-dashboard-shoutout-sent-at svg {
            height: 10px;
            width: 10px;
        }

        .employee-dashboard-shoutout-message {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-radius: 6px;
            color: #f8fafc !important;
            font-size: 0.75rem;
            line-height: 1.35;
            padding: 6px 9px;
        }

        .employee-dashboard-shoutout-message p {
            margin: 0;
        }

        .employee-dashboard-shoutout-footer {
            align-items: center;
            border-top: 1px solid #1e293b !important;
            background: #000000 !important;
            color: #94a3b8 !important;
            display: flex;
            font-size: 0.65rem;
            gap: 5px;
            padding: 6px 10px;
            flex: 0 0 auto;
        }

        .employee-dashboard-shoutout-dot {
            background: #22c55e;
            border-radius: 50%;
            display: inline-block;
            height: 6px;
            width: 6px;
            box-shadow: 0 0 6px #22c55e;
        }

        /* Modals & Panels */
        .employee-dashboard-panel {
            background: #000000 !important;
            border: 1px solid #1e293b !important;
            border-radius: 8px;
            display: grid;
            gap: 15px;
            min-width: 0;
            padding: 16px 20px;
        }

        .employee-dashboard-panel-header {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .employee-dashboard-panel-header h3 {
            color: #f8fafc;
            font-size: 16px;
            font-weight: 850;
            line-height: 1.25;
            margin: 0;
        }

        .employee-dashboard-panel-header p {
            color: #94a3b8;
            font-size: 12px;
            margin: 3px 0 0;
        }

        .employee-dashboard-list {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .employee-dashboard-item {
            background: #0a1122 !important;
            border: 1px solid #1e293b !important;
            border-radius: 8px;
            display: grid;
            gap: 9px;
            min-width: 0;
            padding: 13px;
        }

        .employee-dashboard-item h4 {
            color: #f8fafc !important;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .employee-dashboard-item p {
            color: #94a3b8 !important;
            font-size: 13px;
            line-height: 1.5;
            margin: 6px 0 0;
            overflow-wrap: anywhere;
        }

        .employee-dashboard-meta {
            align-items: center;
            color: #94a3b8;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
            line-height: 1.35;
        }

        .employee-dashboard-meta svg {
            color: #60a5fa;
            height: 15px;
            width: 15px;
        }

        .employee-dashboard-empty {
            align-items: center;
            background: #0a1122 !important;
            border: 1px dashed #1e293b !important;
            border-radius: 8px;
            color: #94a3b8 !important;
            display: flex;
            font-size: 13px;
            font-weight: 700;
            min-height: 82px;
            padding: 16px;
        }

        .employee-holiday-modal {
            display: grid;
            gap: 10px;
        }

        .employee-holiday-modal-item {
            align-items: center;
            background: #0a1122 !important;
            border: 1px solid #1e293b !important;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .employee-holiday-modal-item h4 {
            color: #f8fafc !important;
            font-size: 14px;
            font-weight: 800;
            margin: 0;
        }

        .employee-holiday-modal-date {
            color: #60a5fa !important;
            font-size: 12px;
            font-weight: 750;
            white-space: nowrap;
        }

        /* Mobile Adjustments */
        @media (max-width: 640px) {
            .employee-dashboard-header {
                align-items: stretch;
                padding: 14px;
            }

            .employee-dashboard-header h1 {
                font-size: 20px;
            }

            .employee-dashboard-header-actions {
                width: 100%;
            }

            .employee-dashboard-header-actions button {
                flex: 1 1 auto;
                justify-content: center;
            }

            .employee-dashboard-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .employee-dashboard-summary-item {
                min-height: 76px;
                padding: 10px;
            }

            .employee-dashboard-summary-item strong {
                font-size: 20px;
            }

            .employee-dashboard-summary-item span {
                font-size: 10px;
            }

            .employee-dashboard-list {
                grid-template-columns: 1fr;
            }

            .employee-dashboard-birthday {
                align-items: flex-start;
            }

            .employee-holiday-modal-item {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="employee-dashboard">
        <div class="employee-dashboard-master-grid">
            <!-- Left Column: Core Employee Self Service & Analytics -->
            <div class="employee-dashboard-left-col">
                <header class="employee-dashboard-header">
                    <div class="employee-dashboard-header-copy">
                        <p class="employee-dashboard-header-eyebrow">Employee Self Service</p>
                        <h1>My Dashboard</h1>
                        <p class="employee-dashboard-header-description">Updates, attendance, and development in one place.</p>
                    </div>
                    <div class="employee-dashboard-header-actions" aria-label="Request forms">
                        <button type="button" disabled title="Coming soon">
                            <x-filament::icon icon="heroicon-o-document-text" />
                            Leave Form
                        </button>
                        <button type="button" disabled title="Coming soon">
                            <x-filament::icon icon="heroicon-o-clock" />
                            OT Form
                        </button>
                        <button type="button" disabled title="Coming soon">
                            <x-filament::icon icon="heroicon-o-arrows-right-left" />
                            Change Shift
                        </button>
                    </div>
                </header>

                <section class="employee-dashboard-summary" aria-label="Dashboard summary">
                    <button type="button" class="employee-dashboard-summary-item" wire:click="showAnnouncements" aria-label="View announcements">
                        <div class="employee-dashboard-summary-top">
                            <x-filament::icon icon="heroicon-o-megaphone" />
                            <x-filament::icon icon="heroicon-m-chevron-right" />
                        </div>
                        <strong>{{ $announcements->count() }}</strong>
                        <span>Announcements</span>
                    </button>

                    <button
                        type="button"
                        class="employee-dashboard-summary-item"
                        wire:click="showUpcomingHolidays"
                        wire:loading.attr="disabled"
                        wire:target="showUpcomingHolidays"
                        aria-label="View upcoming holidays"
                    >
                        <div class="employee-dashboard-summary-top">
                            <x-filament::icon icon="heroicon-o-calendar-days" />
                            <x-filament::icon icon="heroicon-m-chevron-right" />
                        </div>
                        <strong>{{ $upcomingHolidays->count() }}</strong>
                        <span>Upcoming holidays</span>
                    </button>

                    <button type="button" class="employee-dashboard-summary-item" wire:click="showUpcomingActivities" aria-label="View upcoming activities">
                        <div class="employee-dashboard-summary-top">
                            <x-filament::icon icon="heroicon-o-clipboard-document-list" />
                            <x-filament::icon icon="heroicon-m-chevron-right" />
                        </div>
                        <strong>{{ $upcomingActivities->count() }}</strong>
                        <span>Upcoming activities</span>
                    </button>
                </section>

                @if ($hasBirthdayToday)
                    <section class="employee-dashboard-birthday">
                        <span class="employee-dashboard-icon">
                            <x-filament::icon icon="heroicon-o-cake" />
                        </span>
                        <div>
                            <h3>Happy Birthday, {{ $employee?->firstname ?: $userName }}</h3>
                            <p>Wishing you a wonderful birthday today.</p>
                        </div>
                    </section>
                @endif

                <div class="employee-dashboard-charts">
                    @livewire(\App\Filament\Employee\Widgets\EmployeeLatesAbsencesChart::class)
                    @livewire(\App\Filament\Employee\Widgets\EmployeeDevelopmentStagePerformanceChart::class)
                    @livewire(\App\Filament\Employee\Widgets\EmployeePersonalKpiRatingsChart::class)
                    @livewire(\App\Filament\Employee\Widgets\EmployeeEvaluationTrainingModulesChart::class)
                </div>
            </div>

            <!-- Right Column: Tall Side Panel (Shout Out Hall) -->
            <div class="employee-dashboard-right-col">
                <section class="employee-dashboard-shoutout" aria-labelledby="employee-shoutout-title">
                    <header class="employee-dashboard-shoutout-header">
                        <div class="employee-dashboard-shoutout-header-title">
                            <x-filament::icon icon="heroicon-o-chat-bubble-left-right" />
                            <h2 id="employee-shoutout-title">Shout Out Hall</h2>
                            <span class="employee-dashboard-shoutout-tag">Live Feed</span>
                        </div>
                        <button type="button" class="employee-dashboard-shoutout-action" disabled title="Coming soon">
                            <x-filament::icon icon="heroicon-o-paper-airplane" />
                            <span>Send Message</span>
                        </button>
                    </header>
                    <div class="employee-dashboard-shoutout-feed" role="feed" aria-label="Recent Shout Out posts and Sample posts">
                        @foreach (array_slice($shoutOutSamples, 0, 5) as $shoutOut)
                            <article class="employee-dashboard-shoutout-item">
                                <div class="employee-dashboard-shoutout-top">
                                    <div class="employee-dashboard-shoutout-person">
                                        <span class="employee-dashboard-shoutout-avatar" aria-hidden="true">{{ $shoutOut['initials'] }}</span>
                                        <div class="employee-dashboard-shoutout-meta">
                                            <strong>{{ $shoutOut['name'] }}</strong>
                                            <small>{{ $shoutOut['role'] }}</small>
                                        </div>
                                    </div>
                                    <time class="employee-dashboard-shoutout-sent-at" datetime="{{ $shoutOut['sent_at']->toIso8601String() }}">
                                        <x-filament::icon icon="heroicon-o-clock" />
                                        {{ $shoutOut['sent_at']->format('M d, g:i A') }}
                                    </time>
                                </div>
                                <div class="employee-dashboard-shoutout-message">
                                    <p>{{ $shoutOut['message'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <footer class="employee-dashboard-shoutout-footer">
                        <span class="employee-dashboard-shoutout-dot" aria-hidden="true"></span>
                        <span>Recent employee shout-outs and team recognitions</span>
                    </footer>
                </section>
            </div>
        </div>
    </div>

    <x-filament::modal id="employee-announcements" width="2xl">
        <x-slot name="heading">Announcements</x-slot>
        <div class="employee-dashboard employee-dashboard-main">
            <section class="employee-dashboard-panel">
                <header class="employee-dashboard-panel-header">
                    <span class="employee-dashboard-icon">
                        <x-filament::icon icon="heroicon-o-megaphone" />
                    </span>
                    <div>
                        <h3>Announcements</h3>
                        <p>Latest published HR updates</p>
                    </div>
                </header>

                <div class="employee-dashboard-list">
                    @forelse ($announcements as $announcement)
                        <article class="employee-dashboard-item">
                            <div>
                                <h4>{{ $announcement->title }}</h4>
                                @if (filled($announcement->description))
                                    <p>{{ $announcement->description }}</p>
                                @endif
                            </div>
                            <div class="employee-dashboard-meta">
                                <x-filament::icon icon="heroicon-o-calendar" />
                                <span>{{ $announcement->published_at?->format('M d, Y') }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="employee-dashboard-empty">No announcements.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </x-filament::modal>

    <x-filament::modal id="employee-upcoming-activities" width="2xl">
        <x-slot name="heading">Upcoming Activities</x-slot>
        <div class="employee-dashboard employee-dashboard-main">
            <section class="employee-dashboard-panel">
                <header class="employee-dashboard-panel-header">
                    <span class="employee-dashboard-icon">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" />
                    </span>
                    <div>
                        <h3>Upcoming Activities</h3>
                        <p>Company events and activities</p>
                    </div>
                </header>

                <div class="employee-dashboard-list">
                    @forelse ($upcomingActivities as $activity)
                        <article class="employee-dashboard-item">
                            <h4>{{ $activity->title }}</h4>
                            <div class="employee-dashboard-meta">
                                <x-filament::icon icon="heroicon-o-calendar" />
                                <span>{{ $activity->date_from->format('M d, Y') }} - {{ $activity->date_to->format('M d, Y') }}</span>
                            </div>
                            @if (filled($activity->description))
                                <p>{{ $activity->description }}</p>
                            @endif
                        </article>
                    @empty
                        <div class="employee-dashboard-empty">No upcoming activities.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </x-filament::modal>

    <x-filament::modal id="employee-upcoming-holidays" width="2xl">
        <x-slot name="heading">Upcoming Holidays</x-slot>
        <x-slot name="description">National holidays scheduled from today onward.</x-slot>

        <div class="employee-holiday-modal">
            @forelse ($upcomingHolidays as $holiday)
                <article class="employee-holiday-modal-item">
                    <div>
                        <h4>{{ $holiday->title }}</h4>
                        <div class="employee-dashboard-meta">
                            {{ $holiday->type?->type ?: 'Holiday' }}
                        </div>
                    </div>
                    <div class="employee-holiday-modal-date">
                        {{ \Illuminate\Support\Carbon::parse($holiday->occurrence_date)->format('M d, Y') }}
                    </div>
                </article>
            @empty
                <div class="employee-holiday-modal-item">
                    <div class="employee-dashboard-empty">No upcoming holidays.</div>
                </div>
            @endforelse
        </div>
    </x-filament::modal>
</x-filament-panels::page>
