@php
    $submission = $this->submission;
    $statusColor = match ($submission?->status) {
        \App\Models\DtrSubmission::STATUS_APPROVED => 'success',
        \App\Models\DtrSubmission::STATUS_REJECTED => 'danger',
        default => 'warning',
    };
    $periodLocked = (bool) $submission?->payrollPeriod?->is_locked;
@endphp

<x-filament-panels::page>
    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .hr-ofd-workspace {
            display: grid;
            width: 100%;
            gap: 1rem;
        }

        .hr-ofd-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.15rem 1.4rem;
            border: 1px solid rgba(59, 130, 246, 0.55);
            border-radius: 0.85rem;
            background: linear-gradient(115deg, #104294 0%, #173668 58%, #0f1f3d 100%);
            box-shadow: 0 10px 28px rgba(2, 6, 23, 0.35);
            color: #ffffff;
        }

        .hr-ofd-header-copy {
            display: flex;
            min-width: 0;
            flex: 1 1 360px;
            flex-direction: column;
            gap: 0.35rem;
        }

        .hr-ofd-eyebrow {
            margin: 0;
            color: #fde047;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .hr-ofd-title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem;
        }

        .hr-ofd-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.015em;
            line-height: 1.2;
        }

        .hr-ofd-subtitle {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
            margin: 0;
            color: #dbeafe;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.45;
        }

        .hr-ofd-dot {
            opacity: 0.55;
        }

        .hr-ofd-lock-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.12rem 0.5rem;
            border-radius: 9999px;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(251, 191, 36, 0.45);
            color: #fde68a;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .hr-ofd-header-actions {
            flex: 0 1 auto;
            min-width: 0;
        }

        .hr-ofd-header-actions > div {
            justify-content: flex-end;
        }

        @media (max-width: 820px) {
            .hr-ofd-header {
                align-items: stretch;
                padding: 1rem 1.1rem;
            }

            .hr-ofd-title {
                font-size: 1.3rem;
            }

            .hr-ofd-header-actions {
                flex-basis: 100%;
            }

            .hr-ofd-header-actions > div {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="hr-ofd-workspace">
        <header class="hr-ofd-header">
            <div class="hr-ofd-header-copy">
                <p class="hr-ofd-eyebrow">Workforce Records &bull; On Field D.T.R Request</p>
                <div class="hr-ofd-title-row">
                    <h1 class="hr-ofd-title">{{ $submission?->submittedEmployeeName() ?: 'On Field DTR' }}</h1>
                    @if ($submission)
                        <x-filament::badge :color="$statusColor" size="sm">
                            {{ $submission->status }}
                        </x-filament::badge>
                    @endif
                    @if ($periodLocked)
                        <span class="hr-ofd-lock-pill">Locked Period</span>
                    @endif
                </div>
                <p class="hr-ofd-subtitle">
                    <span>{{ $submission?->employee_company_id_snapshot ?: 'No Employee ID' }}</span>
                    <span class="hr-ofd-dot">&bull;</span>
                    <span>{{ $submission?->submittedBranchName() ?: 'No Branch' }}</span>
                    <span class="hr-ofd-dot">&bull;</span>
                    <span>Period: {{ $submission?->payrollPeriod?->title ?: 'Not available' }}</span>
                </p>
            </div>

            <div class="hr-ofd-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        @if ($submission)
            @include('filament.pages.partials.on-field-dtr-details', [
                'submission' => $submission,
                'showReviewer' => true,
                'compact' => true,
                'hideHero' => true,
            ])
        @endif
    </div>
</x-filament-panels::page>
