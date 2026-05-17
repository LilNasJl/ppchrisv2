<x-filament-panels::page>
    <style>
        .employee-payroll-filter {
            max-width: 360px;
        }

        .employee-payroll-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .employee-payroll-value {
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        @media (max-width: 900px) {
            .employee-payroll-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .employee-payroll-filter {
                max-width: 100%;
            }

            .employee-payroll-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div style="display: grid; gap: 16px;">
        <div class="employee-payroll-filter">
            {{ $this->form }}
        </div>

        @if ($this->employee)
            <div class="employee-payroll-summary">
                <div>
                    <div style="font-size: 12px; color: #94a3b8;">Employee</div>
                    <div class="employee-payroll-value">
                        <span style="display: inline-flex; align-items: center; border-radius: 999px; background: rgba(37, 99, 235, .16); color: #2563eb; padding: 4px 10px; max-width: 100%; overflow-wrap: anywhere;">
                            {{ trim($this->employee->lastname . ', ' . (filled($this->employee->middlename) ? $this->employee->middlename . '. ' : '') . $this->employee->firstname) }}
                        </span>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #94a3b8;">Designation</div>
                    <div class="employee-payroll-value">{{ $this->employee->designation?->title ?: '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #94a3b8;">Department</div>
                    <div class="employee-payroll-value">{{ $this->employee->department?->name ?: '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #94a3b8;">Branch</div>
                    <div class="employee-payroll-value">{{ $this->employee->branch?->branch_name ?: '-' }}</div>
                </div>
            </div>
        @endif

        @include('filament.pages.partials.payroll-detail-table', [
            'rows' => $this->payrollRow ? collect([$this->payrollRow]) : collect(),
        ])
    </div>
</x-filament-panels::page>
