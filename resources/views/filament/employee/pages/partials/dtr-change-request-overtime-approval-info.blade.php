@props(['record', 'scope', 'earlyCalculated', 'otCalculated'])

<style>
    .ot-info-box {
        --ot-border: rgba(226, 232, 240, 0.9);
        --ot-bg: #f8fafc;
        --ot-card-bg: #ffffff;
        --ot-card-border: #e2e8f0;
        --ot-text-main: #0f172a;
        --ot-text-muted: #64748b;
        background: var(--ot-bg);
        border: 1px solid var(--ot-border);
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 8px;
        font-size: 12px;
        color: var(--ot-text-main);
    }
    .dark .ot-info-box {
        --ot-border: rgba(51, 65, 85, 0.8);
        --ot-bg: #1e293b;
        --ot-card-bg: #0f172a;
        --ot-card-border: #334155;
        --ot-text-main: #f8fafc;
        --ot-text-muted: #94a3b8;
    }
    .ot-stat-card {
        background: var(--ot-card-bg);
        border: 1px solid var(--ot-card-border);
        border-radius: 6px;
        padding: 10px;
        text-align: center;
    }
</style>

<div class="ot-info-box">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:6px;">
        <span style="font-weight:600; color:var(--ot-text-main);">
            D.T.R Date: {{ filled($record->dtr_date) ? \Illuminate\Support\Carbon::parse($record->dtr_date)->format('M d, Y (l)') : '-' }}
        </span>
        <span style="font-weight:600; padding:3px 10px; border-radius:9999px; font-size:11px; {{ match($scope) {
            'overtime_only' => 'background:#f3e8ff; color:#6b21a8;',
            'early_only' => 'background:#e0f2fe; color:#0369a1;',
            default => 'background:#dcfce7; color:#15803d;',
        } }}">
            Requested: {{ $record->overtime_request_scope_label }}
        </span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:10px; margin-bottom:10px;">
        <div class="ot-stat-card">
            <div style="color:var(--ot-text-muted); font-size:11px; margin-bottom:2px;">Calculated Early Clock In</div>
            <div style="font-size:17px; font-weight:700; font-family:monospace; color:var(--ot-text-main);">
                {{ $earlyCalculated }} min
            </div>
            @if ($earlyCalculated >= 30)
                <span style="display:inline-block; font-size:10.5px; color:#16a34a; font-weight:600;">&ge; 30 min threshold</span>
            @else
                <span style="display:inline-block; font-size:10.5px; color:var(--ot-text-muted);">Below 30m threshold</span>
            @endif
        </div>

        <div class="ot-stat-card">
            <div style="color:var(--ot-text-muted); font-size:11px; margin-bottom:2px;">Calculated After-Shift Overtime</div>
            <div style="font-size:17px; font-weight:700; font-family:monospace; color:var(--ot-text-main);">
                {{ $otCalculated }} min
            </div>
            @if ($otCalculated >= 30)
                <span style="display:inline-block; font-size:10.5px; color:#16a34a; font-weight:600;">&ge; 30 min threshold</span>
            @else
                <span style="display:inline-block; font-size:10.5px; color:var(--ot-text-muted);">Below 30m threshold</span>
            @endif
        </div>
    </div>

    @if ($scope === \App\Models\DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY)
        <div style="color:#a855f7; font-size:11.5px; line-height:1.45;">
            &bull; Employee requested <strong>Overtime Only</strong>. As approver, you can only approve the regular overtime hours. Early clock in will not be credited.
        </div>
    @elseif ($scope === \App\Models\DtrChangeRequest::OT_SCOPE_EARLY_ONLY)
        <div style="color:#38bdf8; font-size:11.5px; line-height:1.45;">
            &bull; Employee requested <strong>Early Clock In Only</strong>. As approver, you can only approve the early clock in hours. Regular overtime will not be credited.
        </div>
    @else
        <div style="color:#22c55e; font-size:11.5px; line-height:1.45;">
            &bull; Employee requested <strong>Both Early Clock In & Overtime</strong>. You can approve both components or choose to approve only one of them below, or reject if unearned.
        </div>
    @endif
</div>
