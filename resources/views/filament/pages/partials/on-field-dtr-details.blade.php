@php
    use App\Models\DtrSubmission;
    use App\Services\OnFieldDtrService;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $hideHero = (bool) ($hideHero ?? false);
    $compact = (bool) ($compact ?? false);
    $showReviewer = (bool) ($showReviewer ?? false);
    $isHrReviewer = auth()->check() && in_array(auth()->user()->role, ['hr', 'admin'], true);

    $statusColor = match ($submission->status) {
        DtrSubmission::STATUS_APPROVED => 'success',
        DtrSubmission::STATUS_REJECTED => 'danger',
        default => 'warning',
    };

    $entries = $submission->normalizedEntries();
    $service = app(OnFieldDtrService::class);

    $previews = [];
    $totalPunchedMinutes = 0;
    $totalCreditedMinutes = 0;
    $hasAnyConflict = false;

    foreach ($entries as $idx => $entry) {
        $preview = $service->previewEntry($submission, $entry, $idx);
        $previews[$idx] = $preview;
        $totalPunchedMinutes += (int) ($preview['punched_minutes'] ?? 0);
        $totalCreditedMinutes += (int) ($preview['credited_work_hrs'] ?? 0);

        if (! empty($preview['has_conflict'])) {
            $hasAnyConflict = true;
        }
    }

    $formatMinutes = static function (int $minutes): string {
        $minutes = max(0, $minutes);

        if ($minutes === 0) {
            return '0m';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours === 0) {
            return "{$mins}m";
        }

        return sprintf('%dh %02dm', $hours, $mins);
    };

    $periodLocked = (bool) $submission->payrollPeriod?->is_locked;
    $submittedDifferentFromEmployee = $submission->submitted_by_employee_id
        && $submission->employee_id
        && (int) $submission->submitted_by_employee_id !== (int) $submission->employee_id;

    $filePath = (string) ($submission->file_path ?? '');
    $fileExists = $filePath !== '' && Storage::disk('local')->exists($filePath);
    $ext = strtolower(pathinfo((string) ($submission->file_name ?: $filePath), PATHINFO_EXTENSION));
    $mime = strtolower((string) ($submission->mime_type ?? ''));
    $isImage = str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    $isPdf = $mime === 'application/pdf' || $ext === 'pdf';

    $fileSizeLabel = null;
    if (filled($submission->file_size) && (int) $submission->file_size > 0) {
        $bytes = (int) $submission->file_size;
        $fileSizeLabel = $bytes >= 1048576
            ? number_format($bytes / 1048576, 2).' MB'
            : number_format(max(1, (int) round($bytes / 1024))).' KB';
    }

    $firstPreview = $previews[0] ?? [];
    $firstSchedSummary = filled($firstPreview['schedule_type'] ?? null)
        ? $firstPreview['schedule_type'].(
            filled($firstPreview['schedule_start'] ?? null) && filled($firstPreview['schedule_end'] ?? null)
                ? ' ('.Carbon::parse($firstPreview['schedule_start'])->format('h:i A').' – '.Carbon::parse($firstPreview['schedule_end'])->format('h:i A').')'
                : ''
        )
        : 'Auto / Branch Default';
@endphp

@once
    <style>
        .ofd-page {
            --ofd-text: #0f172a;
            --ofd-text-soft: #334155;
            --ofd-muted: #64748b;
            --ofd-border: #dbe4f0;
            --ofd-border-strong: rgba(37, 99, 235, 0.3);
            --ofd-surface: #ffffff;
            --ofd-surface-elevated: #f8fafc;
            --ofd-surface-soft: #f1f5f9;
            --ofd-blue-soft: #eff6ff;
            --ofd-blue: #2563eb;
            --ofd-blue-strong: #1e40af;
            --ofd-amber-bg: #fffbeb;
            --ofd-amber-border: #fcd34d;
            --ofd-amber-text: #92400e;
            --ofd-green-bg: #f0fdf4;
            --ofd-green-border: #86efac;
            --ofd-green-text: #166534;
            --ofd-red-bg: #fef2f2;
            --ofd-red-border: #fecaca;
            --ofd-red-text: #991b1b;
            display: grid;
            width: 100%;
            gap: 1rem;
            color: var(--ofd-text);
        }

        .dark .ofd-page {
            --ofd-text: #f8fafc;
            --ofd-text-soft: #e2e8f0;
            --ofd-muted: #94a3b8;
            --ofd-border: rgba(71, 85, 105, 0.58);
            --ofd-border-strong: rgba(96, 165, 250, 0.38);
            --ofd-surface: #0b1220;
            --ofd-surface-elevated: #0f172a;
            --ofd-surface-soft: #132038;
            --ofd-blue-soft: rgba(37, 99, 235, 0.16);
            --ofd-blue: #60a5fa;
            --ofd-blue-strong: #bfdbfe;
            --ofd-amber-bg: rgba(245, 158, 11, 0.14);
            --ofd-amber-border: rgba(245, 158, 11, 0.4);
            --ofd-amber-text: #fde68a;
            --ofd-green-bg: rgba(34, 197, 94, 0.14);
            --ofd-green-border: rgba(34, 197, 94, 0.4);
            --ofd-green-text: #86efac;
            --ofd-red-bg: rgba(239, 68, 68, 0.14);
            --ofd-red-border: rgba(239, 68, 68, 0.42);
            --ofd-red-text: #fca5a5;
        }

        .ofd-page--compact {
            gap: 0.9rem;
        }

        .ofd-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            padding: 1.15rem 1.35rem;
            border: 1px solid var(--ofd-border-strong);
            border-radius: 0.75rem;
            background: var(--ofd-blue-soft);
        }

        .ofd-identity,
        .ofd-heading {
            display: flex;
            min-width: 0;
            align-items: flex-start;
            gap: 0.85rem;
        }

        .ofd-hero-icon,
        .ofd-heading-icon {
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 0.65rem;
        }

        .ofd-hero-icon {
            width: 44px;
            height: 44px;
            color: #ffffff;
            background: #2563eb;
        }

        .ofd-heading-icon {
            width: 34px;
            height: 34px;
            color: var(--ofd-blue);
            background: var(--ofd-blue-soft);
            border: 1px solid var(--ofd-border-strong);
        }

        .ofd-icon-svg {
            width: 19px;
            height: 19px;
        }

        .ofd-eyebrow,
        .ofd-label {
            margin: 0;
            color: var(--ofd-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .ofd-eyebrow {
            color: var(--ofd-blue-strong);
        }

        .ofd-employee-name {
            margin: 0.2rem 0 0;
            overflow-wrap: anywhere;
            color: var(--ofd-text);
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .ofd-employee-meta {
            margin: 0.25rem 0 0;
            color: var(--ofd-text-soft);
            font-size: 0.84rem;
        }

        .ofd-divider {
            margin: 0 0.45rem;
            opacity: 0.45;
        }

        .ofd-status {
            flex: 0 0 auto;
            text-align: right;
        }

        .ofd-status-badge {
            display: flex;
            justify-content: flex-end;
            margin-top: 0.3rem;
        }

        /* Alerts */
        .ofd-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.8rem 1rem;
            border: 1px solid var(--ofd-border);
            border-radius: 0.75rem;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .ofd-alert--warning {
            background: var(--ofd-amber-bg);
            border-color: var(--ofd-amber-border);
            color: var(--ofd-amber-text);
        }

        .ofd-alert--danger {
            background: var(--ofd-red-bg);
            border-color: var(--ofd-red-border);
            color: var(--ofd-red-text);
        }

        .ofd-alert-icon {
            width: 19px;
            height: 19px;
            flex: 0 0 auto;
            margin-top: 0.1rem;
        }

        /* 4-Tile KPI / Context Summary */
        .ofd-meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .ofd-meta-card {
            min-width: 0;
            padding: 0.85rem 1rem;
            border: 1px solid var(--ofd-border);
            border-radius: 0.75rem;
            background: var(--ofd-surface);
        }

        .ofd-meta-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .ofd-value {
            margin: 0.3rem 0 0;
            overflow-wrap: anywhere;
            color: var(--ofd-text);
            font-size: 0.92rem;
            font-weight: 700;
            line-height: 1.4;
        }

        .ofd-subvalue {
            margin: 0.2rem 0 0;
            overflow-wrap: anywhere;
            color: var(--ofd-muted);
            font-size: 0.76rem;
            line-height: 1.4;
        }

        .ofd-monospace {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.78rem;
        }

        .ofd-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.12rem 0.48rem;
            border-radius: 9999px;
            border: 1px solid var(--ofd-border);
            background: var(--ofd-surface-soft);
            color: var(--ofd-text-soft);
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1.3;
            white-space: nowrap;
        }

        .ofd-pill--success {
            background: var(--ofd-green-bg);
            border-color: var(--ofd-green-border);
            color: var(--ofd-green-text);
        }

        .ofd-pill--danger {
            background: var(--ofd-red-bg);
            border-color: var(--ofd-red-border);
            color: var(--ofd-red-text);
        }

        .ofd-pill--warning {
            background: var(--ofd-amber-bg);
            border-color: var(--ofd-amber-border);
            color: var(--ofd-amber-text);
        }

        .ofd-pill--info {
            background: var(--ofd-blue-soft);
            border-color: var(--ofd-border-strong);
            color: var(--ofd-blue-strong);
        }

        /* Two-Column Workspace */
        .ofd-workspace-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
            gap: 1rem;
            align-items: start;
        }

        .ofd-col {
            display: grid;
            min-width: 0;
            gap: 1rem;
        }

        .ofd-col--sticky {
            position: sticky;
            top: 1rem;
        }

        .ofd-card {
            min-width: 0;
            border: 1px solid var(--ofd-border);
            border-radius: 0.75rem;
            background: var(--ofd-surface);
            overflow: hidden;
        }

        .ofd-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1.05rem;
            border-bottom: 1px solid var(--ofd-border);
            background: var(--ofd-surface-elevated);
        }

        .ofd-card-body {
            padding: 1rem 1.05rem;
        }

        .ofd-section-title {
            margin: 0;
            color: var(--ofd-text);
            font-size: 0.95rem;
            font-weight: 750;
            line-height: 1.3;
        }

        .ofd-section-copy {
            margin: 0.15rem 0 0;
            color: var(--ofd-muted);
            font-size: 0.77rem;
            line-height: 1.45;
        }

        /* Entry Cards */
        .ofd-entry-list {
            display: grid;
            gap: 0.85rem;
            padding: 1rem;
        }

        .ofd-entry-card {
            min-width: 0;
            border: 1px solid var(--ofd-border);
            border-radius: 0.65rem;
            background: var(--ofd-surface-elevated);
            overflow: hidden;
        }

        .ofd-entry-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem 0.75rem;
            padding: 0.7rem 0.95rem;
            border-bottom: 1px solid var(--ofd-border);
            background: var(--ofd-surface-soft);
        }

        .ofd-entry-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0;
            color: var(--ofd-text);
            font-size: 0.86rem;
            font-weight: 750;
        }

        .ofd-entry-title .ofd-icon-svg {
            width: 17px;
            height: 17px;
            color: var(--ofd-blue);
        }

        .ofd-entry-badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
        }

        .ofd-compare-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            padding: 0.9rem 0.95rem;
        }

        .ofd-compare-box {
            min-width: 0;
            padding: 0.7rem 0.85rem;
            border: 1px solid var(--ofd-border);
            border-radius: 0.55rem;
            background: var(--ofd-surface);
        }

        .ofd-punch-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.45rem;
        }

        .ofd-punch-arrow {
            color: var(--ofd-blue);
            font-weight: 800;
            font-size: 0.9rem;
        }

        .ofd-time-big {
            margin: 0;
            color: var(--ofd-text);
            font-size: 0.95rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .ofd-date-small {
            margin: 0.1rem 0 0;
            color: var(--ofd-muted);
            font-size: 0.74rem;
        }

        /* Calculation Impact Strip */
        .ofd-impact-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
            padding: 0.65rem 0.95rem;
            border-top: 1px solid var(--ofd-border);
            background: rgba(15, 23, 42, 0.35);
        }

        .ofd-impact-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.55rem;
            border: 1px solid var(--ofd-border);
            border-radius: 0.45rem;
            background: var(--ofd-surface);
            font-size: 0.74rem;
        }

        .ofd-impact-chip-label {
            color: var(--ofd-muted);
            font-weight: 600;
        }

        .ofd-impact-chip-val {
            color: var(--ofd-text);
            font-weight: 750;
        }

        .ofd-impact-chip--good {
            background: var(--ofd-green-bg);
            border-color: var(--ofd-green-border);
        }

        .ofd-impact-chip--good .ofd-impact-chip-val {
            color: var(--ofd-green-text);
        }

        .ofd-impact-chip--warn {
            background: var(--ofd-amber-bg);
            border-color: var(--ofd-amber-border);
        }

        .ofd-impact-chip--warn .ofd-impact-chip-val {
            color: var(--ofd-amber-text);
        }

        .ofd-impact-chip--info {
            background: var(--ofd-blue-soft);
            border-color: var(--ofd-border-strong);
        }

        .ofd-impact-chip--info .ofd-impact-chip-val {
            color: var(--ofd-blue-strong);
        }

        .ofd-entry-conflict {
            padding: 0.6rem 0.95rem;
            border-top: 1px solid var(--ofd-red-border);
            background: var(--ofd-red-bg);
            color: var(--ofd-red-text);
            font-size: 0.78rem;
            font-weight: 600;
        }

        /* Proof & Notes */
        .ofd-proof-frame {
            margin-top: 0.75rem;
            border: 1px solid var(--ofd-border);
            border-radius: 0.65rem;
            background: #020617;
            overflow: hidden;
        }

        .ofd-proof-image {
            display: block;
            width: 100%;
            max-height: 360px;
            object-fit: contain;
            background: #020617;
        }

        .ofd-proof-pdf {
            display: block;
            width: 100%;
            height: 380px;
            border: 0;
            background: #0f172a;
        }

        .ofd-proof-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.65rem;
        }

        .ofd-file-name,
        .ofd-body-copy {
            margin: 0;
            overflow-wrap: anywhere;
            color: var(--ofd-text-soft);
            font-size: 0.85rem;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .ofd-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.85rem;
        }

        .ofd-review-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem 1.25rem;
        }

        .ofd-review-remarks {
            margin-top: 0.9rem;
            padding-top: 0.85rem;
            border-top: 1px solid var(--ofd-border);
        }

        @media (max-width: 1120px) {
            .ofd-meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ofd-workspace-grid {
                grid-template-columns: 1fr;
            }

            .ofd-col--sticky {
                position: static;
            }
        }

        @media (max-width: 720px) {
            .ofd-hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .ofd-status {
                width: 100%;
                padding-top: 0.75rem;
                border-top: 1px solid var(--ofd-border);
                text-align: left;
            }

            .ofd-status-badge {
                justify-content: flex-start;
            }

            .ofd-compare-grid,
            .ofd-review-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 520px) {
            .ofd-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endonce

<div class="ofd-page {{ $compact ? 'ofd-page--compact' : '' }}">
    @unless ($hideHero)
        <section class="ofd-hero">
            <div class="ofd-identity">
                <div class="ofd-hero-icon">
                    <x-filament::icon icon="heroicon-o-user" class="ofd-icon-svg" />
                </div>
                <div>
                    <p class="ofd-eyebrow">Employee</p>
                    <h2 class="ofd-employee-name">{{ $submission->submittedEmployeeName() }}</h2>
                    <p class="ofd-employee-meta">
                        {{ $submission->employee_company_id_snapshot ?: 'No employee ID' }}
                        <span class="ofd-divider">|</span>
                        {{ $submission->submittedBranchName() }}
                    </p>
                </div>
            </div>

            <div class="ofd-status">
                <p class="ofd-label">Request Status</p>
                <div class="ofd-status-badge">
                    <x-filament::badge :color="$statusColor">{{ $submission->status }}</x-filament::badge>
                </div>
            </div>
        </section>
    @endunless

    @if ($periodLocked && $submission->isApproved())
        <div class="ofd-alert ofd-alert--warning">
            <x-filament::icon icon="heroicon-o-lock-closed" class="ofd-alert-icon" />
            <div>
                <strong>Locked Payroll Period:</strong> This approved On Field D.T.R belongs to
                <em>{{ $submission->payrollPeriod?->title }}</em>, which is already locked. Linked attendance records cannot be modified.
            </div>
        </div>
    @endif

    @if ($submission->isApproved() && $submission->generated_dtr_deleted_at)
        <div class="ofd-alert ofd-alert--warning">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="ofd-alert-icon" />
            <div>
                <strong>Generated D.T.R Removed:</strong> Linked D.T.R records were deleted on
                {{ $submission->generated_dtr_deleted_at->format('M d, Y h:i A') }}
                @if ($submission->generatedDtrDeletedBy)
                    by {{ $submission->generatedDtrDeletedBy->username }}
                @endif
                .
            </div>
        </div>
    @endif

    @if ($hasAnyConflict)
        <div class="ofd-alert ofd-alert--danger">
            <x-filament::icon icon="heroicon-o-exclamation-circle" class="ofd-alert-icon" />
            <div>
                <strong>Schedule Conflict Detected:</strong> One or more entries overlap an existing D.T.R, leave, or absence entry for this employee.{{ $submission->isPending() ? ' Edit the dates or time punches before approving.' : '' }}
            </div>
        </div>
    @endif

    <section class="ofd-meta-grid">
        <div class="ofd-meta-card">
            <div class="ofd-meta-head">
                <p class="ofd-label">Payroll Period</p>
                <span class="ofd-pill {{ $periodLocked ? 'ofd-pill--danger' : 'ofd-pill--success' }}">
                    {{ $periodLocked ? 'Locked' : 'Open' }}
                </span>
            </div>
            <p class="ofd-value">{{ $submission->payrollPeriod?->title ?: 'Not available' }}</p>
            @if ($submission->payrollPeriod?->date_payout)
                <p class="ofd-subvalue">Payout: {{ $submission->payrollPeriod->date_payout->format('M d, Y') }}</p>
            @endif
        </div>

        <div class="ofd-meta-card">
            <p class="ofd-label">Submitted &amp; Viewed</p>
            <p class="ofd-value">{{ $submission->created_at?->format('M d, Y h:i A') ?: 'Not available' }}</p>
            <p class="ofd-subvalue">
                @if ($submission->viewed_at)
                    Viewed {{ $submission->viewed_at->format('M d, Y h:i A') }}
                @else
                    Awaiting first view
                @endif
            </p>
        </div>

        <div class="ofd-meta-card">
            <div class="ofd-meta-head">
                <p class="ofd-label">Submitted Attendance</p>
                <span class="ofd-pill ofd-pill--info">{{ count($entries) }} {{ Str::plural('entry', count($entries)) }}</span>
            </div>
            <p class="ofd-value">{{ $formatMinutes($totalPunchedMinutes) }} punched &bull; {{ $formatMinutes($totalCreditedMinutes) }} credited</p>
            <p class="ofd-subvalue">{{ $firstSchedSummary }}</p>
        </div>

        <div class="ofd-meta-card">
            <div class="ofd-meta-head">
                <p class="ofd-label">Review &amp; Reference</p>
                <span class="ofd-pill ofd-monospace" title="{{ $submission->publicKey() }}">
                    #{{ Str::limit((string) $submission->publicKey(), 8, '') }}
                </span>
            </div>
            <p class="ofd-value">
                @if ($submission->reviewed_at)
                    {{ $submission->status }} on {{ $submission->reviewed_at->format('M d, Y') }}
                @else
                    Awaiting HR Decision
                @endif
            </p>
            <p class="ofd-subvalue">
                @if ($submittedDifferentFromEmployee)
                    Submitted by {{ $submission->submittedByEmployee?->full_name ?: 'Unknown' }}
                @elseif ($submission->reviewedBy)
                    Reviewed by {{ $submission->reviewedBy->username }}
                @else
                    {{ $submission->submittedBranchName() }}
                @endif
            </p>
        </div>
    </section>

    <div class="ofd-workspace-grid">
        <div class="ofd-col">
            <section class="ofd-card">
                <header class="ofd-card-header">
                    <div class="ofd-heading">
                        <div class="ofd-heading-icon">
                            <x-filament::icon icon="heroicon-o-clock" class="ofd-icon-svg" />
                        </div>
                        <div>
                            <h2 class="ofd-section-title">D.T.R Entries &amp; Calculation Preview ({{ count($entries) }})</h2>
                            <p class="ofd-section-copy">Compare punched times against expected branch schedule and estimated payroll impact.</p>
                        </div>
                    </div>
                </header>

                <div class="ofd-entry-list">
                    @foreach ($entries as $idx => $entry)
                        @php
                            $preview = $previews[$idx] ?? [];
                            $fmtDateIn = filled($entry['date_in'] ?? null) ? Carbon::parse($entry['date_in'])->format('D, M d, Y') : 'Not available';
                            $fmtShortDateIn = filled($entry['date_in'] ?? null) ? Carbon::parse($entry['date_in'])->format('M d, Y') : '-';
                            $fmtTimeIn = filled($entry['time_in'] ?? null) ? Carbon::parse($entry['time_in'])->format('h:i A') : '-';
                            $fmtShortDateOut = filled($entry['date_out'] ?? null) ? Carbon::parse($entry['date_out'])->format('M d, Y') : '-';
                            $fmtTimeOut = filled($entry['time_out'] ?? null) ? Carbon::parse($entry['time_out'])->format('h:i A') : '-';

                            $resolvedSchedType = $preview['schedule_type'] ?: ($entry['schedule_type'] ?: 'Regular');
                            $resolvedSchedStart = filled($preview['schedule_start'] ?? null)
                                ? Carbon::parse($preview['schedule_start'])->format('h:i A')
                                : (filled($entry['schedule_start'] ?? null) ? Carbon::parse($entry['schedule_start'])->format('h:i A') : '-');
                            $resolvedSchedEnd = filled($preview['schedule_end'] ?? null)
                                ? Carbon::parse($preview['schedule_end'])->format('h:i A')
                                : (filled($entry['schedule_end'] ?? null) ? Carbon::parse($entry['schedule_end'])->format('h:i A') : '-');

                            $punchedMins = (int) ($preview['punched_minutes'] ?? 0);
                            $creditedMins = (int) ($preview['credited_work_hrs'] ?? 0);
                            $lateMins = (int) ($preview['late'] ?? 0);
                            $undertimeMins = (int) ($preview['undertime'] ?? 0);
                            $earlyMins = (int) ($preview['early_clock_in'] ?? 0);
                            $otMins = (int) ($preview['overtime'] ?? 0);
                        @endphp
                        <article class="ofd-entry-card">
                            <div class="ofd-entry-top">
                                <p class="ofd-entry-title">
                                    <x-filament::icon icon="heroicon-o-calendar-days" class="ofd-icon-svg" />
                                    Entry #{{ $idx + 1 }} &mdash; {{ $fmtDateIn }}
                                </p>
                                <div class="ofd-entry-badges">
                                    @if (filled($preview['day_part_label'] ?? null))
                                        <span class="ofd-pill {{ ($preview['day_part'] ?? null) === 'unclassified' ? 'ofd-pill--warning' : 'ofd-pill--info' }}">
                                            {{ $preview['day_part_label'] }}
                                        </span>
                                    @endif
                                    @if (! empty($preview['is_holiday']))
                                        <span class="ofd-pill ofd-pill--warning">
                                            Holiday{{ filled($preview['holiday_type'] ?? null) ? ': '.$preview['holiday_type'] : '' }}
                                        </span>
                                    @endif
                                    @if (! empty($preview['has_conflict']))
                                        <span class="ofd-pill ofd-pill--danger">Conflict</span>
                                    @endif
                                </div>
                            </div>

                            <div class="ofd-compare-grid">
                                <div class="ofd-compare-box">
                                    <div class="ofd-meta-head">
                                        <p class="ofd-label">Submitted Punches</p>
                                        <span class="ofd-pill">{{ $formatMinutes($punchedMins) }}</span>
                                    </div>
                                    <div class="ofd-punch-row">
                                        <div>
                                            <p class="ofd-time-big">{{ $fmtTimeIn }}</p>
                                            <p class="ofd-date-small">In &bull; {{ $fmtShortDateIn }}</p>
                                        </div>
                                        <span class="ofd-punch-arrow">&rarr;</span>
                                        <div>
                                            <p class="ofd-time-big">{{ $fmtTimeOut }}</p>
                                            <p class="ofd-date-small">Out &bull; {{ $fmtShortDateOut }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="ofd-compare-box">
                                    <div class="ofd-meta-head">
                                        <p class="ofd-label">Expected Schedule</p>
                                        @if (! empty($preview['schedule_resolved_from_branch']))
                                            <span class="ofd-pill">Branch Default</span>
                                        @endif
                                    </div>
                                    <div style="margin-top: 0.45rem;">
                                        <p class="ofd-time-big">{{ $resolvedSchedType }}</p>
                                        <p class="ofd-date-small">{{ $resolvedSchedStart }} &ndash; {{ $resolvedSchedEnd }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="ofd-impact-strip">
                                <span class="ofd-impact-chip {{ $creditedMins > 0 ? 'ofd-impact-chip--good' : '' }}">
                                    <span class="ofd-impact-chip-label">Credited Work</span>
                                    <span class="ofd-impact-chip-val">{{ $formatMinutes($creditedMins) }} ({{ $creditedMins }}m)</span>
                                </span>
                                <span class="ofd-impact-chip {{ $lateMins > 0 ? 'ofd-impact-chip--warn' : '' }}">
                                    <span class="ofd-impact-chip-label">Late</span>
                                    <span class="ofd-impact-chip-val">{{ $lateMins }}m</span>
                                </span>
                                <span class="ofd-impact-chip {{ $undertimeMins > 0 ? 'ofd-impact-chip--warn' : '' }}">
                                    <span class="ofd-impact-chip-label">Undertime</span>
                                    <span class="ofd-impact-chip-val">{{ $undertimeMins }}m</span>
                                </span>
                                <span class="ofd-impact-chip {{ $earlyMins >= \App\Services\OvertimeApprovalService::MINIMUM_MINUTES ? 'ofd-impact-chip--info' : '' }}">
                                    <span class="ofd-impact-chip-label">Early In</span>
                                    <span class="ofd-impact-chip-val">{{ $earlyMins }}m</span>
                                </span>
                                <span class="ofd-impact-chip {{ $otMins >= \App\Services\OvertimeApprovalService::MINIMUM_MINUTES ? 'ofd-impact-chip--info' : '' }}">
                                    <span class="ofd-impact-chip-label">Overtime</span>
                                    <span class="ofd-impact-chip-val">{{ $otMins }}m</span>
                                </span>
                            </div>

                            @if (filled($preview['error'] ?? null))
                                <div class="ofd-entry-conflict">
                                    Preview note: {{ $preview['error'] }}
                                </div>
                            @elseif (! empty($preview['has_conflict']))
                                <div class="ofd-entry-conflict">
                                    {{ $preview['conflict_message'] }}
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            @if (($showReviewer) && ($submission->reviewed_at || filled($submission->reviewer_remarks) || $submission->generated_dtr_deleted_at))
                <section class="ofd-card">
                    <header class="ofd-card-header">
                        <div class="ofd-heading">
                            <div class="ofd-heading-icon">
                                <x-filament::icon icon="heroicon-o-shield-check" class="ofd-icon-svg" />
                            </div>
                            <div>
                                <h2 class="ofd-section-title">HR Decision &amp; Linked D.T.R</h2>
                                <p class="ofd-section-copy">Audit details and official D.T.R record linkage.</p>
                            </div>
                        </div>
                    </header>

                    <div class="ofd-card-body">
                        <div class="ofd-review-grid">
                            <div class="ofd-review-item">
                                <p class="ofd-label">Reviewed By</p>
                                <p class="ofd-value">{{ $submission->reviewedBy?->username ?: 'Not yet reviewed' }}</p>
                            </div>
                            <div class="ofd-review-item">
                                <p class="ofd-label">Reviewed At</p>
                                <p class="ofd-value">{{ $submission->reviewed_at?->format('M d, Y h:i A') ?: 'Pending' }}</p>
                            </div>
                            <div class="ofd-review-item">
                                <p class="ofd-label">Linked D.T.R Record</p>
                                <p class="ofd-value">
                                    @if ($submission->generated_dtr_deleted_at)
                                        Deleted ({{ $submission->generated_dtr_deleted_at->format('M d, Y') }})
                                    @elseif ($submission->generated_dtr_id)
                                        Official #{{ $submission->generated_dtr_id }}
                                    @else
                                        None
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="ofd-review-remarks">
                            <p class="ofd-label" style="margin-bottom: 0.35rem;">HR Remarks</p>
                            <p class="ofd-body-copy">{{ $submission->reviewer_remarks ?: 'No remarks recorded.' }}</p>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <aside class="ofd-col {{ $compact ? 'ofd-col--sticky' : '' }}">
            <section class="ofd-card">
                <header class="ofd-card-header">
                    <div class="ofd-heading">
                        <div class="ofd-heading-icon">
                            <x-filament::icon icon="heroicon-o-paper-clip" class="ofd-icon-svg" />
                        </div>
                        <div>
                            <h2 class="ofd-section-title">Supporting Proof</h2>
                            <p class="ofd-section-copy">Uploaded attachment for verification.</p>
                        </div>
                    </div>

                    @if ($fileExists && ($ext !== '' || $fileSizeLabel))
                        <span class="ofd-pill">
                            {{ strtoupper($ext ?: 'FILE') }}{{ $fileSizeLabel ? ' • '.$fileSizeLabel : '' }}
                        </span>
                    @endif
                </header>

                <div class="ofd-card-body">
                    <p class="ofd-file-name">{{ $submission->file_name ?: 'No proof file available.' }}</p>

                    @if ($isHrReviewer && $fileExists)
                        @if ($isImage)
                            <a href="{{ route('hr_tools.dtr_submissions.view', $submission) }}" target="_blank" class="ofd-proof-frame" style="display: block;">
                                <img
                                    src="{{ route('hr_tools.dtr_submissions.view', $submission) }}"
                                    alt="On Field DTR supporting proof"
                                    class="ofd-proof-image"
                                    loading="lazy"
                                />
                            </a>
                        @elseif ($isPdf)
                            <div class="ofd-proof-frame">
                                <iframe
                                    src="{{ route('hr_tools.dtr_submissions.view', $submission) }}#view=FitH"
                                    title="On Field DTR proof PDF preview"
                                    class="ofd-proof-pdf"
                                ></iframe>
                            </div>
                        @endif

                        <div class="ofd-actions">
                            <x-filament::button
                                tag="a"
                                :href="route('hr_tools.dtr_submissions.view', $submission)"
                                target="_blank"
                                icon="heroicon-m-eye"
                                color="gray"
                                size="sm"
                            >
                                Open Full View
                            </x-filament::button>
                            <x-filament::button
                                tag="a"
                                :href="route('hr_tools.dtr_submissions.download', $submission)"
                                target="_blank"
                                icon="heroicon-m-arrow-down-tray"
                                size="sm"
                            >
                                Download Proof
                            </x-filament::button>
                        </div>
                    @elseif (! $fileExists && filled($submission->file_name))
                        <p class="ofd-subvalue" style="margin-top: 0.5rem;">Proof file is no longer present on storage.</p>
                    @endif
                </div>
            </section>

            <section class="ofd-card">
                <header class="ofd-card-header">
                    <div class="ofd-heading">
                        <div class="ofd-heading-icon">
                            <x-filament::icon icon="heroicon-o-document-text" class="ofd-icon-svg" />
                        </div>
                        <div>
                            <h2 class="ofd-section-title">Description / Notes</h2>
                            <p class="ofd-section-copy">Submitted field reason or D.T.R comment.</p>
                        </div>
                    </div>
                </header>

                <div class="ofd-card-body">
                    <p class="ofd-body-copy">{{ $submission->description ?: 'No description provided.' }}</p>
                </div>
            </section>
        </aside>
    </div>
</div>
