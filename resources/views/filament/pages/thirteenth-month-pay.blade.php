@php
    $rows = $this->rows;
    $summaryRows = $this->summaryRows;
    $periodColumns = $this->periodColumns;
    $periodColumnGroups = collect($periodColumns)->groupBy('month');
    $money = fn ($value) => number_format((float) $value, 2);
    $releasedTotal = $rows->sum('released_amount');
    $pendingTotal = $rows->sum('pending_amount');
@endphp

<x-filament-panels::page>
    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .thirteenth-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.1rem 1.4rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #ffffff;
        }

        .thirteenth-header-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .thirteenth-header-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .thirteenth-header-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .thirteenth-header-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .thirteenth-header-actions {
            flex: 1 1 360px;
            min-width: 0;
        }

        .thirteenth-header-actions > div {
            justify-content: flex-end;
        }

        .thirteenth-header-actions .fi-btn {
            min-height: 2.3rem;
            border-radius: 0.45rem;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .thirteenth-page { display: grid; gap: 16px; }
        .thirteenth-filters {
            background: linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(14, 165, 233, .04));
            border: 1px solid rgba(59, 130, 246, .24);
            border-radius: 8px;
            padding: 16px;
        }
        .thirteenth-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
        .thirteenth-tab {
            align-items: center; background: transparent; border: 1px solid rgba(59, 130, 246, .3);
            border-radius: 6px; color: rgb(37, 99, 235); cursor: pointer; display: inline-flex;
            font-size: 13px; font-weight: 700; gap: 6px; padding: 8px 12px;
        }
        .thirteenth-tab.active { background: rgb(37, 99, 235); border-color: rgb(37, 99, 235); color: #fff; }
        .thirteenth-metrics { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .thirteenth-metric {
            background: var(--gray-50); border: 1px solid rgba(59, 130, 246, .18); border-radius: 8px; padding: 14px;
        }
        .dark .thirteenth-metric { background: rgba(30, 41, 59, .65); }
        .thirteenth-metric span { color: rgb(100, 116, 139); display: block; font-size: 12px; font-weight: 600; }
        .dark .thirteenth-metric span { color: rgb(148, 163, 184); }
        .thirteenth-metric strong { color: rgb(29, 78, 216); display: block; font-size: 20px; margin-top: 4px; }
        .dark .thirteenth-metric strong { color: rgb(96, 165, 250); }
        .thirteenth-table-wrap { border: 1px solid rgba(59, 130, 246, .2); border-radius: 8px; overflow-x: auto; }
        .thirteenth-table { border-collapse: collapse; font-size: 12px; min-width: 1750px; width: 100%; }
        .thirteenth-table.summary { min-width: 900px; }
        .thirteenth-table th, .thirteenth-table td { border-bottom: 1px solid rgba(148, 163, 184, .22); padding: 7px 8px; white-space: nowrap; }
        .thirteenth-table th { background: rgb(239, 246, 255); color: rgb(30, 64, 175); font-weight: 800; text-align: center; }
        .dark .thirteenth-table th { background: rgb(30, 58, 138); color: rgb(239, 246, 255); }
        .thirteenth-table th.period-locked { background: rgb(220, 252, 231); color: rgb(21, 128, 61); }
        .thirteenth-table th.period-open { background: rgb(255, 247, 237); color: rgb(194, 65, 12); }
        .thirteenth-table th.period-missing { background: rgb(241, 245, 249); color: rgb(100, 116, 139); }
        .dark .thirteenth-table th.period-locked { background: rgba(22, 101, 52, .45); color: rgb(187, 247, 208); }
        .dark .thirteenth-table th.period-open { background: rgba(154, 52, 18, .4); color: rgb(254, 215, 170); }
        .dark .thirteenth-table th.period-missing { background: rgb(30, 41, 59); color: rgb(148, 163, 184); }
        .period-heading { display: block; font-size: 11px; font-weight: 800; }
        .period-state { display: block; font-size: 9px; font-weight: 700; margin-top: 2px; }
        .thirteenth-table td.period-unavailable { color: rgb(148, 163, 184); }
        .thirteenth-table tbody tr:nth-child(even) { background: rgba(219, 234, 254, .24); }
        .dark .thirteenth-table tbody tr:nth-child(even) { background: rgba(30, 64, 175, .12); }
        .thirteenth-table tbody tr:hover { background: rgba(147, 197, 253, .2); }
        .thirteenth-table td.numeric { font-variant-numeric: tabular-nums; text-align: right; }
        .thirteenth-table tfoot td { background: rgb(239, 246, 255); color: rgb(15, 23, 42); font-weight: 800; }
        .dark .thirteenth-table tfoot td { background: rgb(30, 41, 59); color: #f8fafc; }
        .release-badge { border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 700; padding: 3px 8px; }
        .release-badge.released { background: rgb(220, 252, 231); color: rgb(21, 128, 61); }
        .release-badge.pending { background: rgb(254, 249, 195); color: rgb(161, 98, 7); }
        .release-badge.partial { background: rgb(219, 234, 254); color: rgb(29, 78, 216); }
        .thirteenth-empty { color: rgb(100, 116, 139); padding: 28px !important; text-align: center; }
        .thirteenth-note { color: rgb(71, 85, 105); font-size: 12px; margin: 0; }
        .dark .thirteenth-note { color: rgb(203, 213, 225); }
        .thirteenth-warning { background: rgb(255, 247, 237); border: 1px solid rgb(253, 186, 116); border-radius: 6px; color: rgb(154, 52, 18); font-size: 12px; font-weight: 600; padding: 10px 12px; }
        .dark .thirteenth-warning { background: rgba(124, 45, 18, .2); border-color: rgb(194, 65, 12); color: rgb(254, 215, 170); }
        @media (max-width: 900px) {
            .thirteenth-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .thirteenth-header { align-items: stretch; padding: 1rem; }
            .thirteenth-header-title { font-size: 1.35rem; }
            .thirteenth-header-actions { flex-basis: 100%; }
            .thirteenth-header-actions > div { justify-content: flex-start; }
        }
        @media (max-width: 520px) { .thirteenth-metrics { grid-template-columns: 1fr 1fr; } }
    </style>

    <div class="thirteenth-page">
        <header class="thirteenth-header" aria-labelledby="thirteenth-header-title">
            <div class="thirteenth-header-copy">
                <p class="thirteenth-header-eyebrow">COMPENSATION &amp; BENEFITS</p>
                <h1 class="thirteenth-header-title" id="thirteenth-header-title">13th Month Pay</h1>
                <p class="thirteenth-header-subtitle">13th Month Payouts</p>
            </div>
            <div class="thirteenth-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <section class="thirteenth-filters">
            {{ $this->form }}
        </section>

        <div class="thirteenth-tabs">
            <button type="button" class="thirteenth-tab {{ $this->view_mode === 'details' ? 'active' : '' }}" wire:click="showDetails">
                Employee Details
            </button>
            <button type="button" class="thirteenth-tab {{ $this->view_mode === 'summary' ? 'active' : '' }}" wire:click="showSummary">
                Summary
            </button>
        </div>

        <p class="thirteenth-note">{{ $this->periodLabel }} · Locked payroll basis · Entitlement ÷ {{ $this->divisor }} · Previously paid: {{ $money($releasedTotal) }}</p>
        <p class="thirteenth-note">Payroll payout-date basis · Branch: latest payroll assignment · Missing payroll slots: {{ collect($periodColumns)->where('status', 'Not Created')->count() }}</p>
        @if ($rows->sum('overpaid_amount') > 0)
            <div class="thirteenth-warning">Payments above selected accrual: {{ $money($rows->sum('overpaid_amount')) }}. Existing payments are preserved for reconciliation.</div>
        @endif

        @if ($this->releaseConflict)
            <div class="thirteenth-warning">{{ $this->releaseConflict }}</div>
        @endif

        <section class="thirteenth-metrics" wire:key="thirteenth-metrics-{{ $this->results_version }}">
            <div class="thirteenth-metric"><span>Employees</span><strong>{{ $rows->count() }}</strong></div>
            <div class="thirteenth-metric"><span>Accrued Entitlement</span><strong>{{ $money($rows->sum('calculated_amount')) }}</strong></div>
            <div class="thirteenth-metric"><span>Previously Paid</span><strong>{{ $money($releasedTotal) }}</strong></div>
            <div class="thirteenth-metric"><span>Remaining Balance</span><strong>{{ $money($pendingTotal) }}</strong></div>
        </section>

        @if ($this->view_mode === 'summary')
            <div class="thirteenth-table-wrap" wire:key="thirteenth-summary-{{ $this->results_version }}">
                <table class="thirteenth-table summary">
                    <thead><tr><th>#</th><th>Branch</th><th>Employees</th><th>Eligible Basic Pay</th><th>Calculated Pay</th><th>Released</th><th>Pending</th></tr></thead>
                    <tbody>
                        @forelse ($summaryRows as $row)
                            <tr>
                                <td>{{ $row['number'] }}</td><td>{{ $row['branch'] }}</td>
                                <td class="numeric">{{ $row['employees'] }}</td><td class="numeric">{{ $money($row['basis_total']) }}</td>
                                <td class="numeric">{{ $money($row['calculated_total']) }}</td>
                                <td class="numeric">{{ $row['released_count'] }} released{{ $row['partial_count'] ? ', '.$row['partial_count'].' partial' : '' }} / {{ $money($row['released_total']) }}</td>
                                <td class="numeric">{{ $row['pending_count'] }} / {{ $money($row['pending_total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="thirteenth-empty">No locked payroll data is available for the selected period.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($summaryRows->isNotEmpty())
                        <tfoot><tr><td colspan="2">GRAND TOTAL</td><td class="numeric">{{ $summaryRows->sum('employees') }}</td><td class="numeric">{{ $money($summaryRows->sum('basis_total')) }}</td><td class="numeric">{{ $money($summaryRows->sum('calculated_total')) }}</td><td class="numeric">{{ $money($summaryRows->sum('released_total')) }}</td><td class="numeric">{{ $money($summaryRows->sum('pending_total')) }}</td></tr></tfoot>
                    @endif
                </table>
            </div>
        @else
            <div class="thirteenth-table-wrap" wire:key="thirteenth-details-{{ $this->results_version }}">
                <table class="thirteenth-table">
                    <thead>
                        <tr>
                            <th rowspan="2">#</th><th rowspan="2">Name</th><th rowspan="2">Date Hired</th><th rowspan="2">Branch</th><th rowspan="2">Designation</th>
                            @foreach ($periodColumnGroups as $columns)
                                <th colspan="{{ $columns->count() }}">{{ $columns->first()['month_label'] }}</th>
                            @endforeach
                            <th rowspan="2">Eligible Total</th><th rowspan="2">Accrued Entitlement</th><th rowspan="2">Paid</th><th rowspan="2">Balance</th><th rowspan="2">Status / History</th>
                        </tr>
                        <tr>
                            @foreach ($periodColumns as $column)
                                <th
                                    class="{{ $column['status'] === 'Locked' ? 'period-locked' : ($column['status'] === 'Open' ? 'period-open' : 'period-missing') }}"
                                    title="{{ $column['title'] }}"
                                >
                                    <span class="period-heading">{{ $column['period_label'] }}</span>
                                    <span class="period-state">{{ $column['status'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['number'] }}</td><td>{{ $row['employee_name'] }}</td>
                                <td>{{ $row['date_hired'] }}</td><td>{{ $row['branch'] }}</td><td>{{ $row['designation'] }}</td>
                                @foreach ($periodColumns as $key => $column)
                                    <td class="numeric {{ $column['is_locked'] ? '' : 'period-unavailable' }}">{{ $money($row['period_amounts'][$key] ?? 0) }}</td>
                                @endforeach
                                <td class="numeric">{{ $money($row['basis_total']) }}</td><td class="numeric">{{ $money($row['calculated_amount']) }}</td>
                                <td class="numeric">{{ $money($row['released_amount']) }}</td><td class="numeric">{{ $money($row['pending_amount']) }}</td>
                                <td><span class="release-badge {{ $row['released'] ? 'released' : ($row['partially_released'] ? 'partial' : 'pending') }}">{{ $row['release_status'] }}</span>
                                    @if ($row['release_history'])
                                        <details><summary>Payment History</summary>@foreach ($row['release_history'] as $payment)<div>{{ $payment['segment'] }} · {{ $money($payment['amount']) }} · {{ $payment['date'] }}</div>@endforeach</details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 10 + count($periodColumns) }}" class="thirteenth-empty">No locked payroll data is available for the selected period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
