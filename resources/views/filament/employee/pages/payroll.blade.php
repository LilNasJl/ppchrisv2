<x-filament-panels::page>
    <style>
        .employee-payroll-filter {
            max-width: 360px;
        }

        .employee-payroll-card {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 8px;
        }

        .employee-payroll-item {
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 8px;
            padding: 12px;
        }

        .employee-payroll-label {
            color: #64748b;
            font-size: 12px;
        }

        .dark .employee-payroll-label {
            color: #94a3b8;
        }

        .employee-payroll-value {
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        @media (max-width: 900px) {
            .employee-payroll-card {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .employee-payroll-filter {
                max-width: 100%;
            }

            .employee-payroll-card {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div style="display: grid; gap: 16px;">
        <div class="employee-payroll-filter">
            {{ $this->form }}
        </div>

        @if (! $this->selectedPeriod)
            <x-filament::section>
                No payroll summary available yet.
            </x-filament::section>
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
