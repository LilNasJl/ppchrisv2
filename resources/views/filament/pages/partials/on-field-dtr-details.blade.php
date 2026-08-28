@php
    $statusColor = match ($submission->status) {
        \App\Models\DtrSubmission::STATUS_APPROVED => 'success',
        \App\Models\DtrSubmission::STATUS_REJECTED => 'danger',
        default => 'warning',
    };
@endphp

@once
    <style>
        .ofd-page {
            --ofd-text: #0f172a;
            --ofd-muted: #64748b;
            --ofd-border: #dbe4f0;
            --ofd-surface: #ffffff;
            --ofd-surface-soft: #f8fafc;
            --ofd-blue-soft: #eff6ff;
            --ofd-blue: #2563eb;
            --ofd-blue-strong: #1e40af;
            display: grid;
            width: 100%;
            gap: 24px;
            color: var(--ofd-text);
        }

        .dark .ofd-page {
            --ofd-text: #f8fafc;
            --ofd-muted: #94a3b8;
            --ofd-border: rgba(148, 163, 184, 0.22);
            --ofd-surface: rgba(255, 255, 255, 0.035);
            --ofd-surface-soft: rgba(255, 255, 255, 0.05);
            --ofd-blue-soft: rgba(37, 99, 235, 0.14);
            --ofd-blue: #60a5fa;
            --ofd-blue-strong: #bfdbfe;
        }

        .ofd-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 24px;
            border: 1px solid color-mix(in srgb, var(--ofd-blue) 28%, var(--ofd-border));
            border-radius: 8px;
            background: var(--ofd-blue-soft);
        }

        .ofd-identity,
        .ofd-heading {
            display: flex;
            min-width: 0;
            align-items: flex-start;
            gap: 14px;
        }

        .ofd-hero-icon,
        .ofd-heading-icon {
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 8px;
        }

        .ofd-hero-icon {
            width: 46px;
            height: 46px;
            color: #ffffff;
            background: #2563eb;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.2);
        }

        .ofd-heading-icon {
            width: 38px;
            height: 38px;
            color: var(--ofd-blue);
            background: var(--ofd-blue-soft);
        }

        .ofd-icon-svg {
            width: 22px;
            height: 22px;
        }

        .ofd-heading-icon .ofd-icon-svg {
            width: 19px;
            height: 19px;
        }

        .ofd-eyebrow,
        .ofd-label {
            margin: 0;
            color: var(--ofd-muted);
            font-size: 12px;
            font-weight: 600;
            line-height: 1.45;
        }

        .ofd-eyebrow {
            color: var(--ofd-blue-strong);
            text-transform: uppercase;
        }

        .ofd-employee-name {
            margin: 4px 0 0;
            overflow-wrap: anywhere;
            color: var(--ofd-text);
            font-size: clamp(20px, 2vw, 26px);
            font-weight: 750;
            line-height: 1.2;
        }

        .ofd-employee-meta,
        .ofd-section-copy,
        .ofd-body-copy,
        .ofd-file-name {
            color: var(--ofd-muted);
            font-size: 14px;
            line-height: 1.65;
        }

        .ofd-employee-meta {
            margin: 6px 0 0;
        }

        .ofd-divider {
            margin: 0 7px;
            color: var(--ofd-border);
        }

        .ofd-status {
            flex: 0 0 auto;
            text-align: right;
        }

        .ofd-status-badge {
            display: flex;
            justify-content: flex-end;
            margin-top: 6px;
        }

        .ofd-meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px 32px;
            padding: 20px 4px;
            border-top: 1px solid var(--ofd-border);
            border-bottom: 1px solid var(--ofd-border);
        }

        .ofd-meta-item,
        .ofd-review-item {
            min-width: 0;
        }

        .ofd-value {
            margin: 5px 0 0;
            overflow-wrap: anywhere;
            color: var(--ofd-text);
            font-size: 14px;
            font-weight: 650;
            line-height: 1.5;
        }

        .ofd-monospace {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
        }

        .ofd-section {
            min-width: 0;
        }

        .ofd-section-title {
            margin: 0;
            color: var(--ofd-text);
            font-size: 17px;
            font-weight: 700;
            line-height: 1.35;
        }

        .ofd-section-copy {
            margin: 2px 0 0;
        }

        .ofd-entry-grid,
        .ofd-document-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .ofd-entry-card {
            min-width: 0;
            padding: 18px;
            border: 1px solid var(--ofd-border);
            border-radius: 8px;
            background: var(--ofd-surface);
        }

        .ofd-entry-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: var(--ofd-text);
            font-size: 14px;
            font-weight: 700;
        }

        .ofd-entry-title .ofd-icon-svg,
        .ofd-inline-title .ofd-icon-svg {
            width: 19px;
            height: 19px;
            color: var(--ofd-blue);
        }

        .ofd-entry-values {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 18px;
        }

        .ofd-document-grid {
            margin-top: 0;
            padding-top: 24px;
            border-top: 1px solid var(--ofd-border);
        }

        .ofd-document-panel {
            min-width: 0;
            padding-right: 18px;
        }

        .ofd-inline-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            color: var(--ofd-text);
            font-size: 16px;
            font-weight: 700;
        }

        .ofd-file-name,
        .ofd-body-copy {
            margin: 12px 0 0;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .ofd-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }

        .ofd-review {
            padding: 20px;
            border: 1px solid var(--ofd-border);
            border-radius: 8px;
            background: var(--ofd-surface-soft);
        }

        .ofd-review-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-top: 18px;
        }

        .ofd-review-remarks {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--ofd-border);
        }

        @media (max-width: 1100px) {
            .ofd-meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .ofd-page {
                gap: 20px;
            }

            .ofd-hero {
                align-items: flex-start;
                flex-direction: column;
                padding: 20px;
            }

            .ofd-status {
                width: 100%;
                padding-top: 15px;
                border-top: 1px solid var(--ofd-border);
                text-align: left;
            }

            .ofd-status-badge {
                justify-content: flex-start;
            }

            .ofd-entry-grid,
            .ofd-document-grid {
                grid-template-columns: 1fr;
            }

            .ofd-document-panel {
                padding-right: 0;
            }

            .ofd-review-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .ofd-meta-grid,
            .ofd-review-grid,
            .ofd-entry-values {
                grid-template-columns: 1fr;
            }

            .ofd-hero-icon {
                width: 40px;
                height: 40px;
            }
        }
    </style>
@endonce

<div class="ofd-page">
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
            <p class="ofd-label">Request status</p>
            <div class="ofd-status-badge">
                <x-filament::badge :color="$statusColor">{{ $submission->status }}</x-filament::badge>
            </div>
        </div>
    </section>

    <section class="ofd-meta-grid">
        @foreach ([
            'Payroll Period' => $submission->payrollPeriod?->title ?: 'Not available',
            'Date Submitted' => $submission->created_at?->format('M d, Y h:i A') ?: 'Not available',
            'Submitted By' => $submission->submittedEmployeeName(),
            'Request ID' => $submission->publicKey(),
        ] as $label => $value)
            <div class="ofd-meta-item">
                <p class="ofd-label">{{ $label }}</p>
                <p class="ofd-value {{ $label === 'Request ID' ? 'ofd-monospace' : '' }}">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <section class="ofd-section">
        <div class="ofd-heading">
            <div class="ofd-heading-icon">
                <x-filament::icon icon="heroicon-o-clock" class="ofd-icon-svg" />
            </div>
            <div>
                <h2 class="ofd-section-title">DTR Entries</h2>
                <p class="ofd-section-copy">Submitted attendance interval for review.</p>
            </div>
        </div>

        <div class="ofd-entry-grid">
            @foreach ([
                ['title' => 'Time In', 'icon' => 'heroicon-o-arrow-right-on-rectangle', 'date' => $submission->date_in?->format('M d, Y'), 'time' => filled($submission->time_in) ? \Carbon\Carbon::parse($submission->time_in)->format('h:i A') : null],
                ['title' => 'Time Out', 'icon' => 'heroicon-o-arrow-left-on-rectangle', 'date' => $submission->date_out?->format('M d, Y'), 'time' => filled($submission->time_out) ? \Carbon\Carbon::parse($submission->time_out)->format('h:i A') : null],
            ] as $entry)
                <article class="ofd-entry-card">
                    <p class="ofd-entry-title">
                        <x-filament::icon :icon="$entry['icon']" class="ofd-icon-svg" />
                        {{ $entry['title'] }}
                    </p>
                    <div class="ofd-entry-values">
                        <div>
                            <p class="ofd-label">Date</p>
                            <p class="ofd-value">{{ $entry['date'] ?: 'Not available' }}</p>
                        </div>
                        <div>
                            <p class="ofd-label">Time</p>
                            <p class="ofd-value">{{ $entry['time'] ?: 'Not available' }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="ofd-document-grid">
        <div class="ofd-document-panel">
            <h2 class="ofd-inline-title">
                <x-filament::icon icon="heroicon-o-paper-clip" class="ofd-icon-svg" />
                Proof File
            </h2>
            <p class="ofd-file-name">{{ $submission->file_name ?: 'No proof file available.' }}</p>
            @auth
                <div class="ofd-actions">
                    <x-filament::button
                        tag="a"
                        :href="route('hr_tools.dtr_submissions.view', $submission)"
                        target="_blank"
                        icon="heroicon-m-eye"
                        color="gray"
                    >
                        View
                    </x-filament::button>
                    <x-filament::button
                        tag="a"
                        :href="route('hr_tools.dtr_submissions.download', $submission)"
                        target="_blank"
                        icon="heroicon-m-arrow-down-tray"
                    >
                        Download
                    </x-filament::button>
                </div>
            @endauth
        </div>

        <div class="ofd-document-panel">
            <h2 class="ofd-inline-title">
                <x-filament::icon icon="heroicon-o-document-text" class="ofd-icon-svg" />
                Description
            </h2>
            <p class="ofd-body-copy">{{ $submission->description ?: 'No description provided.' }}</p>
        </div>
    </section>

    @if (($showReviewer ?? false) && $submission->reviewed_at)
        <section class="ofd-review">
            <h2 class="ofd-inline-title">
                <x-filament::icon icon="heroicon-o-shield-check" class="ofd-icon-svg" />
                HR Review
            </h2>
            <div class="ofd-review-grid">
                <div class="ofd-review-item">
                    <p class="ofd-label">Reviewed By</p>
                    <p class="ofd-value">{{ $submission->reviewedBy?->username ?: 'Account no longer available' }}</p>
                </div>
                <div class="ofd-review-item">
                    <p class="ofd-label">Reviewed At</p>
                    <p class="ofd-value">{{ $submission->reviewed_at->format('M d, Y h:i A') }}</p>
                </div>
                <div class="ofd-review-item">
                    <p class="ofd-label">Generated DTR</p>
                    <p class="ofd-value">{{ $submission->generated_dtr_id ? 'Linked record #'.$submission->generated_dtr_id : 'None' }}</p>
                </div>
            </div>
            <div class="ofd-review-remarks">
                <p class="ofd-label">HR Remarks</p>
                <p class="ofd-body-copy">{{ $submission->reviewer_remarks ?: 'No remarks.' }}</p>
            </div>
        </section>
    @endif
</div>
