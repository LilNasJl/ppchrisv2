@php
    $rows = $this->summaryRows;
    $money = fn ($value) => number_format((float) $value, 2);
@endphp

<x-filament-panels::page>
    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .summary-workspace {
            display: grid;
            gap: 0.75rem;
            width: 100%;
        }

        .summary-topbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.25rem 1.5rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #ffffff;
        }

        .summary-topbar-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .summary-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .summary-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .summary-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .summary-header-actions {
            flex: 1 1 360px;
            min-width: 0;
        }

        .summary-header-actions > div {
            justify-content: flex-end;
        }

        .summary-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            padding: 0.75rem 1rem;
            border: 1px solid rgb(226, 232, 240);
            border-radius: 0.65rem;
            background: #ffffff;
        }

        .dark .summary-toolbar {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .summary-period-field {
            width: min(100%, 25rem);
        }

        .summary-row-count {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: rgb(71, 85, 105);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .dark .summary-row-count {
            color: rgb(203, 213, 225);
        }

        .summary-row-count strong {
            padding: 0.2rem 0.55rem;
            border: 1px solid rgba(96, 165, 250, 0.35);
            border-radius: 9999px;
            background: rgba(37, 99, 235, 0.12);
            color: #60a5fa;
            font-size: 0.75rem;
        }

        .summary-table {
            width: 100%;
            min-width: 820px;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.75rem;
        }

        .summary-table th,
        .summary-table td {
            border-right: 1px solid rgba(148, 163, 184, 0.14);
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            padding: 0.48rem 0.62rem;
            vertical-align: middle;
        }

        .summary-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: rgb(226, 232, 240);
            color: rgb(30, 41, 59);
            text-align: center;
            font-size: 0.63rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .summary-table .summary-report-heading th {
            position: static;
            padding: 0.58rem 0.65rem;
            background: rgb(241, 245, 249);
            color: rgb(15, 23, 42);
            white-space: normal;
        }

        .dark .summary-table .summary-report-heading th {
            background: #050505;
            color: #ffffff;
        }

        .summary-report-title {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .summary-report-period {
            margin: 0.2rem 0 0;
            color: rgb(71, 85, 105);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .dark .summary-report-period {
            color: rgb(191, 219, 254);
        }

        .summary-accessible-caption {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        .summary-table th:first-child,
        .summary-table td:first-child {
            border-left: 0;
        }

        .summary-table th:last-child,
        .summary-table td:last-child {
            border-right: 0;
        }

        .dark .summary-table th {
            background: rgb(30, 41, 59);
            color: rgb(226, 232, 240);
        }

        .summary-table td {
            color: rgb(15, 23, 42);
            font-variant-numeric: tabular-nums;
        }

        .dark .summary-table td {
            color: #e5e7eb;
        }

        .summary-table tbody tr {
            background: #ffffff;
            transition: background-color 120ms ease;
        }

        .summary-table tbody tr:nth-child(even) {
            background: rgb(248, 250, 252);
        }

        .summary-table tbody tr:hover {
            background: rgb(241, 245, 249);
        }

        .dark .summary-table tbody tr {
            background: transparent;
        }

        .dark .summary-table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        .dark .summary-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        .summary-table tbody td:nth-child(2) {
            min-width: 12rem;
            font-weight: 700;
        }

        .summary-table .summary-col-index {
            background: rgb(241, 245, 249);
            color: rgb(100, 116, 139);
            text-align: center;
        }

        .summary-table .summary-col-branch {
            background: rgb(248, 250, 252);
        }

        .summary-table .summary-col-workers {
            background: rgb(236, 254, 255);
        }

        .summary-table .summary-col-gross {
            background: rgb(248, 250, 252);
        }

        .summary-table .summary-col-deductions {
            background: rgb(255, 241, 242);
        }

        .summary-table .summary-col-net {
            background: rgb(236, 253, 245);
            font-weight: 800;
        }

        .summary-table .summary-col-previous {
            background: rgb(241, 245, 249);
        }

        .summary-table .summary-col-variance {
            background: rgb(255, 251, 235);
        }

        .summary-table thead .summary-col-index,
        .summary-table thead .summary-col-branch {
            background: rgb(203, 213, 225);
            color: rgb(51, 65, 85);
        }

        .summary-table thead .summary-col-workers {
            background: rgb(207, 250, 254);
            color: rgb(14, 116, 144);
        }

        .summary-table thead .summary-col-gross {
            background: rgb(203, 213, 225);
            color: rgb(51, 65, 85);
        }

        .summary-table thead .summary-col-deductions {
            background: rgb(255, 228, 230);
            color: rgb(190, 24, 93);
        }

        .summary-table thead .summary-col-net {
            background: rgb(209, 250, 229);
            color: rgb(4, 120, 87);
        }

        .summary-table thead .summary-col-previous {
            background: rgb(226, 232, 240);
            color: rgb(71, 85, 105);
        }

        .summary-table thead .summary-col-variance {
            background: rgb(254, 243, 199);
            color: rgb(180, 83, 9);
        }

        .dark .summary-table tbody .summary-col-index,
        .dark .summary-table tbody .summary-col-branch {
            background: transparent;
        }

        .dark .summary-table tbody .summary-col-workers {
            background: rgba(6, 182, 212, 0.12);
        }

        .dark .summary-table tbody .summary-col-gross {
            background: rgba(148, 163, 184, 0.08);
        }

        .dark .summary-table tbody .summary-col-deductions {
            background: rgba(244, 63, 94, 0.13);
        }

        .dark .summary-table tbody .summary-col-net {
            background: rgba(16, 185, 129, 0.14);
        }

        .dark .summary-table tbody .summary-col-previous {
            background: rgba(100, 116, 139, 0.16);
        }

        .dark .summary-table tbody .summary-col-variance {
            background: rgba(245, 158, 11, 0.13);
        }

        .dark .summary-table thead .summary-col-index,
        .dark .summary-table thead .summary-col-branch {
            background: rgb(51, 65, 85);
            color: rgb(226, 232, 240);
        }

        .dark .summary-table thead .summary-col-workers {
            background: rgba(6, 95, 115, 0.65);
            color: rgb(165, 243, 252);
        }

        .dark .summary-table thead .summary-col-gross {
            background: rgb(51, 65, 85);
            color: rgb(226, 232, 240);
        }

        .dark .summary-table thead .summary-col-deductions {
            background: rgba(136, 19, 55, 0.65);
            color: rgb(254, 205, 211);
        }

        .dark .summary-table thead .summary-col-net {
            background: rgba(6, 95, 70, 0.7);
            color: rgb(167, 243, 208);
        }

        .dark .summary-table thead .summary-col-previous {
            background: rgb(51, 65, 85);
            color: rgb(203, 213, 225);
        }

        .dark .summary-table thead .summary-col-variance {
            background: rgba(146, 64, 14, 0.7);
            color: rgb(253, 230, 138);
        }

        .summary-table tfoot .summary-col-workers {
            background: rgb(207, 250, 254);
        }

        .summary-table tfoot .summary-col-gross {
            background: rgb(226, 232, 240);
        }

        .summary-table tfoot .summary-col-deductions {
            background: rgb(255, 228, 230);
        }

        .summary-table tfoot .summary-col-net {
            background: rgb(209, 250, 229);
        }

        .summary-table tfoot .summary-col-previous {
            background: rgb(226, 232, 240);
        }

        .summary-table tfoot .summary-col-variance {
            background: rgb(254, 243, 199);
        }

        .dark .summary-table tfoot .summary-col-workers {
            background: rgba(6, 95, 115, 0.65);
        }

        .dark .summary-table tfoot .summary-col-gross {
            background: rgb(51, 65, 85);
        }

        .dark .summary-table tfoot .summary-col-deductions {
            background: rgba(136, 19, 55, 0.65);
        }

        .dark .summary-table tfoot .summary-col-net {
            background: rgba(6, 95, 70, 0.7);
        }

        .dark .summary-table tfoot .summary-col-previous {
            background: rgb(51, 65, 85);
        }

        .dark .summary-table tfoot .summary-col-variance {
            background: rgba(146, 64, 14, 0.7);
        }

        .summary-table .text-right {
            text-align: right;
            white-space: nowrap;
        }

        .summary-table tfoot td {
            border-top: 2px solid #3b82f6;
            border-bottom: 0;
            background: rgb(226, 232, 240);
            color: rgb(15, 23, 42);
            font-weight: 800;
        }

        .dark .summary-table tfoot td {
            background: #050505;
            color: #f8fafc;
        }

        .summary-scroll {
            overflow-x: auto;
            max-width: 100%;
            border: 1px solid rgb(203, 213, 225);
            border-radius: 0.65rem;
            background: #ffffff;
            -webkit-overflow-scrolling: touch;
        }

        .dark .summary-scroll {
            border-color: rgb(51, 65, 85);
            background: #050505;
        }

        .summary-empty {
            display: flex;
            min-height: 6rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: rgb(100, 116, 139);
            text-align: center;
        }

        .dark .summary-empty {
            color: rgb(148, 163, 184);
        }

        .summary-empty-icon {
            width: 1.5rem;
            height: 1.5rem;
            color: #60a5fa;
        }

        @media (max-width: 760px) {
            .summary-topbar {
                align-items: stretch;
                padding: 1rem;
            }

            .summary-title {
                font-size: 1.35rem;
            }

            .summary-header-actions {
                flex-basis: 100%;
            }

            .summary-header-actions > div {
                justify-content: flex-start;
            }

            .summary-row-count {
                width: 100%;
            }
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
                position: static;
                background: #f3f4f6 !important;
                color: #000 !important;
            }

            .summary-table tfoot td {
                background: #f3f4f6 !important;
                color: #000 !important;
            }

        }
    </style>

    <div class="summary-workspace">
        <header class="summary-topbar">
            <div class="summary-topbar-copy">
                <p class="summary-eyebrow">WORKFORCE RECORDS</p>
                <h1 class="summary-title">Payroll Summary</h1>
                <p class="summary-subtitle">Consolidated payroll totals for {{ $this->selectedPeriod?->title ?: 'the selected period' }}.</p>
            </div>

            <div class="summary-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <section class="summary-toolbar" aria-label="Payroll summary filters">
            <div class="summary-period-field">
                {{ $this->form }}
            </div>
            <div class="summary-row-count" aria-live="polite">
                Branches in summary <strong>{{ $rows->count() }}</strong>
            </div>
        </section>

        <div class="payroll-print-area" style="display: grid; gap: 12px;">
            @include('filament.pages.partials.company-export-header')

            <div class="summary-scroll" role="region" aria-label="Payroll summary table" tabindex="0">
                <table class="summary-table">
                    <caption class="summary-accessible-caption">Payroll summary by branch for {{ $this->selectedPeriod?->title ?: 'the selected period' }}</caption>
                    <thead>
                        <tr class="summary-report-heading">
                            <th colspan="8">
                                <h2 class="summary-report-title">Salaries &amp; Wages - Summary</h2>
                                <p class="summary-report-period">Payroll period: {{ $this->selectedPeriod?->title ?: '-' }}</p>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col" class="summary-col-index">#</th>
                            <th scope="col" class="summary-col-branch">Branches</th>
                            <th scope="col" class="summary-col-workers">No. of Workers</th>
                            <th scope="col" class="summary-col-gross">Gross Pay</th>
                            <th scope="col" class="summary-col-deductions">Less: Total Deductions</th>
                            <th scope="col" class="summary-col-net">Net Pay</th>
                            <th scope="col" class="summary-col-previous">Previous Net Pay</th>
                            <th scope="col" class="summary-col-variance">Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="summary-col-index">{{ $row['number'] }}</td>
                                <td class="summary-col-branch">{{ $row['branch'] }}</td>
                                <td class="summary-col-workers text-right">{{ $row['workers'] }}</td>
                                <td class="summary-col-gross text-right">{{ $money($row['gross_pay']) }}</td>
                                <td class="summary-col-deductions text-right">{{ $money($row['total_deductions']) }}</td>
                                <td class="summary-col-net text-right">{{ $money($row['net_pay']) }}</td>
                                <td class="summary-col-previous text-right">{{ $money($row['previous_net_pay']) }}</td>
                                <td class="summary-col-variance text-right">{{ $money($row['variance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="summary-empty">
                                        <x-filament::icon icon="heroicon-o-document-text" class="summary-empty-icon" />
                                        <span>No payroll summary available for this period.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td class="summary-col-workers text-right">{{ $rows->sum('workers') }}</td>
                                <td class="summary-col-gross text-right">{{ $money($rows->sum('gross_pay')) }}</td>
                                <td class="summary-col-deductions text-right">{{ $money($rows->sum('total_deductions')) }}</td>
                                <td class="summary-col-net text-right">{{ $money($rows->sum('net_pay')) }}</td>
                                <td class="summary-col-previous text-right">{{ $money($rows->sum('previous_net_pay')) }}</td>
                                <td class="summary-col-variance text-right">{{ $money($rows->sum('variance')) }}</td>
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
