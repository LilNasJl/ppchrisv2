@props(['record', 'scope', 'earlyCalculated', 'otCalculated'])

@php
    $isOvertimeRequested = in_array($scope, [\App\Models\DtrChangeRequest::OT_SCOPE_BOTH, \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY], true);
    $isEarlyRequested = in_array($scope, [\App\Models\DtrChangeRequest::OT_SCOPE_BOTH, \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY], true);
    $targetRecord = $record->targetDtrRecord();
    $orig = $record->original_values ?? [];
    $scheduleStart = $orig['schedule_start'] ?? $targetRecord?->schedule_start ?? null;
    $scheduleEnd = $orig['schedule_end'] ?? $targetRecord?->schedule_end ?? null;
    $timeIn = $orig['time_in'] ?? $targetRecord?->time_in ?? null;
    $timeOut = $orig['time_out'] ?? $targetRecord?->time_out ?? null;
@endphp

<style>
    .ot-info-box {
        --ot-border: rgba(226, 232, 240, 0.9);
        --ot-bg: #fffbeb;
        --ot-card-bg: #ffffff;
        --ot-card-border: #e2e8f0;
        --ot-text-main: #0f172a;
        --ot-text-muted: #64748b;
        background: var(--ot-bg);
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 8px;
        font-size: 12px;
        color: var(--ot-text-main);
    }
    .dark .ot-info-box {
        --ot-border: rgba(51, 65, 85, 0.8);
        --ot-bg: rgba(245, 158, 11, 0.08);
        --ot-card-bg: #0f172a;
        --ot-card-border: #334155;
        --ot-text-main: #f8fafc;
        --ot-text-muted: #94a3b8;
        border-color: rgba(245, 158, 11, 0.3);
    }
    .ot-stat-card {
        background: var(--ot-card-bg);
        border: 1px solid var(--ot-card-border);
        border-radius: 6px;
        padding: 10px;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 4px;
        transition: all 0.2s ease;
    }
    .ot-stat-card.is-target {
        border-color: #f59e0b;
        background: #fffdf5;
        box-shadow: 0 0 0 1px #f59e0b, 0 1px 3px rgba(245, 158, 11, 0.1);
    }
    .dark .ot-stat-card.is-target {
        border-color: #d97706;
        background: rgba(245, 158, 11, 0.12);
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.4);
    }
    .ot-stat-card.is-excluded {
        opacity: 0.65;
        border-style: dashed;
        background: #f8fafc;
    }
    .dark .ot-stat-card.is-excluded {
        background: rgba(15, 23, 42, 0.5);
    }
    .ot-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        border-radius: 9999px;
        padding: 2px 7px;
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .ot-badge-pill.target {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .dark .ot-badge-pill.target {
        background: rgba(245, 158, 11, 0.25);
        color: #fde68a;
        border-color: rgba(245, 158, 11, 0.5);
    }
    .ot-badge-pill.excluded {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .dark .ot-badge-pill.excluded {
        background: rgba(51, 65, 85, 0.4);
        color: #94a3b8;
        border-color: rgba(51, 65, 85, 0.6);
    }
</style>

<div class="ot-info-box">
    <!-- Header with Date and Requested Target -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px; padding-bottom:8px; border-bottom:1px solid rgba(245, 158, 11, 0.2);">
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:14px;">⏰</span>
            <span style="font-weight:750; font-size:13px; color:var(--ot-text-main);">
                D.T.R Date: {{ filled($record->dtr_date) ? \Illuminate\Support\Carbon::parse($record->dtr_date)->format('M d, Y (l)') : '-' }}
            </span>
        </div>
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:11px; font-weight:600; color:var(--ot-text-muted);">Scope:</span>
            <span class="ot-badge-pill target">
                {{ $record->overtime_request_scope_label }}
            </span>
        </div>
    </div>

    <!-- Cards Breakdown -->
    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:10px; margin-bottom:12px;">
        <!-- Early Clock In Card -->
        <div class="ot-stat-card {{ $isEarlyRequested ? 'is-target' : 'is-excluded' }}">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="color:var(--ot-text-muted); font-size:10.5px; font-weight:700; text-transform:uppercase;">Early Clock In</span>
                @if ($isEarlyRequested)
                    <span class="ot-badge-pill target">● TARGET TO APPROVE</span>
                @else
                    <span class="ot-badge-pill excluded">✕ NOT REQUESTED</span>
                @endif
            </div>

            <div style="font-size:19px; font-weight:800; font-family:monospace; color:{{ $isEarlyRequested ? '#d97706' : 'var(--ot-text-muted)' }}; margin:4px 0 2px 0;">
                {{ $earlyCalculated }} <span style="font-size:11.5px; font-weight:600;">mins</span>
                <span style="font-size:11px; font-weight:normal; opacity:0.8;">({{ sprintf('%.1f', $earlyCalculated / 60) }}h)</span>
            </div>

            @if (filled($timeIn))
                <div style="font-size:10.5px; color:var(--ot-text-muted);">
                    Punch: <strong>{{ \Illuminate\Support\Carbon::parse($timeIn)->format('h:i A') }}</strong> (Shift: {{ filled($scheduleStart) ? \Illuminate\Support\Carbon::parse($scheduleStart)->format('h:i A') : '-' }})
                </div>
            @endif

            <div style="margin-top:auto; padding-top:4px; font-size:10.5px;">
                @if ($earlyCalculated >= 30)
                    <span style="color:#16a34a; font-weight:700;">✓ Meets &ge; 30m threshold</span>
                @else
                    <span style="color:var(--ot-text-muted);">Below 30m threshold</span>
                @endif
            </div>
        </div>

        <!-- Regular After-Shift Overtime Card -->
        <div class="ot-stat-card {{ $isOvertimeRequested ? 'is-target' : 'is-excluded' }}">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="color:var(--ot-text-muted); font-size:10.5px; font-weight:700; text-transform:uppercase;">After-Shift Overtime</span>
                @if ($isOvertimeRequested)
                    <span class="ot-badge-pill target">● TARGET TO APPROVE</span>
                @else
                    <span class="ot-badge-pill excluded">✕ NOT REQUESTED</span>
                @endif
            </div>

            <div style="font-size:19px; font-weight:800; font-family:monospace; color:{{ $isOvertimeRequested ? '#d97706' : 'var(--ot-text-muted)' }}; margin:4px 0 2px 0;">
                {{ $otCalculated }} <span style="font-size:11.5px; font-weight:600;">mins</span>
                <span style="font-size:11px; font-weight:normal; opacity:0.8;">({{ sprintf('%.1f', $otCalculated / 60) }}h)</span>
            </div>

            @if (filled($timeOut))
                <div style="font-size:10.5px; color:var(--ot-text-muted);">
                    Punch: <strong>{{ \Illuminate\Support\Carbon::parse($timeOut)->format('h:i A') }}</strong> (Shift End: {{ filled($scheduleEnd) ? \Illuminate\Support\Carbon::parse($scheduleEnd)->format('h:i A') : '-' }})
                </div>
            @endif

            <div style="margin-top:auto; padding-top:4px; font-size:10.5px;">
                @if ($otCalculated >= 30)
                    <span style="color:#16a34a; font-weight:700;">✓ Meets &ge; 30m threshold</span>
                @else
                    <span style="color:var(--ot-text-muted);">Below 30m threshold</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Explanatory Guideline Box -->
    <div style="padding:8px 10px; border-radius:6px; font-size:11.5px; line-height:1.45; {{ match($scope) {
        \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => 'background:#fef3c7; border:1px solid #fde68a; color:#92400e;',
        \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY => 'background:#e0f2fe; border:1px solid #bae6fd; color:#0369a1;',
        default => 'background:#dcfce7; border:1px solid #bbf7d0; color:#166534;',
    } }}">
        @if ($scope === \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY)
            <strong>Approval Directive:</strong> Employee requested <span style="text-decoration:underline;">AFTER-SHIFT OVERTIME ONLY</span> ({{ $otCalculated }} mins). Early clock in ({{ $earlyCalculated }} mins) is excluded from this request and will not be credited.
        @elseif ($scope === \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY)
            <strong>Approval Directive:</strong> Employee requested <span style="text-decoration:underline;">EARLY CLOCK IN ONLY</span> ({{ $earlyCalculated }} mins). After-shift overtime ({{ $otCalculated }} mins) is excluded from this request and will not be credited.
        @else
            <strong>Approval Directive:</strong> Employee requested <span style="text-decoration:underline;">BOTH</span> Early Clock In ({{ $earlyCalculated }} mins) and Overtime ({{ $otCalculated }} mins). You can approve both components or select specific credited hours below.
        @endif
    </div>
</div>
