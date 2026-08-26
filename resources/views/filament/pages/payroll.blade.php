<x-filament-panels::page>
    <style>
        .payroll-workspace {
            width: 100%;
            max-width: 72rem;
            margin-inline: auto;
        }

        .payroll-workspace-header {
            max-width: 56rem;
            margin-inline: auto;
            text-align: center;
        }

        .payroll-eyebrow {
            color: rgb(37, 99, 235);
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .payroll-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1.75rem;
        }

        .payroll-action-button {
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

        .payroll-action-button:hover {
            background: rgb(29, 78, 216);
            transform: translateY(-1px);
        }

        .payroll-action-button.secondary {
            background: rgb(15, 23, 42);
        }

        .dark .payroll-action-button.secondary {
            background: rgb(30, 64, 175);
        }

        .payroll-action-icon {
            width: 1.2rem;
            height: 1.2rem;
            flex: none;
        }

        .payroll-title {
            color: rgb(15, 23, 42);
            font-size: clamp(1.5rem, 3vw, 2.25rem);
            font-weight: 900;
            text-align: center;
        }

        .dark .payroll-title {
            color: white;
        }

        .payroll-copy {
            margin: 0.65rem auto 1.5rem;
            max-width: 38rem;
            color: rgb(71, 85, 105);
            text-align: center;
            line-height: 1.6;
        }

        .dark .payroll-copy {
            color: rgb(203, 213, 225);
        }

        .payroll-period-panel {
            width: min(100%, 48rem);
            margin: 2rem auto 0;
            border-top: 1px solid rgb(226, 232, 240);
            padding-top: 1.75rem;
        }

        .dark .payroll-period-panel {
            border-color: rgb(51, 65, 85);
        }

        @media (max-width: 640px) {
            .payroll-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="payroll-workspace">
        <header class="payroll-workspace-header">
            <p class="payroll-eyebrow">Compensation Operations</p>
            <h2 class="payroll-title">Payroll Processing</h2>
            <p class="payroll-copy">
                Manage payroll periods and employee access, or select a period to review branches, payroll details, and summaries.
            </p>

            <div class="payroll-actions">
                <a href="{{ $this->managePayrollPeriodsUrl() }}" class="payroll-action-button">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="payroll-action-icon" />
                    Manage Payroll Periods
                </a>

                <a href="{{ $this->payrollVisibilityUrl() }}" class="payroll-action-button secondary">
                    <x-filament::icon icon="heroicon-o-eye" class="payroll-action-icon" />
                    Payroll Visibility
                </a>
            </div>
        </header>

        <section class="payroll-period-panel">
            <h2 class="payroll-title">Select Payroll Period</h2>
            <p class="payroll-copy">
                Search and select a payroll period to manage branch and employee payroll records.
            </p>

            {{ $this->form }}
        </section>
    </div>
</x-filament-panels::page>
