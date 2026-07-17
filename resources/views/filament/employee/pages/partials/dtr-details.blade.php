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

    @media (max-width: 640px) {
        .employee-dtr-details-grid {
            grid-template-columns: 1fr;
        }

        .employee-dtr-detail.is-wide {
            grid-column: auto;
        }
    }
</style>

<div class="employee-dtr-details">
    <dl class="employee-dtr-details-grid">
        <div class="employee-dtr-detail"><dt>Status</dt><dd>{{ $status }}</dd></div>
        <div class="employee-dtr-detail"><dt>Payroll Period</dt><dd>{{ $record->payrollPeriod?->title ?: '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Date In</dt><dd>{{ filled($record->date_in) ? \Carbon\Carbon::parse($record->date_in)->format('M d, Y') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Time In</dt><dd>{{ filled($record->time_in) ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Date Out</dt><dd>{{ filled($record->date_out) ? \Carbon\Carbon::parse($record->date_out)->format('M d, Y') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Time Out</dt><dd>{{ filled($record->time_out) ? \Carbon\Carbon::parse($record->time_out)->format('h:i A') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Schedule Start</dt><dd>{{ filled($record->schedule_start) ? \Carbon\Carbon::parse($record->schedule_start)->format('h:i A') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Schedule End</dt><dd>{{ filled($record->schedule_end) ? \Carbon\Carbon::parse($record->schedule_end)->format('h:i A') : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Schedule Type</dt><dd>{{ filled($record->schedule_type) ? str($record->schedule_type)->replace('_', ' ')->title() : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Day Part</dt><dd>{{ app(\App\Services\DtrDayPartService::class)->label($record->day_part) }}</dd></div>
        <div class="employee-dtr-detail"><dt>Late</dt><dd>{{ max(0, (int) $record->late) }} min</dd></div>
        <div class="employee-dtr-detail"><dt>Undertime</dt><dd>{{ max(0, (int) $record->undertime) }} min</dd></div>
        <div class="employee-dtr-detail"><dt>Overtime</dt><dd>{{ max(0, (int) $record->overtime) }} min</dd></div>
        <div class="employee-dtr-detail"><dt>Credited Overtime</dt><dd>{{ max(0, (int) $record->credited_overtime) }} min</dd></div>
        <div class="employee-dtr-detail"><dt>Work Hours</dt><dd>{{ intdiv(max(0, (int) $record->work_hrs), 60) }}h {{ max(0, (int) $record->work_hrs) % 60 }}m</dd></div>
        <div class="employee-dtr-detail"><dt>Credited Work Hours</dt><dd>{{ intdiv(max(0, (int) $record->credited_work_hrs), 60) }}h {{ max(0, (int) $record->credited_work_hrs) % 60 }}m</dd></div>
        <div class="employee-dtr-detail"><dt>Holiday</dt><dd>{{ $record->holiday_type ?: '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>Holiday Rate</dt><dd>{{ filled($record->holiday_rate) ? number_format((float) $record->holiday_rate, 2).'%' : '-' }}</dd></div>
        <div class="employee-dtr-detail"><dt>OT Status</dt><dd>{{ $record->overtime_status ?: '-' }}</dd></div>
        <div class="employee-dtr-detail is-wide"><dt>Comment</dt><dd>{{ $record->comment ?: '-' }}</dd></div>
    </dl>
</div>
