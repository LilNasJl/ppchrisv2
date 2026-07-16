<x-filament-panels::page>
    <style>
        .employee-payroll-page {
            --ep-surface: #ffffff;
            --ep-surface-muted: #f8fafc;
            --ep-text: #0f172a;
            --ep-muted: #64748b;
            --ep-border: rgba(15, 23, 42, .12);
            --ep-blue: #2563eb;
            --ep-blue-soft: #eff6ff;

            color: var(--ep-text);
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .dark .employee-payroll-page {
            --ep-surface: #0f172a;
            --ep-surface-muted: #111827;
            --ep-text: #f8fafc;
            --ep-muted: #94a3b8;
            --ep-border: rgba(148, 163, 184, .24);
            --ep-blue: #60a5fa;
            --ep-blue-soft: rgba(37, 99, 235, .16);
        }

        .employee-payroll-toolbar {
            align-items: end;
            background: var(--ep-surface);
            border: 1px solid var(--ep-border);
            border-top: 3px solid var(--ep-blue);
            border-radius: 8px;
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1fr) minmax(260px, 360px);
            padding: 16px;
        }

        .employee-payroll-heading {
            align-items: center;
            display: flex;
            gap: 11px;
        }

        .employee-payroll-heading-icon {
            align-items: center;
            background: var(--ep-blue-soft);
            border-radius: 8px;
            color: var(--ep-blue);
            display: inline-flex;
            flex: 0 0 40px;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .employee-payroll-heading-icon svg {
            height: 21px;
            width: 21px;
        }

        .employee-payroll-heading h2 {
            color: var(--ep-text);
            font-size: 17px;
            font-weight: 850;
            line-height: 1.2;
            margin: 0;
        }

        .employee-payroll-heading p {
            color: var(--ep-muted);
            font-size: 12px;
            margin: 3px 0 0;
        }

        .employee-payroll-filter {
            min-width: 0;
        }

        .employee-payroll-card {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .employee-payroll-item {
            background: var(--ep-surface);
            border: 1px solid var(--ep-border);
            border-radius: 8px;
            min-width: 0;
            padding: 12px 13px;
        }

        .employee-payroll-label {
            color: var(--ep-muted);
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .employee-payroll-value {
            color: var(--ep-text);
            font-size: 14px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .employee-payroll-empty {
            background: var(--ep-surface);
            border: 1px dashed var(--ep-border);
            border-radius: 8px;
            color: var(--ep-muted);
            font-size: 13px;
            font-weight: 700;
            padding: 22px;
        }

        .employee-payroll-page .payroll-scroll {
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            scrollbar-color: var(--ep-blue) var(--ep-surface-muted);
            scrollbar-width: thin;
        }

        .employee-payroll-page .payroll-table {
            font-size: 10.5px;
            min-width: 1940px;
        }

        .employee-payroll-page .payroll-table th,
        .employee-payroll-page .payroll-table td {
            height: 30px;
            padding: 4px 5px;
        }

        .employee-payroll-page .payroll-table th {
            font-size: 10px;
            line-height: 1.2;
        }

        @media (max-width: 900px) {
            .employee-payroll-toolbar {
                grid-template-columns: 1fr;
            }

            .employee-payroll-card {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .employee-payroll-card {
                grid-template-columns: 1fr;
            }

            .employee-payroll-page .payroll-table {
                font-size: 10px;
                min-width: 1820px;
            }
        }
    </style>

    <div class="employee-payroll-page">
        <section class="employee-payroll-toolbar">
            <div class="employee-payroll-heading">
                <span class="employee-payroll-heading-icon">
                    <x-filament::icon icon="heroicon-o-banknotes" />
                </span>
                <div>
                    <h2>Payroll Summary</h2>
                    <p>View your finalized payroll by period.</p>
                </div>
            </div>

            <div class="employee-payroll-filter">
                {{ $this->form }}
            </div>
        </section>

        @if (! $this->selectedPeriod)
            <div class="employee-payroll-empty">No payroll summary available yet.</div>
        @elseif ($this->employee)
            <div class="employee-payroll-card">
                <div class="employee-payroll-item">
                    <div class="employee-payroll-label">Employee</div>
                    <div class="employee-payroll-value">{{ $this->employee->full_name }}</div>
                </div>
                <div class="employee-payroll-item">
                    <div class="employee-payroll-label">Designation</div>
                    <div class="employee-payroll-value">{{ $this->employee->designation?->title ?: '-' }}</div>
                </div>
                <div class="employee-payroll-item">
                    <div class="employee-payroll-label">Department</div>
                    <div class="employee-payroll-value">{{ $this->employee->department?->name ?: '-' }}</div>
                </div>
                <div class="employee-payroll-item">
                    <div class="employee-payroll-label">Branch</div>
                    <div class="employee-payroll-value">{{ $this->employee->branch?->branch_name ?: '-' }}</div>
                </div>
            </div>

            @include('filament.pages.partials.payroll-detail-table', [
                'rows' => $this->payrollRow ? collect([$this->payrollRow]) : collect(),
            ])
        @endif
    </div>
</x-filament-panels::page>
