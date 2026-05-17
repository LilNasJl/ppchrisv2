<x-filament-panels::page>
    <style>
        .activity-page {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 24px;
        }

        .activity-card {
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 8px;
            padding: 18px;
            background: rgba(255, 255, 255, .04);
        }

        .activity-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .activity-button {
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .activity-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
            width: 100%;
        }

        .activity-calendar {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .activity-calendar-wrap {
            width: 100%;
            overflow: visible;
        }

        .activity-calendar th,
        .activity-calendar td {
            border: 1px solid rgba(148, 163, 184, .22);
            vertical-align: top;
        }

        .activity-calendar th {
            padding: 10px 6px;
            text-align: center;
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .activity-day {
            min-height: 98px;
            width: 100%;
            display: block;
            padding: 8px;
            text-align: left;
        }

        .activity-muted {
            opacity: .42;
        }

        .activity-number {
            display: inline-flex;
            width: 28px;
            height: 28px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-weight: 700;
        }

        .activity-today {
            background: #2563eb;
            color: white;
        }

        .activity-badge {
            margin-top: 8px;
            border-radius: 7px;
            background: rgba(14, 165, 233, .18);
            color: #7dd3fc;
            padding: 6px;
            font-size: 12px;
            line-height: 1.25;
            overflow: hidden;
        }

        .activity-form,
        .activity-stack {
            display: grid;
            gap: 12px;
        }

        .activity-field label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
        }

        .activity-field input,
        .activity-field textarea {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 8px;
            padding: 8px 10px;
            background: rgba(15, 23, 42, .35);
        }

        .activity-list-item {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 8px;
            padding: 10px;
            display: grid;
            gap: 8px;
        }

        @media (max-width: 1100px) {
            .activity-page {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .activity-card {
                padding: 12px;
            }

            .activity-toolbar {
                gap: 8px;
            }

            .activity-toolbar h2 {
                font-size: 17px !important;
                text-align: center;
            }

            .activity-day {
                min-height: 72px;
                padding: 5px;
            }

            .activity-calendar th {
                padding: 7px 2px;
                font-size: 11px;
            }

            .activity-number {
                width: 24px;
                height: 24px;
                font-size: 12px;
            }

            .activity-badge {
                margin-top: 5px;
                padding: 4px;
                font-size: 10px;
                line-height: 1.15;
                display: block;
                white-space: nowrap;
                text-overflow: ellipsis;
            }
        }

        @media (max-width: 430px) {
            .activity-toolbar {
                flex-wrap: wrap;
            }

            .activity-toolbar h2 {
                order: -1;
                width: 100%;
            }

            .activity-button {
                flex: 1;
                padding: 7px 8px;
                font-size: 12px;
            }

            .activity-day {
                min-height: 56px;
                padding: 4px;
            }

            .activity-calendar th {
                font-size: 10px;
            }

            .activity-number {
                width: 22px;
                height: 22px;
                font-size: 11px;
            }

            .activity-badge {
                width: 8px;
                height: 8px;
                margin-top: 6px;
                padding: 0;
                border-radius: 999px;
                color: transparent;
            }
        }
    </style>

    <div class="activity-page">
        <section class="activity-card">
            <div class="activity-toolbar">
                <button type="button" wire:click="previousMonth" class="activity-button">Previous</button>
                <h2 style="font-size: 20px; font-weight: 700;">{{ $this->monthLabel }}</h2>
                <button type="button" wire:click="nextMonth" class="activity-button">Next</button>
            </div>

            <div class="activity-calendar-wrap">
            <table class="activity-calendar">
                <thead>
                    <tr>
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                            <th>{{ $dayName }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_chunk($this->calendarDays, 7) as $week)
                        <tr>
                            @foreach ($week as $day)
                                <td>
                                    <button
                                        type="button"
                                        wire:click="selectDate('{{ $day['date'] }}')"
                                        class="activity-day {{ $day['isCurrentMonth'] ? '' : 'activity-muted' }}"
                                    >
                                        <span class="activity-number {{ $day['isToday'] ? 'activity-today' : '' }}">
                                            {{ $day['day'] }}
                                        </span>

                                        @foreach ($day['activities']->take(2) as $activity)
                                            <div class="activity-badge">
                                                {{ $activity->title }}
                                            </div>
                                        @endforeach
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </section>

        <aside class="activity-stack">
            <section class="activity-card">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px;">
                    {{ $this->editingActivityId ? 'Update Activity' : 'Add Activity' }}
                </h3>

                <form wire:submit.prevent="saveActivity" class="activity-form">
                    <div class="activity-field">
                        <label>Activity Title</label>
                        <input type="text" wire:model="activityTitle" placeholder="e.g., Company Meeting">
                    </div>

                    <div class="activity-field">
                        <label>Description</label>
                        <textarea rows="3" wire:model="activityDescription" placeholder="Activity details"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                        <div class="activity-field">
                            <label>Range From</label>
                            <input type="date" wire:model="dateFrom">
                        </div>
                        <div class="activity-field">
                            <label>Range To</label>
                            <input type="date" wire:model="dateTo">
                        </div>
                    </div>

                    <button type="submit" class="activity-button activity-primary">
                        {{ $this->editingActivityId ? 'Save Changes' : 'Save Activity' }}
                    </button>

                    @if ($this->editingActivityId)
                        <button type="button" wire:click="resetActivityForm" class="activity-button">
                            Cancel Edit
                        </button>
                    @endif
                </form>
            </section>

            <section class="activity-card">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 8px;">Activity List</h3>
                <p style="color: #94a3b8; font-size: 13px;">
                    Use the View Activities header button to open the full activity table for editing and deleting records.
                </p>
            </section>
        </aside>
    </div>
</x-filament-panels::page>
