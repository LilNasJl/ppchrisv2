<x-filament-panels::page>
    <style>
        .export-title h2,
        .payroll-section-title {
            color: rgb(15, 23, 42);
            font-weight: 800;
        }

        .export-title p {
            color: rgb(51, 65, 85);
            font-size: 13px;
            font-weight: 600;
        }

        .dark .export-title h2,
        .dark .export-title p,
        .dark .payroll-section-title {
            color: #f8fafc;
        }

        .payroll-print-table {
            display: none;
        }

        .payroll-screen-table {
            max-width: 100%;
            min-width: 0;
            overflow-x: auto;
        }

        @media print {
            @page {
                margin: 0;
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            body * {
                visibility: hidden !important;
            }

            .payroll-print-area,
            .payroll-print-area * {
                visibility: visible !important;
            }

            .payroll-print-area {
                background: #fff !important;
                color: #000 !important;
                position: absolute;
                inset: 0 auto auto 0;
                padding: 8mm;
                width: 100%;
            }

            .payroll-print-area * {
                color: #000 !important;
            }

            .export-title h2,
            .export-title p,
            .payroll-section-title {
                color: #000 !important;
            }

            .payroll-screen-table {
                display: none !important;
            }

            .payroll-print-table {
                display: block !important;
            }
        }
    </style>

    <div style="display: grid; gap: 16px;">
        <div style="max-width: 620px;">
            {{ $this->form }}
        </div>

        <div class="payroll-print-area" style="display: grid; gap: 16px;">
            @include('filament.pages.partials.company-export-header')

            <div class="export-title">
                <h2 style="font-size: 18px; margin-bottom: 4px;">
                    {{ \App\Models\Branch::query()->find($this->branch_id)?->branch_name ?: 'Branch Payroll' }}
                </h2>
                <p>
                    Payroll period: {{ $this->selectedPeriod?->title ?: '-' }}
                </p>
            </div>

            <div class="payroll-screen-table">
                <livewire:payroll-detail-table
                    :period-id="(int) $this->period_id"
                    :branch-id="filled($this->branch_id) ? (int) $this->branch_id : null"
                    :enable-search="true"
                    :initial-search="$this->payroll_search"
                    :initial-page="$this->payroll_page"
                    :initial-per-page="$this->payroll_per_page"
                    :initial-preset="$this->payroll_preset"
                    :key="'branch-payroll-'.$this->period_id.'-'.$this->branch_id.'-'.$this->payroll_page.'-'.$this->payroll_preset"
                />
            </div>
            <div class="payroll-print-table">
                @include('filament.pages.partials.payroll-detail-table', [
                    'rows' => $this->rows,
                ])
            </div>

            @include('filament.pages.partials.payroll-signatories', [
                'preparedBy' => $this->prepared_by,
                'checkedBy' => $this->checked_by,
                'approvedBy' => $this->approved_by,
            ])

            @include('filament.pages.partials.export-generated-at')
        </div>
    </div>
</x-filament-panels::page>
