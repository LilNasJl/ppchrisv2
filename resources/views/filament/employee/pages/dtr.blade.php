<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    <style>
        .employee-dtr-page {
            --ed-surface: #ffffff;
            --ed-muted-surface: #f8fafc;
            --ed-text: #0f172a;
            --ed-muted: #64748b;
            --ed-border: rgba(37, 99, 235, .2);
            --ed-blue: #2563eb;
            --ed-blue-soft: #eff6ff;

            color: var(--ed-text);
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .dark .employee-dtr-page {
            --ed-surface: #0f172a;
            --ed-muted-surface: #111827;
            --ed-text: #f8fafc;
            --ed-muted: #94a3b8;
            --ed-border: rgba(96, 165, 250, .28);
            --ed-blue: #60a5fa;
            --ed-blue-soft: rgba(30, 64, 175, .22);
        }

        .employee-dtr-toolbar {
            align-items: end;
            background: var(--ed-surface);
            border: 1px solid var(--ed-border);
            border-top: 3px solid var(--ed-blue);
            border-radius: 8px;
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1fr) minmax(260px, 360px);
            padding: 16px;
        }

        .employee-dtr-heading {
            align-items: center;
            display: flex;
            gap: 11px;
        }

        .employee-dtr-heading-icon {
            align-items: center;
            background: var(--ed-blue-soft);
            border-radius: 8px;
            color: var(--ed-blue);
            display: inline-flex;
            flex: 0 0 40px;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .employee-dtr-heading-icon svg {
            height: 21px;
            width: 21px;
        }

        .employee-dtr-heading h2 {
            color: var(--ed-text);
            font-size: 17px;
            font-weight: 850;
            line-height: 1.2;
            margin: 0;
        }

        .employee-dtr-heading p {
            color: var(--ed-muted);
            font-size: 12px;
            margin: 3px 0 0;
        }

        .employee-dtr-overview {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .employee-dtr-metric {
            background: var(--ed-surface);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            min-width: 0;
            padding: 12px 13px;
        }

        .employee-dtr-metric {
            background: linear-gradient(180deg, var(--ed-surface), var(--ed-blue-soft));
        }

        .employee-dtr-label {
            color: var(--ed-muted);
            font-size: 11px;
            font-weight: 750;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .employee-dtr-value {
            color: var(--ed-text);
            font-size: 14px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .employee-dtr-metric .employee-dtr-value {
            color: var(--ed-blue);
            font-size: 18px;
        }

        .employee-dtr-empty {
            background: var(--ed-surface);
            border: 1px dashed var(--ed-border);
            border-radius: 8px;
            color: var(--ed-muted);
            font-size: 13px;
            font-weight: 700;
            padding: 22px;
        }

        .employee-dtr-tabs {
            display: grid;
            gap: 12px;
            min-width: 0;
        }

        .employee-dtr-tab-list {
            align-items: center;
            background: var(--ed-muted-surface);
            border: 1px solid var(--ed-border);
            border-radius: 8px;
            display: inline-flex;
            gap: 4px;
            justify-self: start;
            padding: 4px;
        }

        .employee-dtr-tab {
            align-items: center;
            border: 0;
            border-radius: 6px;
            color: var(--ed-muted);
            cursor: pointer;
            display: inline-flex;
            font-size: 13px;
            font-weight: 750;
            gap: 7px;
            min-height: 36px;
            padding: 8px 13px;
        }

        .employee-dtr-tab svg {
            height: 17px;
            width: 17px;
        }

        .employee-dtr-tab.is-active {
            background: var(--ed-blue);
            color: #ffffff;
        }

        .employee-dtr-tab-panel {
            min-width: 0;
        }

        [x-cloak] {
            display: none !important;
        }

        @media (max-width: 1024px) {
            .employee-dtr-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .employee-dtr-toolbar {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .employee-dtr-overview {
                grid-template-columns: 1fr;
            }

            .employee-dtr-tab-list {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                justify-self: stretch;
            }

            .employee-dtr-tab {
                justify-content: center;
            }
        }
    </style>

    <div class="employee-dtr-page employee-blue-page">
        <section class="employee-dtr-toolbar">
            <div class="employee-dtr-heading">
                <span class="employee-dtr-heading-icon">
                    <x-filament::icon icon="heroicon-o-clock" />
                </span>
                <div>
                    <h2>Daily Time Record</h2>
                    <p>Review your attendance by payroll period. All records are view only.</p>
                </div>
            </div>

            <div>{{ $this->form }}</div>
        </section>

        @if (! $this->selectedPeriod)
            <div class="employee-dtr-empty">No payroll period is available yet.</div>
        @elseif ($this->employee)
            <div x-data="{ activeTab: 'dtr' }" class="employee-dtr-tabs">
                <div class="employee-dtr-tab-list" role="tablist" aria-label="D.T.R views">
                    <button
                        type="button"
                        class="employee-dtr-tab"
                        x-bind:class="{ 'is-active': activeTab === 'dtr' }"
                        x-bind:aria-selected="activeTab === 'dtr'"
                        x-on:click="activeTab = 'dtr'"
                        role="tab"
                    >
                        <x-filament::icon icon="heroicon-o-table-cells" />
                        D.T.R
                    </button>
                    <button
                        type="button"
                        class="employee-dtr-tab"
                        x-bind:class="{ 'is-active': activeTab === 'overview' }"
                        x-bind:aria-selected="activeTab === 'overview'"
                        x-on:click="activeTab = 'overview'"
                        role="tab"
                    >
                        <x-filament::icon icon="heroicon-o-chart-bar-square" />
                        Overview
                    </button>
                </div>

                <section x-show="activeTab === 'dtr'" class="employee-dtr-tab-panel" role="tabpanel">
                    {{ $this->table }}
                </section>

                <section x-cloak x-show="activeTab === 'overview'" class="employee-dtr-tab-panel" role="tabpanel">
                    @php($overview = $this->overview)
                    <div class="employee-dtr-overview" aria-label="D.T.R overview">
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Payroll Period</div>
                            <div class="employee-dtr-value">{{ $this->selectedPeriod->title }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Status</div>
                            <div class="employee-dtr-value">{{ $this->selectedPeriod->is_locked ? 'Locked' : 'Open' }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Total Entries</div>
                            <div class="employee-dtr-value">{{ number_format($overview['total_entries']) }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Present Days</div>
                            <div class="employee-dtr-value">{{ $this->formatDayUnits($overview['present_entries']) }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Leave</div>
                            <div class="employee-dtr-value">{{ number_format($overview['leave_entries']) }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Absent</div>
                            <div class="employee-dtr-value">{{ number_format($overview['absent_entries']) }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Total Late</div>
                            <div class="employee-dtr-value">{{ $this->formatMinutes($overview['total_late']) }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Total Undertime</div>
                            <div class="employee-dtr-value">{{ $this->formatMinutes($overview['total_undertime']) }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Credited Overtime</div>
                            <div class="employee-dtr-value">{{ $this->formatDuration($overview['total_credited_overtime']) }}</div>
                        </div>
                        <div class="employee-dtr-metric">
                            <div class="employee-dtr-label">Credited Work Hours</div>
                            <div class="employee-dtr-value">{{ $this->formatDuration($overview['total_credited_work']) }}</div>
                        </div>
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
