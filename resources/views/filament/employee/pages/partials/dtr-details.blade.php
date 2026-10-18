@php
    $attendanceUnits = app(\App\Services\DtrAttendanceUnitService::class);
    $resolvedDayPart = $record instanceof \App\Models\Dtr
        ? $attendanceUnits->dayPartForRecord($record)
        : ($record->day_part ?? null);

    $dayUnits = 0.0;
    if ($record instanceof \App\Models\Dtr) {
        try {
            $dayUnits = (float) $attendanceUnits->recordAttendanceUnits($record);
        } catch (\Throwable) {
            $dayUnits = 0.0;
        }
    } else {
        $dayUnits = (float) ($record->day_count ?? 1.0);
    }
    
    $statusClass = match ($status) {
        'Absent' => 'is-danger',
        'Leave' => 'is-info',
        'For Approval', 'Overtime' => 'is-warning',
        default => 'is-success',
    };
@endphp

<style>
    .dtr-details-modal-wrapper {
        --modal-border: rgba(226, 232, 240, 0.9);
        --modal-bg-subtle: #f8fafc;
        --modal-bg-box: #ffffff;
        --modal-text-main: #0f172a;
        --modal-text-muted: #64748b;
        --modal-blue: #2563eb;
        display: flex;
        flex-direction: column;
        gap: 12px;
        color: var(--modal-text-main);
    }

    .dark .dtr-details-modal-wrapper {
        --modal-border: rgba(51, 65, 85, 0.8);
        --modal-bg-subtle: #1e293b;
        --modal-bg-box: #0f172a;
        --modal-text-main: #f8fafc;
        --modal-text-muted: #94a3b8;
        --modal-blue: #60a5fa;
    }

    /* Top Summary Banner */
    .dtr-modal-banner {
        background: linear-gradient(135deg, var(--modal-bg-subtle), var(--modal-bg-box));
        border: 1px solid var(--modal-border);
        border-left: 4px solid var(--modal-blue);
        border-radius: 8px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .dtr-modal-banner-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .dtr-modal-banner-title {
        font-size: 13.5px;
        font-weight: 800;
        color: var(--modal-text-main);
    }

    .dtr-modal-banner-sub {
        font-size: 11.5px;
        color: var(--modal-text-muted);
    }

    /* Grouped Sections */
    .dtr-modal-section {
        border: 1px solid var(--modal-border);
        border-radius: 8px;
        overflow: hidden;
        background: var(--modal-bg-box);
    }

    .dtr-modal-section-header {
        background: var(--modal-bg-subtle);
        border-bottom: 1px solid var(--modal-border);
        padding: 7px 12px;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--modal-text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .dtr-modal-grid {
        display: grid;
        gap: 1px;
        background: var(--modal-border);
    }

    .dtr-modal-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .dtr-modal-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .dtr-modal-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .dtr-modal-item {
        background: var(--modal-bg-box);
        padding: 8px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .dtr-modal-item-label {
        font-size: 9.5px;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--modal-text-muted);
        margin-bottom: 2px;
    }

    .dtr-modal-item-value {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--modal-text-main);
        word-break: break-word;
    }

    .dtr-modal-item-value.mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-variant-numeric: tabular-nums;
    }

    /* Badges */
    .dtr-metric-pill {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 9999px;
        width: fit-content;
        letter-spacing: 0.01em;
    }
    .dtr-metric-pill.is-danger { background-color: #fee2e2; color: #991b1b; }
    .dark .dtr-metric-pill.is-danger { background-color: rgba(239, 68, 68, 0.2); color: #fca5a5; }

    .dtr-metric-pill.is-warning { background-color: #ffedd5; color: #9a3412; }
    .dark .dtr-metric-pill.is-warning { background-color: rgba(249, 115, 22, 0.2); color: #fdba74; }

    .dtr-metric-pill.is-purple { background-color: #f3e8ff; color: #6b21a8; }
    .dark .dtr-metric-pill.is-purple { background-color: rgba(168, 85, 247, 0.2); color: #d8b4fe; }

    .dtr-metric-pill.is-success { background-color: #dcfce7; color: #166534; }
    .dark .dtr-metric-pill.is-success { background-color: rgba(34, 197, 94, 0.2); color: #86efac; }

    .dtr-metric-pill.is-info { background-color: #e0f2fe; color: #075985; }
    .dark .dtr-metric-pill.is-info { background-color: rgba(56, 189, 248, 0.2); color: #7dd3fc; }

    .dtr-metric-pill.is-gray { background-color: #f1f5f9; color: #475569; }
    .dark .dtr-metric-pill.is-gray { background-color: rgba(148, 163, 184, 0.15); color: #cbd5e1; }

    @media (max-width: 640px) {
        .dtr-modal-grid-4, .dtr-modal-grid-3, .dtr-modal-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="dtr-details-modal-wrapper">
    <!-- Banner Overview -->
    <div class="dtr-modal-banner">
        <div class="dtr-modal-banner-info">
            <span class="dtr-modal-banner-title">
                {{ filled($record->date_in) ? \Carbon\Carbon::parse($record->date_in)->format('F d, Y (l)') : 'D.T.R Entry' }}
            </span>
            <span class="dtr-modal-banner-sub">
                Payroll Period: {{ $record->payrollPeriod?->title ?: '-' }}
            </span>
        </div>
        <div>
            <span class="dtr-metric-pill {{ $statusClass }}">{{ $status }}</span>
        </div>
    </div>

    <!-- Section 1: Timings & Schedule -->
    <div class="dtr-modal-section">
        <div class="dtr-modal-section-header">
            <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4" />
            Attendance & Schedule Timings
        </div>
        <div class="dtr-modal-grid dtr-modal-grid-4">
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Time In</span>
                <span class="dtr-modal-item-value mono">
                    {{ filled($record->time_in) ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '-' }}
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Date In</span>
                <span class="dtr-modal-item-value">
                    {{ filled($record->date_in) ? \Carbon\Carbon::parse($record->date_in)->format('M d, Y') : '-' }}
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Time Out</span>
                <span class="dtr-modal-item-value mono">
                    {{ filled($record->time_out) ? \Carbon\Carbon::parse($record->time_out)->format('h:i A') : '-' }}
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Date Out</span>
                <span class="dtr-modal-item-value">
                    {{ filled($record->date_out) ? \Carbon\Carbon::parse($record->date_out)->format('M d, Y') : '-' }}
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Schedule Start</span>
                <span class="dtr-modal-item-value mono">
                    {{ filled($record->schedule_start) ? \Carbon\Carbon::parse($record->schedule_start)->format('h:i A') : '-' }}
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Schedule End</span>
                <span class="dtr-modal-item-value mono">
                    {{ filled($record->schedule_end) ? \Carbon\Carbon::parse($record->schedule_end)->format('h:i A') : '-' }}
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Schedule Type</span>
                <span class="dtr-modal-item-value">
                    {{ filled($record->schedule_type) ? str($record->schedule_type)->replace('_', ' ')->title() : '-' }}
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Day Part & Count</span>
                <span class="dtr-modal-item-value">
                    {{ app(\App\Services\DtrDayPartService::class)->label($resolvedDayPart) }}
                    ({{ number_format($dayUnits, 1) }} work d)
                    @if ($record instanceof \App\Models\Dtr && ($absenceMinutes = $attendanceUnits->recordAbsenceMinutes($record)) > 0)
                        &bull; {{ number_format($absenceMinutes / 60, 1) }} hrs absent
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Section 2: Work Duration & Metrics -->
    <div class="dtr-modal-section">
        <div class="dtr-modal-section-header">
            <x-filament::icon icon="heroicon-o-chart-bar" class="w-4 h-4" />
            Work Duration & Attendance Metrics
        </div>
        <div class="dtr-modal-grid dtr-modal-grid-4">
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Work Hours</span>
                <span class="dtr-modal-item-value mono">
                    @if ((int) $record->work_hrs > 0)
                        {{ intdiv((int) $record->work_hrs, 60) }}h {{ sprintf('%02d', (int) $record->work_hrs % 60) }}m
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Credited Work Hours</span>
                <span class="dtr-modal-item-value">
                    @if ((int) $record->credited_work_hrs > 0)
                        @php
                            $credClass = ((int) $record->late > 0 || (int) $record->undertime > 0) ? 'is-warning' : 'is-info';
                        @endphp
                        <span class="dtr-metric-pill {{ $credClass }}">
                            {{ intdiv((int) $record->credited_work_hrs, 60) }}h {{ sprintf('%02d', (int) $record->credited_work_hrs % 60) }}m
                        </span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Late</span>
                <span class="dtr-modal-item-value">
                    @if ((int) $record->late > 0)
                        <span class="dtr-metric-pill is-danger">{{ (int) $record->late }} min</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Undertime</span>
                <span class="dtr-modal-item-value">
                    @if ((int) $record->undertime > 0)
                        <span class="dtr-metric-pill is-warning">{{ (int) $record->undertime }} min</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Early OT</span>
                <span class="dtr-modal-item-value">
                    @if ((int) $record->early_clock_in > 0)
                        <span class="dtr-metric-pill is-info">{{ (int) $record->early_clock_in }} min</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Credited Early OT</span>
                <span class="dtr-modal-item-value">
                    @if ((int) $record->credited_early_clock_in > 0)
                        <span class="dtr-metric-pill is-success">{{ (int) $record->credited_early_clock_in }} min</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Overtime</span>
                <span class="dtr-modal-item-value">
                    @if ((int) $record->overtime > 0)
                        <span class="dtr-metric-pill is-purple">{{ (int) $record->overtime }} min</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Credited Overtime</span>
                <span class="dtr-modal-item-value">
                    @if ((int) $record->credited_overtime > 0)
                        <span class="dtr-metric-pill is-success">{{ (int) $record->credited_overtime }} min</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Section 3: Holiday & Overtime Status -->
    <div class="dtr-modal-section">
        <div class="dtr-modal-section-header">
            <x-filament::icon icon="heroicon-o-sparkles" class="w-4 h-4" />
            Holiday & Overtime Information
        </div>
        <div class="dtr-modal-grid dtr-modal-grid-3">
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Holiday</span>
                <span class="dtr-modal-item-value">
                    @if (filled($record->holiday_type))
                        <span class="dtr-metric-pill is-warning">{{ $record->holiday_type }}</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">Holiday Rate</span>
                <span class="dtr-modal-item-value">
                    @if (filled($record->holiday_rate))
                        <span class="dtr-metric-pill is-success">{{ number_format((float) $record->holiday_rate, 2) }}%</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-label">OT Status</span>
                <span class="dtr-modal-item-value">
                    @if (filled($record->overtime_status))
                        @php
                            $otStatusClass = match ($record->overtime_status) {
                                'Approved' => 'is-success',
                                'Rejected' => 'is-danger',
                                'Pending' => 'is-warning',
                                default => 'is-gray',
                            };
                        @endphp
                        <span class="dtr-metric-pill {{ $otStatusClass }}">{{ $record->overtime_status }}</span>
                    @else
                        <span style="color: var(--modal-text-muted);">-</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Section 4: Comment -->
    @if (filled($record->comment))
        <div class="dtr-modal-section">
            <div class="dtr-modal-section-header">
                <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4" />
                Comment / Remarks
            </div>
            <div class="dtr-modal-item">
                <span class="dtr-modal-item-value">{{ $record->comment }}</span>
            </div>
        </div>
    @endif
</div>
