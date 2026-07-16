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
    @endphp

    <style>
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

        .employee-dashboard-hero,
        .employee-dashboard-panel,
        .employee-dashboard-birthday {
            background: var(--ed-surface);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
        }

        .employee-dashboard-hero {
            border-top: 3px solid var(--ed-blue);
            display: grid;
            gap: 20px;
            grid-template-columns: minmax(0, 1fr) minmax(360px, .9fr);
            padding: clamp(18px, 3vw, 28px);
        }

        .employee-dashboard-eyebrow {
            color: var(--ed-blue);
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .employee-dashboard-hero h2 {
            color: var(--ed-text);
            font-size: clamp(24px, 3vw, 32px);
            font-weight: 850;
            line-height: 1.15;
            margin: 0;
        }

        .employee-dashboard-hero p {
            color: var(--ed-muted);
            font-size: 14px;
            margin: 9px 0 0;
        }

        .employee-dashboard-summary {
            align-self: center;
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

        @media (max-width: 1024px) {
            .employee-dashboard-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .employee-dashboard {
                gap: 14px;
            }

            .employee-dashboard-hero {
                padding: 17px;
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
        <section class="employee-dashboard-hero">
            <div>
                <span class="employee-dashboard-eyebrow">Employee Self Service</span>
                <h2>Welcome back, {{ $userName }}</h2>
                <p>{{ now()->format('l, F d, Y') }}</p>
            </div>

            <div class="employee-dashboard-summary" aria-label="Dashboard summary">
                <div class="employee-dashboard-summary-item">
                    <div class="employee-dashboard-summary-top">
                        <x-filament::icon icon="heroicon-o-megaphone" />
                    </div>
                    <strong>{{ $announcements->count() }}</strong>
                    <span>Announcements</span>
                </div>

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

                <div class="employee-dashboard-summary-item">
                    <div class="employee-dashboard-summary-top">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" />
                    </div>
                    <strong>{{ $upcomingActivities->count() }}</strong>
                    <span>Upcoming activities</span>
                </div>
            </div>
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

        <div class="employee-dashboard-main">
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
                        </article>
                    @empty
                        <div class="employee-dashboard-empty">No upcoming activities.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

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
                        {{ $holiday->date->format('M d, Y') }}
                    </div>
                </article>
            @empty
                <div class="employee-dashboard-empty">No upcoming holidays.</div>
            @endforelse
        </div>
    </x-filament::modal>
</x-filament-panels::page>
