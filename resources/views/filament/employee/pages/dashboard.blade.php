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

        .employee-dashboard {
            --ed-surface: #ffffff;
            --ed-surface-muted: #f8fafc;
            --ed-text: #0f172a;
            --ed-muted: #64748b;
            --ed-border: rgba(15, 23, 42, .12);
            --ed-blue: #2563eb;
            --ed-blue-dark: #1e3a8a;
            --ed-blue-soft: #eff6ff;

            color: var(--ed-text);
            display: grid;
            gap: 18px;
        }

        .dark .employee-dashboard {
            --ed-surface: #0f172a;
            --ed-surface-muted: #111827;
            --ed-text: #f8fafc;
            --ed-muted: #94a3b8;
            --ed-border: rgba(148, 163, 184, .24);
            --ed-blue: #60a5fa;
            --ed-blue-dark: #bfdbfe;
            --ed-blue-soft: rgba(37, 99, 235, .16);
        }

        .employee-dashboard *,
        .employee-holiday-modal * {
            box-sizing: border-box;
        }

        .employee-dashboard-panel,
        .employee-dashboard-birthday {
            background: var(--ed-surface);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
        }

        .employee-dashboard-header {
            align-items: center;
            background: linear-gradient(108deg, #075bc3 0%, #123f7b 54%, #102b55 100%);
            border: 1px solid rgba(96, 165, 250, .58);
            border-radius: 8px;
            color: #fff;
            display: flex;
            flex-wrap: wrap;
            gap: 16px 24px;
            justify-content: space-between;
            padding: 18px 22px;
        }

        .employee-dashboard-header-copy {
            min-width: 0;
        }

        .employee-dashboard-header-eyebrow {
            color: #d9f75a;
            font-size: 11px;
            font-weight: 800;
            margin: 0 0 5px;
            text-transform: uppercase;
        }

        .employee-dashboard-header h1 {
            color: #fff;
            font-size: 26px;
            font-weight: 800;
            line-height: 1.15;
            margin: 0;
        }

        .employee-dashboard-header-description {
            color: #e0ecff;
            font-size: 14px;
            margin: 6px 0 0;
        }

        .employee-dashboard-header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .employee-dashboard-header-actions button {
            align-items: center;
            background: rgba(10, 31, 68, .52);
            border: 1px solid rgba(191, 219, 254, .45);
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            font-size: 12px;
            font-weight: 750;
            gap: 7px;
            min-height: 36px;
            padding: 7px 11px;
        }

        .employee-dashboard-header-actions button:disabled {
            cursor: not-allowed;
            opacity: .76;
        }

        .employee-dashboard-header-actions svg {
            height: 16px;
            width: 16px;
        }

        .employee-dashboard-summary {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .employee-dashboard-summary-item {
            appearance: none;
            background: var(--ed-surface-muted);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            color: inherit;
            display: grid;
            gap: 7px;
            min-height: 92px;
            padding: 13px;
            text-align: left;
            width: 100%;
        }

        button.employee-dashboard-summary-item {
            cursor: pointer;
            transition: border-color 160ms ease, background-color 160ms ease, transform 160ms ease;
        }

        button.employee-dashboard-summary-item:hover,
        button.employee-dashboard-summary-item:focus-visible {
            background: var(--ed-blue-soft);
            border-color: var(--ed-blue);
            outline: none;
            transform: translateY(-1px);
        }

        .employee-dashboard-summary-top {
            align-items: center;
            color: var(--ed-blue);
            display: flex;
            justify-content: space-between;
        }

        .employee-dashboard-summary-top svg {
            height: 18px;
            width: 18px;
        }

        .employee-dashboard-summary-item strong {
            color: var(--ed-blue-dark);
            font-size: 25px;
            font-weight: 850;
            line-height: 1;
        }

        .employee-dashboard-summary-item span {
            color: var(--ed-muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
        }

        .employee-dashboard-birthday {
            align-items: center;
            border-left: 4px solid var(--ed-blue);
            display: flex;
            gap: 14px;
            padding: clamp(16px, 2.4vw, 22px);
            width: 100%;
        }

        .employee-dashboard-birthday h3 {
            color: var(--ed-text);
            font-size: clamp(21px, 2.8vw, 32px);
            font-weight: 850;
            line-height: 1.12;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .employee-dashboard-birthday p {
            color: var(--ed-muted);
            font-size: 14px;
            margin: 6px 0 0;
        }

        .employee-dashboard-main {
            display: grid;
            gap: 18px;
        }

        .employee-dashboard-panel {
            display: grid;
            gap: 15px;
            min-width: 0;
            padding: clamp(16px, 2vw, 20px);
        }

        .employee-dashboard-panel-header {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .employee-dashboard-icon {
            align-items: center;
            background: var(--ed-blue-soft);
            border-radius: 8px;
            color: var(--ed-blue);
            display: inline-flex;
            flex: 0 0 40px;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .employee-dashboard-icon svg {
            height: 21px;
            width: 21px;
        }

        .employee-dashboard-panel-header h3 {
            color: var(--ed-text);
            font-size: 16px;
            font-weight: 850;
            line-height: 1.25;
            margin: 0;
        }

        .employee-dashboard-panel-header p {
            color: var(--ed-muted);
            font-size: 12px;
            margin: 3px 0 0;
        }

        .employee-dashboard-list {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .employee-dashboard-item {
            background: var(--ed-surface-muted);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            display: grid;
            gap: 9px;
            min-width: 0;
            padding: 13px;
        }

        .employee-dashboard-item h4 {
            color: var(--ed-text);
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .employee-dashboard-item p {
            color: var(--ed-muted);
            font-size: 13px;
            line-height: 1.5;
            margin: 6px 0 0;
            overflow-wrap: anywhere;
        }

        .employee-dashboard-meta {
            align-items: center;
            color: var(--ed-muted);
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
            line-height: 1.35;
        }

        .employee-dashboard-meta svg {
            color: var(--ed-blue);
            height: 15px;
            width: 15px;
        }

        .employee-dashboard-empty {
            align-items: center;
            background: var(--ed-surface-muted);
            border: 1px dashed var(--ed-border);
            border-radius: 8px;
            color: var(--ed-muted);
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
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, .12);
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .dark .employee-holiday-modal-item {
            background: #111827;
            border-color: rgba(148, 163, 184, .24);
        }

        .employee-holiday-modal-item h4 {
            font-size: 14px;
            font-weight: 800;
            margin: 0;
        }

        .employee-holiday-modal-date {
            color: #2563eb;
            font-size: 12px;
            font-weight: 750;
            white-space: nowrap;
        }

        .dark .employee-holiday-modal-date {
            color: #60a5fa;
        }

        .employee-dashboard-charts {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .employee-dashboard-charts > * {
            min-width: 0;
        }

        .employee-dashboard-shoutout {
            background: var(--ed-surface);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            overflow: hidden;
        }

        .dark .employee-dashboard-shoutout {
            background: #111827;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .employee-dashboard-shoutout-header {
            align-items: center;
            background: var(--ed-surface-muted);
            border-bottom: 1px solid var(--ed-border);
            display: flex;
            gap: 8px;
            padding: 9px 12px;
        }

        .dark .employee-dashboard-shoutout-header {
            background: #182234;
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }

        .employee-dashboard-shoutout-header svg {
            color: var(--ed-blue);
            height: 17px;
            width: 17px;
        }

        .employee-dashboard-shoutout-header h2 {
            color: var(--ed-text);
            font-size: 14px;
            font-weight: 800;
            margin: 0;
        }

        .employee-dashboard-shoutout-action {
            align-items: center;
            background: var(--ed-blue);
            border: 1px solid var(--ed-blue);
            border-radius: 6px;
            color: #fff;
            display: inline-flex;
            font-size: 11px;
            font-weight: 750;
            gap: 6px;
            min-height: 30px;
            margin-left: auto;
            padding: 5px 9px;
        }

        .employee-dashboard-shoutout-action:disabled {
            cursor: not-allowed;
            opacity: .75;
        }

        .employee-dashboard-shoutout-action svg {
            color: currentColor;
            height: 14px;
            width: 14px;
        }

        .employee-dashboard-shoutout table {
            border-collapse: collapse;
            width: 100%;
        }

        .employee-dashboard-shoutout tbody,
        .employee-dashboard-shoutout tbody tr {
            background: var(--ed-surface) !important;
        }

        .dark .employee-dashboard-shoutout tbody,
        .dark .employee-dashboard-shoutout tbody tr {
            background: #111827 !important;
        }

        .employee-dashboard-shoutout td {
            background: transparent !important;
        }

        .employee-dashboard-shoutout tr + tr {
            border-top: 1px solid var(--ed-border);
        }

        .employee-dashboard-shoutout td {
            padding: 7px 12px;
            vertical-align: middle;
        }

        .employee-dashboard-shoutout td:first-child {
            width: 190px;
        }

        .employee-dashboard-shoutout-person {
            align-items: center;
            display: flex;
            gap: 8px;
            min-width: 0;
        }

        .employee-dashboard-shoutout-avatar {
            align-items: center;
            background: #1d4ed8;
            border: 1px solid #60a5fa;
            border-radius: 50%;
            color: #fff;
            display: inline-flex;
            flex: 0 0 28px;
            font-size: 10px;
            font-weight: 800;
            height: 28px;
            justify-content: center;
        }

        .employee-dashboard-shoutout-person strong,
        .employee-dashboard-shoutout-person small {
            display: block;
            overflow-wrap: anywhere;
        }

        .employee-dashboard-shoutout-person strong {
            color: var(--ed-text);
            font-size: 12px;
        }

        .employee-dashboard-shoutout-person small {
            color: var(--ed-muted);
            font-size: 10px;
        }

        .employee-dashboard-shoutout-message {
            background: var(--ed-surface-muted);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            color: var(--ed-text);
            font-size: 12px;
            line-height: 1.35;
            padding: 7px 10px;
        }

        .employee-dashboard-shoutout-sent-at {
            align-items: center;
            color: var(--ed-muted);
            display: flex;
            font-size: 10px;
            gap: 5px;
            justify-content: flex-end;
            margin-top: 4px;
            white-space: nowrap;
        }

        .employee-dashboard-shoutout-sent-at svg {
            height: 12px;
            width: 12px;
        }

        @media (max-width: 1024px) {
            .employee-dashboard-charts {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .employee-dashboard {
                gap: 14px;
            }

            .employee-dashboard-header {
                align-items: stretch;
                padding: 16px;
            }

            .employee-dashboard-header h1 {
                font-size: 23px;
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
                min-height: 82px;
                padding: 10px;
            }

            .employee-dashboard-summary-item strong {
                font-size: 21px;
            }

            .employee-dashboard-summary-item span {
                font-size: 10px;
            }

            .employee-dashboard-list {
                grid-template-columns: 1fr;
            }

            .employee-dashboard-shoutout tr {
                display: grid;
            }

            .employee-dashboard-shoutout tr + tr {
                border-top: 1px solid var(--ed-border);
            }

            .employee-dashboard-shoutout td {
                display: block;
                padding: 7px 10px 0;
            }

            .employee-dashboard-shoutout td:first-child {
                width: auto;
            }

            .employee-dashboard-shoutout td:last-child {
                padding-bottom: 8px;
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

        <section class="employee-dashboard-shoutout" aria-labelledby="employee-shoutout-title">
            <header class="employee-dashboard-shoutout-header">
                <x-filament::icon icon="heroicon-o-chat-bubble-left-right" />
                <h2 id="employee-shoutout-title">Shout Out Hall</h2>
                <button type="button" class="employee-dashboard-shoutout-action" disabled title="Coming soon">
                    <x-filament::icon icon="heroicon-o-paper-airplane" />
                    Send Message
                </button>
            </header>
            <table aria-label="Shout Out Hall Sample posts">
                <tbody>
                    @foreach (array_slice($shoutOutSamples, 0, 5) as $shoutOut)
                        <tr>
                            <td>
                                <div class="employee-dashboard-shoutout-person">
                                    <span class="employee-dashboard-shoutout-avatar" aria-hidden="true">{{ $shoutOut['initials'] }}</span>
                                    <span>
                                        <strong>{{ $shoutOut['name'] }}</strong>
                                        <small>{{ $shoutOut['role'] }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="employee-dashboard-shoutout-message">{{ $shoutOut['message'] }}</div>
                                <time class="employee-dashboard-shoutout-sent-at" datetime="{{ $shoutOut['sent_at']->toIso8601String() }}">
                                    <x-filament::icon icon="heroicon-o-clock" />
                                    {{ $shoutOut['sent_at']->format('M d, Y, g:i A') }}
                                </time>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <div class="employee-dashboard-charts">
            @livewire(\App\Filament\Employee\Widgets\EmployeeLatesAbsencesChart::class)
            @livewire(\App\Filament\Employee\Widgets\EmployeeDevelopmentStagePerformanceChart::class)
            @livewire(\App\Filament\Employee\Widgets\EmployeePersonalKpiRatingsChart::class)
            @livewire(\App\Filament\Employee\Widgets\EmployeeEvaluationTrainingModulesChart::class)
        </div>

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
                <div class="employee-dashboard-empty">No upcoming holidays.</div>
            @endforelse
        </div>
    </x-filament::modal>
</x-filament-panels::page>
