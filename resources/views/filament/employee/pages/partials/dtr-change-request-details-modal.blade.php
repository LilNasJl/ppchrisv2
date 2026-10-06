@props(['record'])

@php
    $workflow = $record->workflow_snapshot ?? [];
    $history = $record->approvals_history ?? [];
    $orig = $record->original_values ?? [];
    $prop = $record->proposed_values ?? [];

    $statusClass = match ($record->status) {
        \App\Models\DtrChangeRequest::STATUS_APPROVED => 'status-approved',
        \App\Models\DtrChangeRequest::STATUS_REJECTED => 'status-rejected',
        \App\Models\DtrChangeRequest::STATUS_CANCELLED => 'status-cancelled',
        default => 'status-pending',
    };
@endphp

<style>
    .crd-wrapper {
        --crd-border: rgba(226, 232, 240, 0.9);
        --crd-bg-subtle: #f8fafc;
        --crd-bg-box: #ffffff;
        --crd-text-main: #0f172a;
        --crd-text-muted: #64748b;
        --crd-blue: #2563eb;
        display: flex;
        flex-direction: column;
        gap: 12px;
        color: var(--crd-text-main);
        font-size: 12px;
    }

    .dark .crd-wrapper {
        --crd-border: rgba(51, 65, 85, 0.8);
        --crd-bg-subtle: #1e293b;
        --crd-bg-box: #0f172a;
        --crd-text-main: #f8fafc;
        --crd-text-muted: #94a3b8;
        --crd-blue: #60a5fa;
    }

    /* Banner */
    .crd-banner {
        border-radius: 8px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        border: 1px solid var(--crd-border);
    }
    .crd-banner.status-approved { background-color: #f0fdf4; border-color: #86efac; color: #166534; }
    .dark .crd-banner.status-approved { background-color: rgba(34, 197, 94, 0.15); border-color: rgba(34, 197, 94, 0.3); color: #86efac; }

    .crd-banner.status-pending { background-color: #fffbeb; border-color: #fde68a; color: #92400e; }
    .dark .crd-banner.status-pending { background-color: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.3); color: #fcd34d; }

    .crd-banner.status-rejected { background-color: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .dark .crd-banner.status-rejected { background-color: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; }

    .crd-banner.status-cancelled { background-color: #f1f5f9; border-color: #cbd5e1; color: #475569; }
    .dark .crd-banner.status-cancelled { background-color: rgba(148, 163, 184, 0.15); border-color: rgba(148, 163, 184, 0.3); color: #cbd5e1; }

    .crd-banner-title {
        font-size: 13.5px;
        font-weight: 800;
    }
    .crd-banner-sub {
        font-size: 10.5px;
        opacity: 0.85;
    }

    /* Section Cards */
    .crd-section {
        border: 1px solid var(--crd-border);
        border-radius: 8px;
        background: var(--crd-bg-box);
        overflow: hidden;
    }

    .crd-section-header {
        background: var(--crd-bg-subtle);
        border-bottom: 1px solid var(--crd-border);
        padding: 7px 12px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--crd-text-muted);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .crd-grid {
        display: grid;
        gap: 1px;
        background: var(--crd-border);
    }
    .crd-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .crd-item {
        background: var(--crd-bg-box);
        padding: 8px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .crd-label {
        font-size: 9.5px;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--crd-text-muted);
        margin-bottom: 2px;
    }

    .crd-val {
        font-size: 12px;
        font-weight: 700;
        color: var(--crd-text-main);
    }

    /* Comparison Table */
    .crd-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        text-align: left;
    }
    .crd-table th {
        background: var(--crd-bg-subtle);
        padding: 6px 10px;
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--crd-text-muted);
        border-bottom: 1px solid var(--crd-border);
    }
    .crd-table td {
        padding: 6px 10px;
        border-bottom: 1px solid var(--crd-border);
        color: var(--crd-text-main);
    }
    .crd-table tr:last-child td {
        border-bottom: none;
    }
    .crd-table .field-col {
        font-weight: 600;
        color: var(--crd-text-muted);
        width: 130px;
        background: var(--crd-bg-subtle);
    }
    .crd-table .diff-highlight {
        color: var(--crd-blue);
        font-weight: 700;
    }

    /* Hierarchy step pill */
    .crd-step-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 6px;
        border: 1px solid var(--crd-border);
        padding: 4px 8px;
        font-size: 11px;
        background: var(--crd-bg-subtle);
        color: var(--crd-text-main);
    }
    .crd-step-pill.is-active {
        border-color: #f59e0b;
        background-color: #fffbeb;
        color: #b45309;
        font-weight: 700;
    }
    .dark .crd-step-pill.is-active {
        background-color: rgba(245, 158, 11, 0.15);
        color: #fcd34d;
    }

    .crd-step-pill.is-passed {
        border-color: #86efac;
        background-color: #f0fdf4;
        color: #166534;
    }
    .dark .crd-step-pill.is-passed {
        background-color: rgba(34, 197, 94, 0.15);
        color: #86efac;
    }

    @media (max-width: 640px) {
        .crd-grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="crd-wrapper">
    <!-- Top Status Banner -->
    <div class="crd-banner {{ $statusClass }}">
        <div>
            <span class="crd-banner-sub uppercase tracking-wider">Approval Workflow Position</span>
            <div class="crd-banner-title">{{ $record->display_status }}</div>
        </div>
        <div style="text-align:right;">
            <span class="crd-banner-sub uppercase tracking-wider">Current Responsible</span>
            <div style="font-weight:700;">{{ $record->current_approver_name }}</div>
            <div class="crd-banner-sub">
                Level {{ $record->current_level }} of {{ $record->total_levels }} ({{ $record->current_step_label ?? 'Review' }})
            </div>
        </div>
    </div>

    <!-- Request Information Section -->
    <div class="crd-section">
        <div class="crd-section-header">
            <span>Request Details</span>
            <span>Submission Context</span>
        </div>
        <div class="crd-grid crd-grid-4">
            <div class="crd-item">
                <span class="crd-label">Request Type</span>
                <span class="crd-val" style="color:var(--crd-blue);">{{ $record->request_type_label }}</span>
            </div>
            <div class="crd-item">
                <span class="crd-label">D.T.R Date</span>
                <span class="crd-val">
                    {{ filled($record->dtr_date ?? $record->date_from) ? \Illuminate\Support\Carbon::parse($record->dtr_date ?? $record->date_from)->format('M d, Y') : '-' }}
                </span>
            </div>
            <div class="crd-item">
                <span class="crd-label">Date Submitted</span>
                <span class="crd-val">{{ $record->created_at?->format('M d, Y h:i A') }}</span>
            </div>
            <div class="crd-item">
                <span class="crd-label">Payroll Period</span>
                <span class="crd-val">{{ $record->payroll_period_title_snapshot }}</span>
            </div>
        </div>
        <div style="padding:10px 12px;border-top:1px solid var(--crd-border);">
            <span class="crd-label">Employee Explanation / Justification</span>
            <div style="margin-top:4px;padding:8px 10px;background:var(--crd-bg-subtle);border-radius:6px;border:1px solid var(--crd-border);white-space:pre-wrap;line-height:1.45;">
                {{ $record->description }}
            </div>
            @if ($record->attachment_url)
                <div style="margin-top:8px;display:flex;align-items:center;gap:8px;">
                    <span class="crd-label" style="margin-bottom:0;">Attached File:</span>
                    <a href="{{ $record->attachment_url }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:var(--crd-blue);text-decoration:none;padding:3px 8px;background:var(--crd-bg-subtle);border:1px solid var(--crd-border);border-radius:4px;">
                        <span>{{ $record->attachment_name }}</span>
                        <span style="font-size:9.5px;opacity:0.8;">(View / Download)</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Values Comparison / Entries Table -->
    @if ($record->request_type === \App\Models\DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES && !empty($prop['entries']))
        <div class="crd-section">
            <div class="crd-section-header">
                <span>Proposed Missing Attendance Entries ({{ count($prop['entries']) }})</span>
                <span>{{ $record->status === \App\Models\DtrChangeRequest::STATUS_APPROVED ? 'Officially Created' : 'Pending Final Approval' }}</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="crd-table">
                    <thead>
                        <tr>
                            <th style="width:35px; text-align:center;">#</th>
                            <th>Date In</th>
                            <th>Time In</th>
                            <th>Date Out</th>
                            <th>Time Out</th>
                            <th>Schedule Type</th>
                            <th>Schedule Window</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prop['entries'] as $idx => $entry)
                            <tr>
                                <td style="text-align:center; font-weight:700;">{{ $idx + 1 }}</td>
                                <td style="font-weight:600; color:var(--crd-blue);">
                                    {{ filled($entry['date_in'] ?? null) ? \Illuminate\Support\Carbon::parse($entry['date_in'])->format('M d, Y') : '-' }}
                                </td>
                                <td>{{ filled($entry['time_in'] ?? null) ? \Illuminate\Support\Carbon::parse($entry['time_in'])->format('h:i A') : '-' }}</td>
                                <td>{{ filled($entry['date_out'] ?? null) ? \Illuminate\Support\Carbon::parse($entry['date_out'])->format('M d, Y') : '-' }}</td>
                                <td>{{ filled($entry['time_out'] ?? null) ? \Illuminate\Support\Carbon::parse($entry['time_out'])->format('h:i A') : '-' }}</td>
                                <td>{{ $entry['schedule_type'] ?? 'Regular' }}</td>
                                <td>
                                    {{ filled($entry['schedule_start'] ?? null) ? \Illuminate\Support\Carbon::parse($entry['schedule_start'])->format('h:i A') : '-' }} –
                                    {{ filled($entry['schedule_end'] ?? null) ? \Illuminate\Support\Carbon::parse($entry['schedule_end'])->format('h:i A') : '-' }}
                                </td>
                                <td style="color:var(--crd-text-muted);">{{ $entry['comment'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($record->request_type === \App\Models\DtrChangeRequest::TYPE_DELETE_TIME_ENTRY && !empty($orig))
        <div class="crd-section">
            <div class="crd-section-header" style="background-color: #fef2f2; color: #991b1b;">
                <span>Attendance Record Targeted for Permanent Deletion</span>
                <span>{{ $record->status === \App\Models\DtrChangeRequest::STATUS_APPROVED ? 'Permanently Deleted' : 'Pending Final Approval' }}</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="crd-table">
                    <thead>
                        <tr>
                            <th class="field-col">Field</th>
                            <th>Targeted Record Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="field-col">Date In</td>
                            <td style="font-weight:600;">{{ $orig['date_in'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="field-col">Time In</td>
                            <td>{{ filled($orig['time_in'] ?? null) ? \Illuminate\Support\Carbon::parse($orig['time_in'])->format('h:i A') : 'Missing' }}</td>
                        </tr>
                        <tr>
                            <td class="field-col">Date Out</td>
                            <td>{{ $orig['date_out'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="field-col">Time Out</td>
                            <td>{{ filled($orig['time_out'] ?? null) ? \Illuminate\Support\Carbon::parse($orig['time_out'])->format('h:i A') : 'Missing' }}</td>
                        </tr>
                        <tr>
                            <td class="field-col">Schedule</td>
                            <td>
                                {{ ($orig['schedule_start'] ?? null) ? \Illuminate\Support\Carbon::parse($orig['schedule_start'])->format('h:i A') : '-' }} –
                                {{ ($orig['schedule_end'] ?? null) ? \Illuminate\Support\Carbon::parse($orig['schedule_end'])->format('h:i A') : '-' }}
                                ({{ $orig['schedule_type'] ?? 'Regular' }})
                            </td>
                        </tr>
                        <tr>
                            <td class="field-col">Work Hours</td>
                            <td>{{ (int)($orig['work_hrs'] ?? 0) }} mins (Credited: {{ (int)($orig['credited_work_hrs'] ?? 0) }} mins)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @elseif (!empty($orig) || !empty($prop))
        <div class="crd-section">
            <div class="crd-section-header">
                <span>{{ !empty($prop) ? 'D.T.R Comparison (Original vs Proposed Correction)' : 'D.T.R Values at Submission' }}</span>
                @if ($record->first_level_edited_at)
                    <span style="font-weight:normal;text-transform:none;">
                        Edited by Level 1 on {{ $record->first_level_edited_at->format('M d, Y h:i A') }}
                    </span>
                @endif
            </div>
            <div style="overflow-x:auto;">
                <table class="crd-table">
                    <thead>
                        <tr>
                            <th class="field-col">Field</th>
                            <th>Original Values</th>
                            @if (!empty($prop))
                                <th style="color:var(--crd-blue);">Proposed Corrections</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="field-col">Date In</td>
                            <td>{{ $orig['date_in'] ?? '-' }}</td>
                            @if (!empty($prop))
                                <td class="{{ ($orig['date_in'] ?? null) !== ($prop['date_in'] ?? null) ? 'diff-highlight' : '' }}">
                                    {{ $prop['date_in'] ?? '-' }}
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td class="field-col">Time In</td>
                            <td>{{ filled($orig['time_in'] ?? null) ? \Illuminate\Support\Carbon::parse($orig['time_in'])->format('h:i A') : 'Missing' }}</td>
                            @if (!empty($prop))
                                <td class="{{ ($orig['time_in'] ?? null) !== ($prop['time_in'] ?? null) ? 'diff-highlight' : '' }}">
                                    {{ filled($prop['time_in'] ?? null) ? \Illuminate\Support\Carbon::parse($prop['time_in'])->format('h:i A') : 'Missing' }}
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td class="field-col">Date Out</td>
                            <td>{{ $orig['date_out'] ?? '-' }}</td>
                            @if (!empty($prop))
                                <td class="{{ ($orig['date_out'] ?? null) !== ($prop['date_out'] ?? null) ? 'diff-highlight' : '' }}">
                                    {{ $prop['date_out'] ?? '-' }}
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td class="field-col">Time Out</td>
                            <td>{{ filled($orig['time_out'] ?? null) ? \Illuminate\Support\Carbon::parse($orig['time_out'])->format('h:i A') : 'Missing' }}</td>
                            @if (!empty($prop))
                                <td class="{{ ($orig['time_out'] ?? null) !== ($prop['time_out'] ?? null) ? 'diff-highlight' : '' }}">
                                    {{ filled($prop['time_out'] ?? null) ? \Illuminate\Support\Carbon::parse($prop['time_out'])->format('h:i A') : 'Missing' }}
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td class="field-col">Schedule</td>
                            <td>
                                {{ ($orig['schedule_start'] ?? null) ? \Illuminate\Support\Carbon::parse($orig['schedule_start'])->format('h:i A') : '-' }} –
                                {{ ($orig['schedule_end'] ?? null) ? \Illuminate\Support\Carbon::parse($orig['schedule_end'])->format('h:i A') : '-' }}
                                ({{ $orig['schedule_type'] ?? 'Regular' }})
                            </td>
                            @if (!empty($prop))
                                <td class="{{ ($orig['schedule_type'] ?? null) !== ($prop['schedule_type'] ?? null) ? 'diff-highlight' : '' }}">
                                    {{ ($prop['schedule_start'] ?? null) ? \Illuminate\Support\Carbon::parse($prop['schedule_start'])->format('h:i A') : '-' }} –
                                    {{ ($prop['schedule_end'] ?? null) ? \Illuminate\Support\Carbon::parse($prop['schedule_end'])->format('h:i A') : '-' }}
                                    ({{ $prop['schedule_type'] ?? 'Regular' }})
                                </td>
                            @endif
                        </tr>
                        @if ($record->is_overtime_type)
                            <tr>
                                <td class="field-col">Overtime</td>
                                <td colspan="{{ !empty($prop) ? '2' : '1' }}">
                                    <div>Early OT: {{ (int)($orig['early_clock_in'] ?? 0) }} mins | Regular OT: {{ (int)($orig['overtime'] ?? 0) }} mins | Status: {{ $orig['overtime_status'] ?? 'None' }}</div>
                                    <div style="margin-top:5px;font-size:11.5px;color:var(--crd-text-muted);">
                                        <strong>Requested Scope:</strong> {{ $record->overtime_request_scope_label }}
                                        @if (isset($record->proposed_values['approved_overtime_scope']))
                                            <span style="margin: 0 6px;">&bull;</span>
                                            <strong>Approved Scope:</strong>
                                            {{ match($record->proposed_values['approved_overtime_scope']) {
                                                \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => 'Overtime Only (' . (int)($record->proposed_values['credited_overtime'] ?? 0) . ' mins)',
                                                \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY => 'Early Clock In Only (' . (int)($record->proposed_values['credited_early_clock_in'] ?? 0) . ' mins)',
                                                default => 'Both (' . (int)($record->proposed_values['credited_early_clock_in'] ?? 0) . 'm Early, ' . (int)($record->proposed_values['credited_overtime'] ?? 0) . 'm OT)',
                                            } }}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Workflow Progress & History -->
    <div class="crd-section">
        <div class="crd-section-header">
            <span>Approval Sequence</span>
            <span>Multi-Level Routing</span>
        </div>
        <div style="padding:10px 12px;">
            @if (!empty($workflow))
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-bottom:10px;">
                    @foreach ($workflow as $idx => $step)
                        @php
                            $stepLevel = (int) ($step['level'] ?? ($idx + 1));
                            $isCurrent = ($record->status === \App\Models\DtrChangeRequest::STATUS_PENDING && (int)$record->current_level === $stepLevel);
                            $isPassed = ((int)$record->current_level > $stepLevel || $record->status === \App\Models\DtrChangeRequest::STATUS_APPROVED);
                        @endphp
                        <div class="crd-step-pill {{ $isCurrent ? 'is-active' : ($isPassed ? 'is-passed' : '') }}">
                            <span>Level {{ $stepLevel }}: {{ $step['label'] ?? 'Approver' }} ({{ $step['approver_name'] ?? 'Assigned' }})</span>
                            @if ($isPassed)
                                <span style="font-weight:bold;">✓</span>
                            @elseif ($isCurrent)
                                <span style="font-size:10px;font-weight:800;">● Active</span>
                            @endif
                        </div>
                        @if ($idx < count($workflow) - 1)
                            <span style="color:var(--crd-text-muted);">→</span>
                        @endif
                    @endforeach
                </div>
            @endif

            @if (!empty($history))
                <div style="border-top:1px solid var(--crd-border);padding-top:8px;">
                    <span class="crd-label">Decisions & Action History</span>
                    <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px;">
                        @foreach ($history as $h)
                            <div style="border:1px solid var(--crd-border);background:var(--crd-bg-subtle);border-radius:6px;padding:8px 10px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:4px;">
                                    <div>
                                        <strong>{{ $h['action'] ?? 'Action' }}</strong> by {{ $h['actor_name'] ?? 'Approver' }}
                                        <span style="font-size:10.5px;color:var(--crd-text-muted);">({{ $h['label'] ?? ('Level ' . ($h['level'] ?? '')) }})</span>
                                    </div>
                                    <div style="font-size:10px;color:var(--crd-text-muted);">
                                        {{ filled($h['acted_at'] ?? null) ? \Illuminate\Support\Carbon::parse($h['acted_at'])->format('M d, Y h:i A') : '' }}
                                    </div>
                                </div>
                                @if (filled($h['remarks'] ?? null))
                                    <div style="margin-top:4px;font-style:italic;font-size:11.5px;color:var(--crd-text-main);">
                                        Remarks: "{{ $h['remarks'] }}"
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($record->rejection_reason)
                <div style="margin-top:8px;padding:8px 10px;border-radius:6px;background-color:#fef2f2;border:1px solid #fecaca;color:#991b1b;">
                    <strong>Rejection Reason:</strong> {{ $record->rejection_reason }}
                </div>
            @endif
        </div>
    </div>
</div>
