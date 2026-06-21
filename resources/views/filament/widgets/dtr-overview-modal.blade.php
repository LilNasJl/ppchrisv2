@php
    $overview = $overview ?? [];
    $minutesToHours = fn ($minutes) => number_format(((int) $minutes) / 60, 2);
    $metricStyle = 'border:1px solid rgba(148,163,184,.25);border-radius:8px;padding:14px;background:rgba(15,23,42,.03);';
    $labelStyle = 'font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;';
    $valueStyle = 'font-size:24px;font-weight:800;margin-top:4px;';
    $subStyle = 'font-size:12px;color:#64748b;margin-top:2px;';
@endphp

<div style="display:grid;gap:16px;">
    <div style="display:grid;gap:4px;">
        <div style="font-weight:800;font-size:16px;">{{ $overview['employee'] ?? 'No employee selected' }}</div>
        <div style="color:#64748b;">Payroll Period: {{ $overview['period'] ?? 'No payroll period selected' }}</div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;">
        <div style="{{ $metricStyle }}">
            <div style="{{ $labelStyle }}">Total Days of Work</div>
            <div style="{{ $valueStyle }}">{{ number_format((int) ($overview['total_days_work'] ?? 0)) }}</div>
            <div style="{{ $subStyle }}">working dates</div>
        </div>

        <div style="{{ $metricStyle }}">
            <div style="{{ $labelStyle }}">Late</div>
            <div style="{{ $valueStyle }}">{{ number_format((int) ($overview['late'] ?? 0)) }}</div>
            <div style="{{ $subStyle }}">{{ $minutesToHours($overview['late'] ?? 0) }} hour/s</div>
        </div>

        <div style="{{ $metricStyle }}">
            <div style="{{ $labelStyle }}">Undertime</div>
            <div style="{{ $valueStyle }}">{{ number_format((int) ($overview['undertime'] ?? 0)) }}</div>
            <div style="{{ $subStyle }}">{{ $minutesToHours($overview['undertime'] ?? 0) }} hour/s</div>
        </div>

        <div style="{{ $metricStyle }}">
            <div style="{{ $labelStyle }}">Credited Overtime</div>
            <div style="{{ $valueStyle }}">{{ number_format((int) ($overview['credited_overtime'] ?? 0)) }}</div>
            <div style="{{ $subStyle }}">{{ $minutesToHours($overview['credited_overtime'] ?? 0) }} hour/s</div>
        </div>

        <div style="{{ $metricStyle }}">
            <div style="{{ $labelStyle }}">Credited Work Hours</div>
            <div style="{{ $valueStyle }}">{{ number_format((int) ($overview['credited_work_hrs'] ?? 0)) }}</div>
            <div style="{{ $subStyle }}">{{ $minutesToHours($overview['credited_work_hrs'] ?? 0) }} hour/s</div>
        </div>
    </div>
</div>
