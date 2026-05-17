<x-filament-panels::page>
    <style>
        .holiday-page {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 24px;
        }

        .holiday-card {
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 10px;
            padding: 18px;
            background: rgba(255, 255, 255, .04);
        }

        .holiday-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .holiday-button {
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .holiday-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
            width: 100%;
        }

        .holiday-calendar {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .holiday-calendar-wrap {
            width: 100%;
            overflow: visible;
        }

        .holiday-calendar th,
        .holiday-calendar td {
            border: 1px solid rgba(148, 163, 184, .22);
            vertical-align: top;
        }

        .holiday-calendar th {
            padding: 10px 6px;
            text-align: center;
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .holiday-day {
            min-height: 92px;
            width: 100%;
            display: block;
            padding: 8px;
            text-align: left;
        }

        .holiday-muted {
            opacity: .42;
        }

        .holiday-selected {
            outline: 2px solid #3b82f6;
            outline-offset: -2px;
        }

        .holiday-number {
            display: inline-flex;
            width: 28px;
            height: 28px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-weight: 700;
        }

        .holiday-today {
            background: #2563eb;
            color: white;
        }

        .holiday-badge {
            margin-top: 8px;
            border-radius: 7px;
            background: rgba(245, 158, 11, .18);
            color: #fbbf24;
            padding: 6px;
            font-size: 12px;
            line-height: 1.25;
            overflow: hidden;
        }

        .holiday-stack {
            display: grid;
            gap: 20px;
        }

        .holiday-form {
            display: grid;
            gap: 12px;
        }

        .holiday-field label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
        }

        .holiday-field input,
        .holiday-field select,
        .holiday-field textarea {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 8px;
            padding: 8px 10px;
            background: rgba(15, 23, 42, .35);
        }

        .holiday-list {
            display: grid;
            gap: 10px;
        }

        .holiday-list-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 8px;
            padding: 10px;
        }

        @media (max-width: 1100px) {
            .holiday-page {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .holiday-card {
                padding: 12px;
            }

            .holiday-toolbar {
                gap: 8px;
            }

            .holiday-toolbar h2 {
                font-size: 17px !important;
                text-align: center;
            }

            .holiday-day {
                min-height: 72px;
                padding: 5px;
            }

            .holiday-calendar th {
                padding: 7px 2px;
                font-size: 11px;
            }

            .holiday-number {
                width: 24px;
                height: 24px;
                font-size: 12px;
            }

            .holiday-badge {
                margin-top: 5px;
                padding: 4px;
                font-size: 10px;
                line-height: 1.15;
                display: block;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .holiday-badge br {
                display: none;
            }

            .holiday-badge strong {
                font-weight: 700;
            }
        }

        @media (max-width: 430px) {
            .holiday-toolbar {
                flex-wrap: wrap;
            }

            .holiday-toolbar h2 {
                order: -1;
                width: 100%;
            }

            .holiday-button {
                flex: 1;
                padding: 7px 8px;
                font-size: 12px;
            }

            .holiday-day {
                min-height: 56px;
                padding: 4px;
            }

            .holiday-calendar th {
                font-size: 10px;
            }

            .holiday-number {
                width: 22px;
                height: 22px;
                font-size: 11px;
            }

            .holiday-badge {
                width: 8px;
                height: 8px;
                margin-top: 6px;
                padding: 0;
                border-radius: 999px;
                color: transparent;
            }

            .holiday-badge * {
                display: none;
            }
        }
    </style>

    <div class="holiday-page">
        <section class="holiday-card">
            <div class="holiday-toolbar">
                <button type="button" wire:click="previousMonth" class="holiday-button">Previous</button>
                <h2 style="font-size: 20px; font-weight: 700;">{{ $this->monthLabel }}</h2>
                <button type="button" wire:click="nextMonth" class="holiday-button">Next</button>
            </div>

            <div class="holiday-calendar-wrap">
            <table class="holiday-calendar">
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
                                        class="holiday-day {{ $day['isCurrentMonth'] ? '' : 'holiday-muted' }} {{ $day['isSelected'] ? 'holiday-selected' : '' }}"
                                    >
                                        <span class="holiday-number {{ $day['isToday'] ? 'holiday-today' : '' }}">
                                            {{ $day['day'] }}
                                        </span>

                                        @if ($day['holidayTitle'])
                                            <div class="holiday-badge">
                                                <strong>{{ $day['holidayTitle'] }}</strong><br>
                                                {{ $day['holidayType'] }}
                                            </div>
                                        @endif
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </section>

        <aside class="holiday-stack">
            <section class="holiday-card">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px;">Set Holiday</h3>

                <form wire:submit.prevent="saveHoliday" class="holiday-form">
                    <div class="holiday-field">
                        <label>Selected Date</label>
                        <input type="date" wire:model="selectedDate">
                    </div>

                    <div class="holiday-field">
                        <label>Holiday Type</label>
                        <select wire:model="holidayTypeId">
                            <option value="">Select type</option>
                            @foreach ($this->holidayTypes as $holidayType)
                                <option value="{{ $holidayType->id }}">
                                    {{ $holidayType->type }} ({{ $holidayType->rate }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="holiday-field">
                        <label>Holiday Title</label>
                        <input type="text" wire:model="holidayTitle" placeholder="e.g., Independence Day">
                    </div>

                    <button type="submit" class="holiday-button holiday-primary">Save Holiday</button>
                </form>
            </section>

        </aside>
    </div>
</x-filament-panels::page>
