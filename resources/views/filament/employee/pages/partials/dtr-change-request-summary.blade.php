@props(['record', 'requestType' => null, 'overtimeScope' => null])

@php
    $dateFormatted = filled($record->date_in) ? \Illuminate\Support\Carbon::parse($record->date_in)->format('M d, Y (l)') : '-';
    $timeIn = filled($record->time_in) ? \Illuminate\Support\Carbon::parse($record->time_in)->format('h:i A') : null;
    $timeOut = filled($record->time_out) ? \Illuminate\Support\Carbon::parse($record->time_out)->format('h:i A') : null;
    
    $schedStart = filled($record->schedule_start) ? \Illuminate\Support\Carbon::parse($record->schedule_start)->format('h:i A') : null;
    $schedEnd = filled($record->schedule_end) ? \Illuminate\Support\Carbon::parse($record->schedule_end)->format('h:i A') : null;
    $scheduleText = ($schedStart && $schedEnd) ? "{$schedStart} – {$schedEnd}" : 'No Schedule';
    
    $earlyOt = max(0, (int) ($record->early_clock_in ?? 0));
    $regularOt = max(0, (int) ($record->overtime ?? 0));
    $otEligible = ($earlyOt >= 30 || $regularOt >= 30);
    $otStatus = $record->overtime_status ?: 'None';

    $late = (int) ($record->late ?? 0);
    $undertime = (int) ($record->undertime ?? 0);

    $schedTypeLower = strtolower((string) ($record->schedule_type ?? ''));
    $isForgotSched = str_contains($schedTypeLower, 'forgot');
    $hasMissingPunch = blank($record->time_in) || blank($record->time_out);
    $isForgotToPunch = $isForgotSched || $hasMissingPunch;
@endphp

<style>
    .cr-summary-wrapper {
        --cr-border: rgba(226, 232, 240, 0.9);
        --cr-bg-subtle: #f8fafc;
        --cr-bg-box: #ffffff;
        --cr-text-main: #0f172a;
        --cr-text-muted: #64748b;
        --cr-blue: #2563eb;
        display: flex;
        flex-direction: column;
        gap: 10px;
        color: var(--cr-text-main);
        font-size: 12px;
        margin-bottom: 2px;
    }

    .dark .cr-summary-wrapper {
        --cr-border: rgba(51, 65, 85, 0.8);
        --cr-bg-subtle: #1e293b;
        --cr-bg-box: #0f172a;
        --cr-text-main: #f8fafc;
        --cr-text-muted: #94a3b8;
        --cr-blue: #60a5fa;
    }

    /* Banner */
    .cr-banner {
        background: linear-gradient(135deg, var(--cr-bg-subtle), var(--cr-bg-box));
        border: 1px solid var(--cr-border);
        border-left: 4px solid var(--cr-blue);
        border-radius: 8px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .cr-banner-left {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .cr-banner-title {
        font-size: 13.5px;
        font-weight: 800;
        color: var(--cr-text-main);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .cr-banner-sub {
        font-size: 11.5px;
        color: var(--cr-text-muted);
    }

    /* Cards container */
    .cr-section {
        border: 1px solid var(--cr-border);
        border-radius: 8px;
        overflow: hidden;
        background: var(--cr-bg-box);
    }

    .cr-section-header {
        background: var(--cr-bg-subtle);
        border-bottom: 1px solid var(--cr-border);
        padding: 6px 12px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--cr-text-muted);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .cr-grid {
        display: grid;
        gap: 1px;
        background: var(--cr-border);
    }

    .cr-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .cr-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

    .cr-item {
        background: var(--cr-bg-box);
        padding: 8px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .cr-label {
        font-size: 9.5px;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--cr-text-muted);
        margin-bottom: 3px;
    }

    .cr-val {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--cr-text-main);
    }

    .cr-val.mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-variant-numeric: tabular-nums;
    }

    .cr-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 7px;
        font-size: 10.5px;
        font-weight: 700;
        border-radius: 9999px;
        width: fit-content;
    }

    .cr-pill.danger { background-color: #fee2e2; color: #991b1b; }
    .dark .cr-pill.danger { background-color: rgba(239, 68, 68, 0.2); color: #fca5a5; }

    .cr-pill.warning { background-color: #ffedd5; color: #9a3412; }
    .dark .cr-pill.warning { background-color: rgba(249, 115, 22, 0.2); color: #fdba74; }

    .cr-pill.success { background-color: #dcfce7; color: #166534; }
    .dark .cr-pill.success { background-color: rgba(34, 197, 94, 0.2); color: #86efac; }

    .cr-pill.info { background-color: #e0f2fe; color: #075985; }
    .dark .cr-pill.info { background-color: rgba(56, 189, 248, 0.2); color: #7dd3fc; }

    .cr-pill.purple { background-color: #f3e8ff; color: #6b21a8; }
    .dark .cr-pill.purple { background-color: rgba(168, 85, 247, 0.2); color: #d8b4fe; }

    .cr-pill.gray { background-color: #f1f5f9; color: #475569; }
    .dark .cr-pill.gray { background-color: rgba(148, 163, 184, 0.15); color: #cbd5e1; }

    /* Notice Boxes */
    .cr-notice {
        border-radius: 8px;
        padding: 9px 12px;
        display: flex;
        align-items: flex-start;
        gap: 9px;
        font-size: 11.5px;
        line-height: 1.45;
    }

    .cr-notice svg {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .cr-notice.notice-success {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }
    .dark .cr-notice.notice-success {
        background-color: rgba(34, 197, 94, 0.12);
        border-color: rgba(34, 197, 94, 0.3);
        color: #86efac;
    }

    .cr-notice.notice-warning {
        background-color: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }
    .dark .cr-notice.notice-warning {
        background-color: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.3);
        color: #fcd34d;
    }

    .cr-notice.notice-danger {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }
    .dark .cr-notice.notice-danger {
        background-color: rgba(239, 68, 68, 0.12);
        border-color: rgba(239, 68, 68, 0.3);
        color: #fca5a5;
    }

    .cr-notice.notice-info {
        background-color: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }
    .dark .cr-notice.notice-info {
        background-color: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.3);
        color: #93c5fd;
    }

    .cr-notice.notice-purple {
        background-color: #faf5ff;
        border: 1px solid #e9d5ff;
        color: #6b21a8;
    }
    .dark .cr-notice.notice-purple {
        background-color: rgba(168, 85, 247, 0.12);
        border-color: rgba(168, 85, 247, 0.3);
        color: #d8b4fe;
    }

    @media (max-width: 640px) {
        .cr-grid-4, .cr-grid-3 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="cr-summary-wrapper">
    <!-- Top Banner -->
    <div class="cr-banner">
        <div class="cr-banner-left">
            <div class="cr-banner-title">
                <svg style="width:16px;height:16px;color:var(--cr-blue);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span>{{ $dateFormatted }}</span>
            </div>
            <span class="cr-banner-sub">
                Period: {{ $record->payrollPeriod?->title ?: 'Current Payroll Period' }}
            </span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span class="cr-pill info">
                {{ $scheduleText }}
            </span>
            <span class="cr-pill gray">
                {{ str($record->schedule_type ?? 'Regular')->replace('_', ' ')->title() }}
            </span>
        </div>
    </div>

    <!-- Attendance Details Grid -->
    <div class="cr-section">
        <div class="cr-section-header">
            <span>Attendance Timings</span>
            <span>Punch Status</span>
        </div>
        <div class="cr-grid cr-grid-4">
            <div class="cr-item">
                <span class="cr-label">Time In</span>
                @if ($timeIn)
                    <span class="cr-val mono" style="color:#16a34a;">{{ $timeIn }}</span>
                @else
                    <span class="cr-pill danger">Missing In</span>
                @endif
            </div>
            <div class="cr-item">
                <span class="cr-label">Time Out</span>
                @if ($timeOut)
                    <span class="cr-val mono" style="color:#16a34a;">{{ $timeOut }}</span>
                @else
                    <span class="cr-pill danger">Missing Out</span>
                @endif
            </div>
            <div class="cr-item">
                <span class="cr-label">Late</span>
                @if ($late > 0)
                    <span class="cr-pill danger">{{ $late }} mins</span>
                @else
                    <span class="cr-val mono" style="color:var(--cr-text-muted);">-</span>
                @endif
            </div>
            <div class="cr-item">
                <span class="cr-label">Undertime</span>
                @if ($undertime > 0)
                    <span class="cr-pill warning">{{ $undertime }} mins</span>
                @else
                    <span class="cr-val mono" style="color:var(--cr-text-muted);">-</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Overtime & Approval Status Grid -->
    <div class="cr-section">
        <div class="cr-section-header">
            <span>Overtime Details</span>
            <span>Approval Tracking</span>
        </div>
        <div class="cr-grid cr-grid-3">
            <div class="cr-item">
                <span class="cr-label">Early OT</span>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span class="cr-val mono">{{ $earlyOt }} mins</span>
                    @if ($earlyOt >= 30)
                        <span class="cr-pill success" style="font-size:9.5px;">&ge; 30m</span>
                    @endif
                </div>
            </div>
            <div class="cr-item">
                <span class="cr-label">Regular OT</span>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span class="cr-val mono">{{ $regularOt }} mins</span>
                    @if ($regularOt >= 30)
                        <span class="cr-pill success" style="font-size:9.5px;">&ge; 30m</span>
                    @endif
                </div>
            </div>
            <div class="cr-item">
                <span class="cr-label">OT Status</span>
                @php
                    $otClass = match($otStatus) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Pending' => 'warning',
                        default => 'gray',
                    };
                @endphp
                <span class="cr-pill {{ $otClass }}">{{ $otStatus }}</span>
            </div>
        </div>
    </div>

    <!-- Dynamic Contextual Guidance -->
    @if ($requestType === 'overtime_approval')
        @if ($otEligible)
            <div class="cr-notice notice-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <div>
                    <strong>Eligible for Overtime Request:</strong> At least one overtime segment meets the required 30-minute policy threshold (Early OT: {{ $earlyOt }}m, Regular OT: {{ $regularOt }}m).
                    @if ($overtimeScope === \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY)
                        <div style="margin-top:3px;font-size:11px;color:#15803d;">Selected component: <strong>Overtime Only ({{ $regularOt }}m)</strong>. Early clock in will not be submitted for credit.</div>
                    @elseif ($overtimeScope === \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY)
                        <div style="margin-top:3px;font-size:11px;color:#15803d;">Selected component: <strong>Early Clock In Only ({{ $earlyOt }}m)</strong>. After-shift overtime will not be submitted for credit.</div>
                    @else
                        <div style="margin-top:3px;font-size:11px;color:#15803d;">Selected component: <strong>Both Early Clock In & Overtime</strong>.</div>
                    @endif
                </div>
            </div>
        @else
            <div class="cr-notice notice-danger">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <div>
                    <strong>Minimum Overtime Threshold Not Met:</strong> Company policy requires at least 30 minutes of Early OT or Regular OT to request overtime approval. Both current values (Early: {{ $earlyOt }}m, Regular: {{ $regularOt }}m) are below 30 minutes.
                </div>
            </div>
        @endif
    @elseif ($requestType === 'forgot_to_punch')
        @if (! $isForgotToPunch)
            <div class="cr-notice notice-danger">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <div>
                    <strong>Invalid Request Type:</strong> This attendance entry is complete and not marked as Forgot To Punch. If you need to correct timestamps or schedule details, please select <strong>Time Entries Discrepancy</strong> instead.
                </div>
            </div>
        @else
            <div class="cr-notice notice-info">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>
                <div>
                    <strong>Forgot to Punch Request:</strong> Submit this to request correction for missed punches. Your first-level approver (Station In-Charge) will review the log and supply the verified punch times.
                </div>
            </div>
        @endif
    @elseif ($requestType === 'time_entries_discrepancy')
        <div class="cr-notice notice-purple">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
            </svg>
            <div>
                <strong>Time Entries Discrepancy:</strong> Use this request for incorrect biometric timestamps, wrong schedule start/end, or broken shift discrepancies.
            </div>
        </div>
    @elseif ($requestType === 'delete_time_entry')
        <div class="cr-notice notice-danger" style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:10px 12px; border-radius:6px; display:flex; gap:8px; align-items:flex-start;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:20px; height:20px; flex-shrink:0;">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
            <div>
                <strong>Warning – Permanent Record Deletion:</strong> This request will permanently delete the selected DTR entry after final approval. The DTR record cannot be restored through this Change Request once deletion is completed.
            </div>
        </div>
    @endif
</div>
