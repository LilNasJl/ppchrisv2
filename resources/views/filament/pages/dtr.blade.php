<x-filament-panels::page>
    <style>
        .dtr-workspace {
            width: 100%;
            max-width: 72rem;
            margin-inline: auto;
        }

        .dtr-workspace-header {
            max-width: 56rem;
            margin-inline: auto;
            text-align: center;
        }

        .dtr-eyebrow {
            color: rgb(37, 99, 235);
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .dtr-landing-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1.75rem;
        }

        .dtr-action-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 3.25rem;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            color: #fff;
            background: rgb(37, 99, 235);
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.2);
            transition: transform 150ms ease, background-color 150ms ease;
        }

        .dtr-action-button:hover {
            background: rgb(29, 78, 216);
            transform: translateY(-1px);
        }

        .dtr-action-icon {
            width: 1.2rem;
            height: 1.2rem;
            flex: none;
        }

        .dtr-action-button.secondary {
            background: rgb(15, 23, 42);
        }

        .dark .dtr-action-button.secondary {
            background: rgb(30, 64, 175);
        }

        .dtr-action-badge {
            position: absolute;
            right: -0.45rem;
            top: -0.55rem;
            min-width: 1.45rem;
            border-radius: 999px;
            background: rgb(220, 38, 38);
            color: white;
            font-size: 0.72rem;
            font-weight: 900;
            line-height: 1;
            padding: 0.35rem 0.45rem;
        }

        .dtr-landing-title {
            color: rgb(15, 23, 42);
            font-size: clamp(1.5rem, 3vw, 2.25rem);
            font-weight: 900;
            text-align: center;
        }

        .dark .dtr-landing-title {
            color: white;
        }

        .dtr-landing-copy {
            margin: 0.65rem auto 1.5rem;
            max-width: 34rem;
            color: rgb(71, 85, 105);
            text-align: center;
            line-height: 1.6;
        }

        .dark .dtr-landing-copy {
            color: rgb(203, 213, 225);
        }

        .dtr-period-panel {
            width: min(100%, 48rem);
            margin: 2rem auto 0;
            border-top: 1px solid rgb(226, 232, 240);
            padding-top: 1.75rem;
        }

        .dark .dtr-period-panel {
            border-color: rgb(51, 65, 85);
        }

        @media (max-width: 768px) {
            .dtr-landing-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="dtr-workspace">
        <header class="dtr-workspace-header">
            <p class="dtr-eyebrow">Attendance Operations</p>
            <h2 class="dtr-landing-title">Daily Time Record</h2>
            <p class="dtr-landing-copy">
                Review submissions, import biometric records, or select a payroll period to manage employee attendance.
            </p>

            <div class="dtr-landing-actions">
                <a href="{{ $this->dtrSubmissionsUrl() }}" class="dtr-action-button">
                    <x-filament::icon icon="heroicon-o-inbox-stack" class="dtr-action-icon" />
                    DTR Submissions
                    @if ($this->dtrSubmissionCount() > 0)
                        <span class="dtr-action-badge">{{ $this->dtrSubmissionCount() }}</span>
                    @endif
                </a>

                <a href="{{ $this->dtrProofSubmissionsUrl() }}" class="dtr-action-button">
                    <x-filament::icon icon="heroicon-o-map-pin" class="dtr-action-icon" />
                    On Field DTR
                    @if ($this->dtrProofSubmissionCount() > 0)
                        <span class="dtr-action-badge">{{ $this->dtrProofSubmissionCount() }}</span>
                    @endif
                </a>

                <a href="{{ $this->dtrImporterUrl() }}" class="dtr-action-button secondary">
                    <x-filament::icon icon="heroicon-o-arrow-up-tray" class="dtr-action-icon" />
                    DTR Importer
                </a>
            </div>
        </header>

        <section class="dtr-period-panel">
            <h2 class="dtr-landing-title">Select Payroll Period</h2>
            <p class="dtr-landing-copy">
                Search and select a payroll period to manage branch and employee D.T.R records.
            </p>

            {{ $this->form }}
        </section>
    </div>
</x-filament-panels::page>
