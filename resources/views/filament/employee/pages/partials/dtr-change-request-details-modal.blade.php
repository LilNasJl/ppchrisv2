@props(['record'])

@php
    $workflow = $record->workflow_snapshot ?? [];
    $history = $record->approvals_history ?? [];
    $orig = $record->original_values ?? [];
    $prop = $record->proposed_values ?? [];
    $targetRecord = $record->targetDtrRecord();
    $employeeName = $record->employee_name_snapshot ?: ($record->employee?->full_name ?: 'Employee');

    $statusClass = match ($record->status) {
        \App\Models\DtrChangeRequest::STATUS_APPROVED => 'status-approved',
        \App\Models\DtrChangeRequest::STATUS_REJECTED => 'status-rejected',
        \App\Models\DtrChangeRequest::STATUS_CANCELLED => 'status-cancelled',
        default => 'status-pending',
    };

    $earlyMins = max(0, (int) ($orig['early_clock_in'] ?? $targetRecord?->early_clock_in ?? 0));
    $otMins = max(0, (int) ($orig['overtime'] ?? $targetRecord?->overtime ?? 0));
    $scope = $record->overtime_request_scope;
    $isOvertimeRequested = in_array($scope, [\App\Models\DtrChangeRequest::OT_SCOPE_BOTH, \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY], true);
    $isEarlyRequested = in_array($scope, [\App\Models\DtrChangeRequest::OT_SCOPE_BOTH, \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY], true);

    $scheduleStart = $orig['schedule_start'] ?? $targetRecord?->schedule_start ?? null;
    $scheduleEnd = $orig['schedule_end'] ?? $targetRecord?->schedule_end ?? null;
    $scheduleType = $orig['schedule_type'] ?? $targetRecord?->schedule_type ?? 'Regular';
    $timeIn = $orig['time_in'] ?? $targetRecord?->time_in ?? null;
    $timeOut = $orig['time_out'] ?? $targetRecord?->time_out ?? null;
    $dateIn = $orig['date_in'] ?? $targetRecord?->date_in ?? ($record->dtr_date ?? null);
    $dateOut = $orig['date_out'] ?? $targetRecord?->date_out ?? $dateIn;
    $workHrs = (int) ($orig['work_hrs'] ?? $targetRecord?->work_hrs ?? 0);
    $otStatus = $orig['overtime_status'] ?? $targetRecord?->overtime_status ?? ($record->status === \App\Models\DtrChangeRequest::STATUS_APPROVED ? 'Approved' : 'Pending');
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

    /* Overtime Specific Styles */
    .crd-ot-highlight-section {
        border-color: #f59e0b !important;
        background: var(--crd-bg-box);
    }
    .dark .crd-ot-highlight-section {
        border-color: rgba(245, 158, 11, 0.5) !important;
    }

    .crd-ot-action-banner {
        border-radius: 6px;
        padding: 10px 12px;
        border: 1px solid rgba(245, 158, 11, 0.4);
        background: rgba(245, 158, 11, 0.08);
        color: #b45309;
        font-size: 12px;
        line-height: 1.45;
    }
    .dark .crd-ot-action-banner {
        background: rgba(245, 158, 11, 0.15);
        border-color: rgba(245, 158, 11, 0.4);
        color: #fcd34d;
    }

    .crd-ot-card {
        border-radius: 8px;
        border: 1px solid var(--crd-border);
        background: var(--crd-bg-subtle);
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        position: relative;
    }

    .crd-ot-card.is-target {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.06);
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.2);
    }
    .dark .crd-ot-card.is-target {
        border-color: #d97706;
        background: rgba(245, 158, 11, 0.12);
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.3);
    }

    .crd-ot-card.is-excluded {
        opacity: 0.7;
        border-style: dashed;
    }

    .crd-ot-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--crd-text-muted);
    }

    .crd-ot-minutes-value {
        font-size: 20px;
        font-weight: 800;
        font-family: monospace;
        line-height: 1.2;
    }

    .crd-ot-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border-radius: 9999px;
        padding: 2px 8px;
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .crd-ot-badge-pill.is-target {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .dark .crd-ot-badge-pill.is-target {
        background: rgba(245, 158, 11, 0.25);
        color: #fde68a;
        border-color: rgba(245, 158, 11, 0.5);
    }

    .crd-ot-badge-pill.is-excluded {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .dark .crd-ot-badge-pill.is-excluded {
        background: rgba(51, 65, 85, 0.4);
        color: #94a3b8;
        border-color: rgba(51, 65, 85, 0.6);
    }

    .crd-ot-punch-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
    }
    .crd-ot-punch-badge.target {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .dark .crd-ot-punch-badge.target {
        background: rgba(245, 158, 11, 0.2);
        color: #fde68a;
        border-color: rgba(245, 158, 11, 0.4);
    }
    .crd-ot-punch-badge.excluded {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }
    .dark .crd-ot-punch-badge.excluded {
        background: rgba(51, 65, 85, 0.3);
        color: #94a3b8;
        border-color: rgba(51, 65, 85, 0.5);
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

    .crd-step-pill.is-rejected {
        border-color: #fecaca;
        background-color: #fef2f2;
        color: #991b1b;
        font-weight: 700;
    }
    .dark .crd-step-pill.is-rejected {
        background-color: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
    }

    @media (max-width: 640px) {
        .crd-grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .crd-ot-grid-2 {
            grid-template-columns: 1fr;
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
                @if ($record->is_overtime_type)
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <span class="crd-val" style="color:#d97706; display:inline-flex; align-items:center; gap:4px; font-weight:800;">
                            <span>⏰</span> Overtime Approval
                        </span>
                        <span style="font-size:9.5px; font-weight:700; color:#b45309; text-transform:uppercase; letter-spacing:0.03em;">
                            {{ $record->overtime_request_scope_label }}
                        </span>
                    </div>
                @else
                    <span class="crd-val" style="color:var(--crd-blue);">{{ $record->request_type_label }}</span>
                @endif
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

        @if ($record->is_overtime_type)
            <!-- High-Visibility Scope Strip for Overtime -->
            <div style="padding:8px 12px; background:rgba(245, 158, 11, 0.08); border-top:1px solid var(--crd-border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                <div style="display:flex; align-items:center; gap:6px;">
                    <span class="crd-label" style="margin-bottom:0;">Approval Target:</span>
                    <span class="crd-ot-badge-pill is-target">{{ $record->overtime_request_scope_label }}</span>
                </div>
                <div style="font-size:11.5px; font-weight:700;">
                    Target Under Review:
                    @if ($record->overtime_request_scope === \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY)
                        <span style="color:#d97706;">{{ $otMins }} mins ({{ sprintf('%.1f', $otMins / 60) }} hrs) Regular Overtime</span>
                    @elseif ($record->overtime_request_scope === \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY)
                        <span style="color:#d97706;">{{ $earlyMins }} mins ({{ sprintf('%.1f', $earlyMins / 60) }} hrs) Early Clock In</span>
                    @else
                        <span style="color:#d97706;">{{ $earlyMins }}m Early + {{ $otMins }}m Overtime (Total: {{ $earlyMins + $otMins }} mins)</span>
                    @endif
                </div>
            </div>
        @endif

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
    @elseif ($record->is_overtime_type)
        {{-- DEDICATED OVERTIME & EARLY CLOCK IN APPROVAL BREAKDOWN --}}
        <div class="crd-section crd-ot-highlight-section">
            <div class="crd-section-header" style="background: linear-gradient(90deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.05)); border-bottom-color: rgba(245, 158, 11, 0.3); color: #d97706;">
                <div style="display:flex; align-items:center; gap:6px; font-weight:800;">
                    <span style="font-size:13px;">⏰</span>
                    <span>OVERTIME & EARLY CLOCK IN APPROVAL BREAKDOWN</span>
                </div>
                <span class="crd-ot-badge-pill is-target">
                    Requested: {{ $record->overtime_request_scope_label }}
                </span>
            </div>

            <div style="padding: 12px; display: flex; flex-direction: column; gap: 12px;">
                <!-- Action summary banner for the approver -->
                <div class="crd-ot-action-banner">
                    <div style="display:flex; align-items:flex-start; gap:8px;">
                        <span style="font-size:16px; line-height:1;">👉</span>
                        <div>
                            <div style="font-weight:800; font-size:12.5px; color:inherit;">
                                @if ($scope === \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY)
                                    Employee is requesting to approve <span style="text-decoration:underline;">AFTER-SHIFT OVERTIME ONLY</span> ({{ $otMins }} mins / {{ sprintf('%.1f', $otMins / 60) }} hrs)
                                @elseif ($scope === \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY)
                                    Employee is requesting to approve <span style="text-decoration:underline;">EARLY CLOCK IN ONLY</span> ({{ $earlyMins }} mins / {{ sprintf('%.1f', $earlyMins / 60) }} hrs)
                                @else
                                    Employee is requesting to approve <span style="text-decoration:underline;">BOTH</span> Early Clock In ({{ $earlyMins }} mins) and After-Shift Overtime ({{ $otMins }} mins)
                                @endif
                            </div>
                            <div style="margin-top:3px; font-size:11px; opacity:0.9;">
                                @if ($scope === \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY)
                                    Early clock in ({{ $earlyMins }} mins) was <strong>not requested</strong> and will remain uncredited.
                                @elseif ($scope === \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY)
                                    Regular overtime ({{ $otMins }} mins) was <strong>not requested</strong> and will remain uncredited.
                                @else
                                    Approver may credit both components or selectively approve specific hours upon review.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two side-by-side cards: Early Clock In vs Regular Overtime -->
                <div class="crd-ot-grid-2">
                    <!-- Early Clock In Card -->
                    <div class="crd-ot-card {{ $isEarlyRequested ? 'is-target' : 'is-excluded' }}">
                        <div class="crd-ot-card-header">
                            <span>EARLY CLOCK IN</span>
                            @if ($isEarlyRequested)
                                <span class="crd-ot-badge-pill is-target">● TARGET TO APPROVE</span>
                            @else
                                <span class="crd-ot-badge-pill is-excluded">✕ NOT REQUESTED</span>
                            @endif
                        </div>
                        <div class="crd-ot-minutes-value" style="color: {{ $isEarlyRequested ? '#d97706' : 'var(--crd-text-muted)' }};">
                            {{ $earlyMins }} <span style="font-size:12px; font-weight:600;">mins</span>
                            <span style="font-size:11px; font-weight:normal; opacity:0.8;">({{ sprintf('%.1f', $earlyMins / 60) }} hrs)</span>
                        </div>
                        <div style="font-size:11px; color:var(--crd-text-muted); line-height:1.4;">
                            Shift start: <strong>{{ filled($scheduleStart) ? \Illuminate\Support\Carbon::parse($scheduleStart)->format('h:i A') : '04:30 AM' }}</strong><br>
                            Actual Clock In: <strong>{{ filled($timeIn) ? \Illuminate\Support\Carbon::parse($timeIn)->format('h:i A') : '-' }}</strong>
                        </div>
                        <div style="margin-top:auto; padding-top:6px; border-top:1px solid var(--crd-border); font-size:10.5px;">
                            @if ($earlyMins >= 30)
                                <span style="color:#16a34a; font-weight:700;">✓ Meets &ge; 30 mins threshold</span>
                            @else
                                <span style="color:var(--crd-text-muted);">Below 30 mins threshold</span>
                            @endif
                        </div>
                    </div>

                    <!-- After-Shift Overtime Card -->
                    <div class="crd-ot-card {{ $isOvertimeRequested ? 'is-target' : 'is-excluded' }}">
                        <div class="crd-ot-card-header">
                            <span>AFTER-SHIFT OVERTIME</span>
                            @if ($isOvertimeRequested)
                                <span class="crd-ot-badge-pill is-target">● TARGET TO APPROVE</span>
                            @else
                                <span class="crd-ot-badge-pill is-excluded">✕ NOT REQUESTED</span>
                            @endif
                        </div>
                        <div class="crd-ot-minutes-value" style="color: {{ $isOvertimeRequested ? '#d97706' : 'var(--crd-text-muted)' }};">
                            {{ $otMins }} <span style="font-size:12px; font-weight:600;">mins</span>
                            <span style="font-size:11px; font-weight:normal; opacity:0.8;">({{ sprintf('%.1f', $otMins / 60) }} hrs)</span>
                        </div>
                        <div style="font-size:11px; color:var(--crd-text-muted); line-height:1.4;">
                            Shift end: <strong>{{ filled($scheduleEnd) ? \Illuminate\Support\Carbon::parse($scheduleEnd)->format('h:i A') : '12:30 PM' }}</strong><br>
                            Actual Clock Out: <strong>{{ filled($timeOut) ? \Illuminate\Support\Carbon::parse($timeOut)->format('h:i A') : '-' }}</strong>
                        </div>
                        <div style="margin-top:auto; padding-top:6px; border-top:1px solid var(--crd-border); font-size:10.5px;">
                            @if ($otMins >= 30)
                                <span style="color:#16a34a; font-weight:700;">✓ Meets &ge; 30 mins threshold</span>
                            @else
                                <span style="color:var(--crd-text-muted);">Below 30 mins threshold</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Decision Outcome if already approved -->
                @if (isset($record->proposed_values['approved_overtime_scope']) || $record->status === \App\Models\DtrChangeRequest::STATUS_APPROVED)
                    <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 6px; padding: 8px 12px; color: #166534;">
                        <div style="font-weight: 750; font-size: 11.5px; color: #15803d; display:flex; align-items:center; gap:5px;">
                            <span>✓</span> Official Credited Overtime Decision:
                        </div>
                        <div style="margin-top:3px; font-size:11.5px;">
                            <strong>Approved Scope:</strong>
                            {{ match($record->proposed_values['approved_overtime_scope'] ?? $scope) {
                                \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => 'Overtime Only (' . (int)($record->proposed_values['credited_overtime'] ?? $otMins) . ' mins credited)',
                                \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY => 'Early Clock In Only (' . (int)($record->proposed_values['credited_early_clock_in'] ?? $earlyMins) . ' mins credited)',
                                default => 'Both (' . (int)($record->proposed_values['credited_early_clock_in'] ?? $earlyMins) . 'm Early, ' . (int)($record->proposed_values['credited_overtime'] ?? $otMins) . 'm OT credited)',
                            } }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Actual Attendance Reference Table -->
        <div class="crd-section">
            <div class="crd-section-header">
                <span>Actual D.T.R Punches & Shift Schedule Reference</span>
                <span>Official Timestamps</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="crd-table">
                    <thead>
                        <tr>
                            <th class="field-col">Field / Event</th>
                            <th>Recorded Schedule & Timestamp</th>
                            <th>Analysis / Excess Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="field-col">Shift Schedule</td>
                            <td style="font-weight:700;">
                                {{ filled($scheduleStart) ? \Illuminate\Support\Carbon::parse($scheduleStart)->format('h:i A') : '-' }} –
                                {{ filled($scheduleEnd) ? \Illuminate\Support\Carbon::parse($scheduleEnd)->format('h:i A') : '-' }}
                                ({{ $scheduleType }})
                            </td>
                            <td style="color:var(--crd-text-muted);">Standard Shift Duration</td>
                        </tr>
                        <tr>
                            <td class="field-col">Clock In</td>
                            <td style="font-weight:700; color: {{ $earlyMins > 0 ? '#d97706' : 'inherit' }};">
                                {{ filled($timeIn) ? \Illuminate\Support\Carbon::parse($timeIn)->format('h:i A') : 'No Punch' }}
                                <span style="font-size:10.5px; font-weight:normal; color:var(--crd-text-muted);">({{ $dateIn }})</span>
                            </td>
                            <td>
                                @if ($earlyMins > 0)
                                    <span style="font-weight:600; color:#d97706;">{{ $earlyMins }} mins Early Clock In</span>
                                    @if ($isEarlyRequested)
                                        <span class="crd-ot-punch-badge target">★ TARGET TO APPROVE</span>
                                    @else
                                        <span class="crd-ot-punch-badge excluded">Not Requested</span>
                                    @endif
                                @else
                                    <span style="color:var(--crd-text-muted);">On schedule / No early time</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="field-col">Clock Out</td>
                            <td style="font-weight:700; color: {{ $otMins > 0 ? '#d97706' : 'inherit' }};">
                                {{ filled($timeOut) ? \Illuminate\Support\Carbon::parse($timeOut)->format('h:i A') : 'No Punch' }}
                                <span style="font-size:10.5px; font-weight:normal; color:var(--crd-text-muted);">({{ $dateOut }})</span>
                            </td>
                            <td>
                                @if ($otMins > 0)
                                    <span style="font-weight:600; color:#d97706;">{{ $otMins }} mins After-Shift Overtime</span>
                                    @if ($isOvertimeRequested)
                                        <span class="crd-ot-punch-badge target">★ TARGET TO APPROVE</span>
                                    @else
                                        <span class="crd-ot-punch-badge excluded">Not Requested</span>
                                    @endif
                                @else
                                    <span style="color:var(--crd-text-muted);">On schedule / No overtime</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="field-col">Work Hours</td>
                            <td colspan="2">
                                {{ $workHrs }} mins total elapsed
                                @if (isset($orig['credited_work_hrs']))
                                    &bull; Credited: {{ (int)$orig['credited_work_hrs'] }} mins
                                @endif
                                &bull; Current OT Status: <span style="font-weight:700;">{{ $otStatus }}</span>
                            </td>
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
                    <div class="crd-step-pill is-passed">
                        <span>{{ $employeeName }}</span>
                        <span style="font-weight:bold;">✓</span>
                    </div>
                    <span style="color:var(--crd-text-muted);">→</span>
                    @foreach ($workflow as $idx => $step)
                        @php
                            $stepLevel = (int) ($step['level'] ?? ($idx + 1));
                            $isHrStep = !empty($step['is_hr']) || ($step['label'] ?? null) === 'HR Final Approval';
                            $isCurrent = ($record->status === \App\Models\DtrChangeRequest::STATUS_PENDING && (int)$record->current_level === $stepLevel);
                            $isRejected = ($record->status === \App\Models\DtrChangeRequest::STATUS_REJECTED && (int)$record->current_level === $stepLevel);
                            $isPassed = (!$isRejected && ((int)$record->current_level > $stepLevel || $record->status === \App\Models\DtrChangeRequest::STATUS_APPROVED));
                            $pillClass = $isRejected ? 'is-rejected' : ($isCurrent ? 'is-active' : ($isPassed ? 'is-passed' : ''));
                        @endphp
                        <div class="crd-step-pill {{ $pillClass }}">
                            @if ($isHrStep)
                                <span>HR Final Approval</span>
                            @else
                                <span>Level {{ $stepLevel }} – {{ $step['label'] ?? 'Approver' }} ({{ $step['approver_name'] ?? 'Assigned' }})</span>
                            @endif
                            @if ($isPassed)
                                <span style="font-weight:bold;">✓ Approved</span>
                            @elseif ($isRejected)
                                <span style="font-size:10px;font-weight:800;">✕ Rejected</span>
                            @elseif ($isCurrent)
                                <span style="font-size:10px;font-weight:800;">● Pending</span>
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
