<x-filament-panels::page>
    <style>
        .birthday-page {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 24px;
        }

        .birthday-card {
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 8px;
            padding: 18px;
            background: rgba(255, 255, 255, .04);
        }

        .birthday-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .birthday-button {
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .birthday-calendar {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .birthday-calendar th,
        .birthday-calendar td {
            border: 1px solid rgba(148, 163, 184, .22);
            vertical-align: top;
        }

        .birthday-calendar th {
            padding: 10px 6px;
            text-align: center;
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .birthday-day {
            min-height: 94px;
            padding: 8px;
        }

        .birthday-day-button {
            background: transparent;
            border: 0;
            color: inherit;
            cursor: pointer;
            display: block;
            text-align: left;
            transition: background-color .15s ease;
            width: 100%;
        }

        .birthday-day-button:hover {
            background: rgba(234, 179, 8, .08);
        }

        .birthday-day-button:focus-visible {
            box-shadow: inset 0 0 0 2px #eab308;
            outline: none;
        }

        .birthday-selected {
            background: rgba(37, 99, 235, .1);
            box-shadow: inset 0 0 0 2px #2563eb;
        }

        .dark .birthday-selected {
            background: rgba(96, 165, 250, .14);
            box-shadow: inset 0 0 0 2px #60a5fa;
        }

        .birthday-muted {
            opacity: .42;
        }

        .birthday-number {
            display: inline-flex;
            width: 28px;
            height: 28px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-weight: 700;
        }

        .birthday-today {
            background: #2563eb;
            color: white;
        }

        .birthday-badge {
            display: block;
            margin-top: 8px;
            border-radius: 7px;
            background: rgba(234, 179, 8, .18);
            color: #facc15;
            padding: 6px;
            font-size: 12px;
            line-height: 1.25;
            overflow: hidden;
        }

        .birthday-modal-list {
            display: grid;
            gap: 8px;
        }

        .birthday-modal-person {
            align-items: center;
            border-bottom: 1px solid rgba(148, 163, 184, .24);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 10px 0;
        }

        .birthday-modal-person:last-child {
            border-bottom: 0;
        }

        .birthday-modal-name {
            color: rgb(15, 23, 42);
            font-size: 14px;
            font-weight: 700;
        }

        .dark .birthday-modal-name {
            color: #f8fafc;
        }

        .birthday-modal-meta {
            color: #64748b;
            font-size: 12px;
            margin-top: 3px;
        }

        .dark .birthday-modal-meta {
            color: #94a3b8;
        }

        @media (max-width: 1100px) {
            .birthday-page {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .birthday-card {
                padding: 12px;
            }

            .birthday-toolbar {
                gap: 8px;
            }

            .birthday-toolbar h2 {
                font-size: 17px !important;
                text-align: center;
            }

            .birthday-day {
                min-height: 72px;
                padding: 5px;
            }

            .birthday-calendar th {
                padding: 7px 2px;
                font-size: 11px;
            }

            .birthday-number {
                width: 24px;
                height: 24px;
                font-size: 12px;
            }

            .birthday-badge {
                margin-top: 5px;
                padding: 4px;
                font-size: 10px;
                line-height: 1.15;
                white-space: nowrap;
                text-overflow: ellipsis;
            }
        }

        @media (max-width: 430px) {
            .birthday-toolbar {
                flex-wrap: wrap;
            }

            .birthday-toolbar h2 {
                order: -1;
                width: 100%;
            }

            .birthday-button {
                flex: 1;
                padding: 7px 8px;
                font-size: 12px;
            }

            .birthday-day {
                min-height: 56px;
                padding: 4px;
            }

            .birthday-calendar th {
                font-size: 10px;
            }

            .birthday-number {
                width: 22px;
                height: 22px;
                font-size: 11px;
            }

            .birthday-badge {
                width: 8px;
                height: 8px;
                margin-top: 6px;
                padding: 0;
                border-radius: 999px;
                color: transparent;
            }
        }
    </style>

    <div class="birthday-page">
        <section class="birthday-card">
            <div class="birthday-toolbar">
                <button type="button" wire:click="previousMonth" class="birthday-button">Previous</button>
                <h2 style="font-size: 20px; font-weight: 700;">{{ $this->monthLabel }}</h2>
                <button type="button" wire:click="nextMonth" class="birthday-button">Next</button>
            </div>

            <table class="birthday-calendar">
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
                                    @if ($day['birthdays']->isNotEmpty())
                                        <button
                                            type="button"
                                            wire:click="showBirthdays('{{ $day['date'] }}')"
                                            class="birthday-day birthday-day-button {{ $day['isCurrentMonth'] ? '' : 'birthday-muted' }} {{ $day['isSelected'] ? 'birthday-selected' : '' }}"
                                            aria-pressed="{{ $day['isSelected'] ? 'true' : 'false' }}"
                                            aria-label="View {{ $day['birthdays']->count() }} birthday{{ $day['birthdays']->count() === 1 ? '' : 's' }} on {{ \Carbon\Carbon::parse($day['date'])->format('F d') }}"
                                        >
                                            <span class="birthday-number {{ $day['isToday'] ? 'birthday-today' : '' }}">
                                                {{ $day['day'] }}
                                            </span>

                                            @foreach ($day['birthdays']->take(2) as $employee)
                                                <span class="birthday-badge">
                                                    {{ $employee->full_name }}
                                                </span>
                                            @endforeach
                                        </button>
                                    @else
                                        <div class="birthday-day {{ $day['isCurrentMonth'] ? '' : 'birthday-muted' }}">
                                            <span class="birthday-number {{ $day['isToday'] ? 'birthday-today' : '' }}">
                                                {{ $day['day'] }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>

    <x-filament::modal id="birthday-list-modal" width="lg">
        <x-slot name="heading">
            Birthdays on {{ $this->selectedBirthdayLabel }}
        </x-slot>

        <x-slot name="description">
            {{ count($selectedBirthdays) }} employee{{ count($selectedBirthdays) === 1 ? '' : 's' }}
        </x-slot>

        <div class="birthday-modal-list">
            @foreach ($selectedBirthdays as $birthday)
                <div class="birthday-modal-person">
                    <div>
                        <div class="birthday-modal-name">{{ $birthday['name'] }}</div>
                        <div class="birthday-modal-meta">
                            {{ $birthday['designation'] }} | {{ $birthday['branch'] }}
                        </div>
                    </div>

                    <x-filament::icon icon="heroicon-m-cake" class="h-5 w-5 text-warning-500" />
                </div>
            @endforeach
        </div>
    </x-filament::modal>
</x-filament-panels::page>
