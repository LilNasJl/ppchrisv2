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
            --ed-page: #f8fafc;
            --ed-surface: transparent;
            --ed-surface-soft: transparent;
            --ed-text: #0f172a;
            --ed-muted: #64748b;
            --ed-border: rgba(15, 23, 42, .14);
            --ed-shadow: none;
            --ed-blue: #2563eb;
            --ed-blue-soft: #dbeafe;
            --ed-green: #059669;
            --ed-green-soft: #d1fae5;
            --ed-amber: #d97706;
            --ed-amber-soft: #fef3c7;

            color: var(--ed-text);
            display: grid;
            gap: 20px;
        }

        .dark .employee-dashboard {
            --ed-page: #020617;
            --ed-surface: transparent;
            --ed-surface-soft: transparent;
            --ed-text: #f8fafc;
            --ed-muted: #cbd5e1;
            --ed-border: rgba(148, 163, 184, .28);
            --ed-shadow: none;
            --ed-blue: #93c5fd;
            --ed-blue-soft: rgba(37, 99, 235, .18);
            --ed-green: #6ee7b7;
            --ed-green-soft: rgba(16, 185, 129, .16);
            --ed-amber: #fcd34d;
            --ed-amber-soft: rgba(245, 158, 11, .18);
        }

        .employee-dashboard * {
            box-sizing: border-box;
        }

        .employee-dashboard-hero {
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            background: var(--ed-surface);
            box-shadow: var(--ed-shadow);
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1fr) auto;
            overflow: hidden;
            padding: clamp(18px, 3vw, 28px);
        }

        .dark .employee-dashboard-hero {
            background: var(--ed-surface);
        }

        .employee-dashboard-eyebrow {
            color: var(--ed-blue);
            display: block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .employee-dashboard-hero h2 {
            color: var(--ed-text);
            font-size: 30px;
            font-weight: 850;
            line-height: 1.1;
            margin: 0;
        }

        .employee-dashboard-hero p {
            color: var(--ed-muted);
            font-size: 14px;
            line-height: 1.5;
            margin: 10px 0 0;
        }

        .employee-dashboard-summary {
            align-self: center;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(96px, 1fr));
            min-width: min(420px, 100%);
        }

        .employee-dashboard-summary-item {
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            background: transparent;
            display: grid;
            gap: 5px;
            min-height: 92px;
            padding: 12px;
        }

        .dark .employee-dashboard-summary-item {
            background: transparent;
        }

        .employee-dashboard-summary-item strong {
            color: var(--ed-text);
            font-size: 26px;
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
            background: var(--ed-surface);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            box-shadow: var(--ed-shadow);
            display: flex;
            gap: 14px;
            min-width: 0;
            padding: clamp(16px, 2.4vw, 22px);
            width: 100%;
        }

        .dark .employee-dashboard-birthday {
            background: var(--ed-surface);
        }

        .employee-dashboard-birthday h3 {
            color: var(--ed-text);
            font-size: clamp(22px, 2.8vw, 34px);
            font-weight: 900;
            line-height: 1.08;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .employee-dashboard-birthday p {
            color: var(--ed-muted);
            font-size: 14px;
            font-weight: 750;
            margin: 6px 0 0;
        }

        .employee-dashboard-main {
            display: grid;
            gap: 18px;
            grid-template-columns: 1fr;
        }

        .employee-dashboard-panel {
            background: var(--ed-surface);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            box-shadow: var(--ed-shadow);
            display: grid;
            gap: 14px;
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
            border-radius: 8px;
            display: inline-flex;
            flex: 0 0 42px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .employee-dashboard-icon svg {
            height: 22px;
            width: 22px;
        }

        .employee-dashboard-icon-blue {
            background: transparent;
            color: var(--ed-blue);
        }

        .employee-dashboard-icon-green {
            background: transparent;
            color: var(--ed-green);
        }

        .employee-dashboard-icon-amber {
            background: transparent;
            color: var(--ed-amber);
        }

        .employee-dashboard-panel-header h3 {
            color: var(--ed-text);
            font-size: 16px;
            font-weight: 850;
            line-height: 1.2;
            margin: 0;
        }

        .employee-dashboard-panel-header p {
            color: var(--ed-muted);
            font-size: 12px;
            font-weight: 650;
            line-height: 1.4;
            margin: 3px 0 0;
        }

        .employee-dashboard-list {
            display: grid;
            gap: 10px;
        }

        .employee-dashboard-list-grid {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .employee-dashboard-item {
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            background: var(--ed-surface-soft);
            display: grid;
            gap: 8px;
            min-width: 0;
            padding: 12px;
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
            line-height: 1.45;
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
            height: 15px;
            width: 15px;
        }

        .employee-dashboard-empty {
            align-items: center;
            background: var(--ed-surface-soft);
            border: 1px dashed var(--ed-border);
            border-radius: 8px;
            color: var(--ed-muted);
            display: flex;
            font-size: 13px;
            font-weight: 700;
            min-height: 96px;
            padding: 16px;
        }

        @media (max-width: 1100px) {
            .employee-dashboard-hero {
                grid-template-columns: 1fr;
            }

            .employee-dashboard-summary {
                min-width: 0;
            }
        }

        @media (max-width: 680px) {
            .employee-dashboard {
                gap: 16px;
            }

            .employee-dashboard-hero {
                padding: 18px;
            }

            .employee-dashboard-hero h2 {
                font-size: 24px;
            }

            .employee-dashboard-summary {
                grid-template-columns: 1fr;
            }

            .employee-dashboard-summary-item {
                min-height: 74px;
            }

            .employee-dashboard-birthday {
                align-items: flex-start;
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
                    <strong>{{ $announcements->count() }}</strong>
                    <span>Announcements</span>
                </div>
                <div class="employee-dashboard-summary-item">
                    <strong>{{ $upcomingHolidays->count() }}</strong>
                    <span>Upcoming holidays</span>
                </div>
                <div class="employee-dashboard-summary-item">
                    <strong>{{ $upcomingActivities->count() }}</strong>
                    <span>Upcoming activities</span>
                </div>
            </div>
        </section>

        @if ($hasBirthdayToday)
            <section class="employee-dashboard-birthday">
                <span class="employee-dashboard-icon employee-dashboard-icon-amber">
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
                    <span class="employee-dashboard-icon employee-dashboard-icon-blue">
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
                    <span class="employee-dashboard-icon employee-dashboard-icon-green">
                        <x-filament::icon icon="heroicon-o-calendar-days" />
                    </span>
                    <div>
                        <h3>Upcoming Holidays</h3>
                        <p>Scheduled holidays ahead</p>
                    </div>
                </header>

                <div class="employee-dashboard-list employee-dashboard-list-grid">
                    @forelse ($upcomingHolidays as $holiday)
                        <article class="employee-dashboard-item">
                            <h4>{{ $holiday->title }}</h4>
                            <div class="employee-dashboard-meta">
                                <x-filament::icon icon="heroicon-o-clock" />
                                <span>{{ $holiday->date->format('M d, Y') }} - {{ $holiday->type?->type }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="employee-dashboard-empty">No upcoming holidays.</div>
                    @endforelse
                </div>
            </section>

            <section class="employee-dashboard-panel">
                <header class="employee-dashboard-panel-header">
                    <span class="employee-dashboard-icon employee-dashboard-icon-amber">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" />
                    </span>
                    <div>
                        <h3>Upcoming Activities</h3>
                        <p>Company events and activities</p>
                    </div>
                </header>

                <div class="employee-dashboard-list employee-dashboard-list-grid">
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
</x-filament-panels::page>
