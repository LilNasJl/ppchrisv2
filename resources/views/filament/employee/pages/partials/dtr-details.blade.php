<style>
    .employee-dtr-details {
        --detail-border: rgba(37, 99, 235, .2);
        --detail-label: #64748b;
        --detail-text: #0f172a;
        --detail-surface: #f8fafc;
        display: grid;
        gap: 14px;
    }

    .dark .employee-dtr-details {
        --detail-border: rgba(96, 165, 250, .26);
        --detail-label: #94a3b8;
        --detail-text: #f8fafc;
        --detail-surface: #111827;
    }

    .employee-dtr-details-grid {
        display: grid;
        gap: 9px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .employee-dtr-detail {
        background: var(--detail-surface);
        border: 1px solid var(--detail-border);
        border-radius: 7px;
        min-width: 0;
        padding: 10px 12px;
    }

    .employee-dtr-detail.is-wide {
        grid-column: 1 / -1;
    }

    .employee-dtr-detail dt {
        color: var(--detail-label);
        font-size: 10px;
        font-weight: 750;
        margin-bottom: 3px;
        text-transform: uppercase;
    }

    .employee-dtr-detail dd {
        color: var(--detail-text);
        font-size: 13px;
        font-weight: 700;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .dtr-metric-pill {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 9999px;
    }
    .dtr-metric-pill.is-danger {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    .dark .dtr-metric-pill.is-danger {
        background-color: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }
    .dtr-metric-pill.is-warning {
        background-color: #ffedd5;
        color: #c2410c;
    }
    .dark .dtr-metric-pill.is-warning {
        background-color: rgba(249, 115, 22, 0.2);
        color: #fdba74;
    }
    .dtr-metric-pill.is-purple {
        background-color: #f3e8ff;
        color: #7e22ce;
    }
    .dark .dtr-metric-pill.is-purple {
        background-color: rgba(168, 85, 247, 0.2);
        color: #d8b4fe;
    }
    .dtr-metric-pill.is-success {
        background-color: #dcfce7;
        color: #15803d;
    }
    .dark .dtr-metric-pill.is-success {
        background-color: rgba(34, 197, 94, 0.2);
        color: #86efac;
    }
    .dtr-metric-pill.is-info {
        background-color: #eff6ff;
        color: #1d4ed8;
    }
    .dark .dtr-metric-pill.is-info {
        background-color: rgba(59, 130, 246, 0.2);
        color: #93c5fd;
    }
    .dtr-metric-pill.is-gray {
        background-color: #f1f5f9;
        color: #475569;
    }
    .dark .dtr-metric-pill.is-gray {
        background-color: rgba(148, 163, 184, 0.15);
        color: #cbd5e1;
    }

    @media (max-width: 640px) {
        .employee-dtr-details-grid {
            grid-template-columns: 1fr;
        }

        .employee-dtr-detail.is-wide {
            grid-column: auto;
        }
    }
</style>

@php
    $attendanceUnits = app(\App\Services\DtrAttendanceUnitService::class);
    $resolvedDayPart = $record instanceof \App\Models\Dtr
        ? $attendanceUnits->dayPartForRecord($record)
        : ($record->day_part ?? null);
@endphp

<div class="employee-dtr-details">
    <dl class="employee-dtr-details-grid">
        <div class="employee-dtr-detail">
            <dt>Status</dt>
            <dd>
                @php
                    $statusClass = match ($status) {
                        'Absent' => 'is-danger',
                        'Leave' => 'is-info',
                        'For Approval', 'Overtime' => 'is-warning',
                        default => 'is-success',
                    };
                @endphp
                <span class="dtr-metric-pill {{ $statusClass }}">{{ $status }}</span>
            </dd>
        </div>
        <div class="employee-dtr-detail"><dt>Payroll Period</dt><dd>{{ $record->payrollPeriod?->title ?: '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Date In</dt><dd>{{ filled($record->date_in) ? \Carbon\Carbon::parse($record->date_in)->format('M d, Y') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Time In</dt><dd style="font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums;">{{ filled($record->time_in) ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Date Out</dt><dd>{{ filled($record->date_out) ? \Carbon\Carbon::parse($record->date_out)->format('M d, Y') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Time Out</dt><dd style="font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums;">{{ filled($record->time_out) ? \Carbon\Carbon::parse($record->time_out)->format('h:i A') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Schedule Start</dt><dd style="font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums;">{{ filled($record->schedule_start) ? \Carbon\Carbon::parse($record->schedule_start)->format('h:i A') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Schedule End</dt><dd style="font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums;">{{ filled($record->schedule_end) ? \Carbon\Carbon::parse($record->schedule_end)->format('h:i A') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Schedule Type</dt><dd>{{ filled($record->schedule_type) ? str($record->schedule_type)->replace('_', ' ')->title() : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Day Part</dt><dd>{{ app(\App\Services\DtrDayPartService::class)->label($resolvedDayPart) }}</dd></div>
        <div class="employee-dtr-detail"><dt>Day Count</dt><dd>{{ number_format($attendanceUnits->recordAttendanceUnits($record), 1) }}</dd></div>
        <div class="employee-dtr-detail">
            <dt>Late</dt>
            <dd>
                @if ((int) $record->late > 0)
                    <span class="dtr-metric-pill is-danger">{{ (int) $record->late }} min</span>
                @else
                    <span style="color: var(--detail-label);">-</span>
                @endif
            </dd>
        </div>
        <div class="employee-dtr-detail">
            <dt>Undertime</dt>
            <dd>
                @if ((int) $record->undertime > 0)
                    <span class="dtr-metric-pill is-warning">{{ (int) $record->undertime }} min</span>
                @else
                    <span style="color: var(--detail-label);">-</span>
                @endif
            </dd>
        </div>
        <div class="employee-dtr-detail">
            <dt>Overtime</dt>
            <dd>
                @if ((int) $record->overtime > 0)
                    <span class="dtr-metric-pill is-purple">{{ (int) $record->overtime }} min</span>
                @else
                    <span style="color: var(--detail-label);">-</span>
                @endif
            </dd>
        </div>
        <div class="employee-dtr-detail">
            <dt>Credited Overtime</dt>
            <dd>
                @if ((int) $record->credited_overtime > 0)
                    <span class="dtr-metric-pill is-success">{{ (int) $record->credited_overtime }} min</span>
                @else
                    <span style="color: var(--detail-label);">-</span>
                @endif
            </dd>
        </div>
        <div class="employee-dtr-detail">
            <dt>Work Hours</dt>
            <dd style="font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums;">
                @if ((int) $record->work_hrs > 0)
                    {{ intdiv((int) $record->work_hrs, 60) }}h {{ (int) $record->work_hrs % 60 }}m
                @else
                    <span style="color: var(--detail-label);">-</span>
                @endif
            </dd>
        </div>
        <div class="employee-dtr-detail">
            <dt>Credited Work Hours</dt>
            <dd>
                @if ((int) $record->credited_work_hrs > 0)
                    @php
                        $credClass = ((int) $record->late > 0 || (int) $record->undertime > 0) ? 'is-warning' : 'is-info';
                    @endphp
                    <span class="dtr-metric-pill {{ $credClass }}">
                        {{ intdiv((int) $record->credited_work_hrs, 60) }}h {{ (int) $record->credited_work_hrs % 60 }}m
                    </span>
                @else
                    <span style="color: var(--detail-label);">-</span>
                @endif
            </dd>
        </div>
        <div class="employee-dtr-detail"><dt>Holiday</dt><dd>{{ $record->holiday_type ?: '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Holiday Rate</dt><dd>{{ filled($record->holiday_rate) ? number_format((float) $record->holiday_rate, 2).'%' : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>OT Status</dt><dd>{{ $record->overtime_status ?: '-' }}</dd></div>
        <div class="employee-dtr-detail is-wide"><dt>Comment</dt><dd>{{ $record->comment ?: '-' }}</dd></div>
    </dl>
</div>
