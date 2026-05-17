@php
    $rows = $this->summaryRows;
    $money = fn ($value) => number_format((float) $value, 2);
@endphp

<x-filament-panels::page>
    <style>
        .summary-table {
            width: 100%;
            min-width: 860px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid rgba(148, 163, 184, .35);
            padding: 8px 10px;
        }

        .summary-table th {
            background: rgb(248, 250, 252);
            color: rgb(15, 23, 42);
            text-align: center;
            font-weight: 700;
        }

        .dark .summary-table th {
            background: rgb(31, 41, 55);
            color: #f8fafc;
        }

        .summary-table td {
            color: rgb(15, 23, 42);
        }

        .dark .summary-table td {
            color: #e5e7eb;
        }

        .summary-table .text-right {
            text-align: right;
        }

        .summary-table tfoot td {
            background: rgb(241, 245, 249);
            color: rgb(15, 23, 42);
            font-weight: 700;
        }

        .dark .summary-table tfoot td {
            background: rgb(30, 41, 59);
            color: #f8fafc;
        }

        .summary-scroll {
            overflow-x: auto;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 8px;
        }

        .export-title {
            text-align: center;
        }

        .export-title h2 {
            color: rgb(15, 23, 42);
            font-size: 20px;
            font-weight: 800;
        }

        .export-title p {
            color: rgb(51, 65, 85);
            font-size: 13px;
            font-weight: 600;
            margin-top: 4px;
        }

        .dark .export-title h2,
        .dark .export-title p {
            color: #f8fafc;
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
                padding: 10mm;
                width: 100%;
            }

            .payroll-print-area * {
                color: #000 !important;
            }

            .summary-scroll {
                overflow: visible;
                border: 0;
            }

            .summary-table {
                min-width: 0;
                font-size: 10px;
            }

            .summary-table th,
            .summary-table td {
                background: #fff !important;
                border-color: #000 !important;
                color: #000 !important;
            }

            .summary-table th {
                background: #f3f4f6 !important;
                color: #000 !important;
            }

            .summary-table tfoot td {
                background: #f3f4f6 !important;
                color: #000 !important;
            }

            .export-title h2,
            .export-title p {
                color: #000 !important;
            }
        }
    </style>

    <div style="display: grid; gap: 16px;">
        <div style="max-width: 360px;">
            {{ $this->form }}
        </div>

        <div class="payroll-print-area" style="display: grid; gap: 16px;">
            @include('filament.pages.partials.company-export-header')

            <div class="export-title">
                <h2>Salaries & Wages - Summary</h2>
                <p>Payroll period: {{ $this->selectedPeriod?->title ?: '-' }}</p>
            </div>

            <div class="summary-scroll">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Branches</th>
                            <th>No. of Workers</th>
                            <th>Gross Pay</th>
                            <th>Less: Total Deductions</th>
                            <th>Net Pay</th>
                            <th>Previous Net Pay</th>
                            <th>Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['number'] }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td class="text-right">{{ $row['workers'] }}</td>
                                <td class="text-right">{{ $money($row['gross_pay']) }}</td>
                                <td class="text-right">{{ $money($row['total_deductions']) }}</td>
                                <td class="text-right">{{ $money($row['net_pay']) }}</td>
                                <td class="text-right">{{ $money($row['previous_net_pay']) }}</td>
                                <td class="text-right">{{ $money($row['variance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center;">No payroll summary available.</td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td class="text-right">{{ $rows->sum('workers') }}</td>
                                <td class="text-right">{{ $money($rows->sum('gross_pay')) }}</td>
                                <td class="text-right">{{ $money($rows->sum('total_deductions')) }}</td>
                                <td class="text-right">{{ $money($rows->sum('net_pay')) }}</td>
                                <td class="text-right">{{ $money($rows->sum('previous_net_pay')) }}</td>
                                <td class="text-right">{{ $money($rows->sum('variance')) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
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
