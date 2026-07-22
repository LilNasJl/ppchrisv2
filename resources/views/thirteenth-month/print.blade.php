<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>&#8203;</title>
    <style>
        :root { color-scheme: light only; }
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { background: #fff !important; color: #000 !important; margin: 0; min-height: 100%; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 1.3; }
        .toolbar { align-items: center; background: #f8fafc; border-bottom: 1px solid #d1d5db; display: flex; gap: 8px; justify-content: flex-end; padding: 10px 16px; }
        .toolbar button { background: #fff; border: 1px solid #9ca3af; border-radius: 6px; color: #111827; cursor: pointer; font: inherit; font-weight: 700; padding: 7px 12px; }
        .toolbar .primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .document { background: #fff; color: #000; margin: 0 auto; padding: 10mm; width: 100%; }
        .company-header { align-items: start; display: grid; gap: 10px; grid-template-columns: 76px 1fr 76px; margin-bottom: 10px; text-align: center; }
        .company-logo { height: 54px; object-fit: contain; width: 76px; }
        .company-name { font-size: 16px; font-weight: 800; line-height: 1.2; }
        .company-address { font-size: 9px; }
        h1 { font-size: 14px; margin: 0 0 4px; text-align: center; }
        .subtitle { font-size: 10px; font-weight: 700; margin-bottom: 8px; text-align: center; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; color: #000; padding: 3px 4px; vertical-align: top; }
        th { background: #eff6ff; font-weight: 700; text-align: center; }
        th.period-open { background: #fff7ed; }
        th.period-missing { background: #f1f5f9; color: #475569; }
        .period-state { display: block; font-size: .8em; font-weight: 400; }
        td.numeric { text-align: right; }
        tfoot td { background: #eff6ff; font-weight: 700; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        .empty { padding: 18px; text-align: center; }
        .signatories { display: grid; gap: 18px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 24px; }
        .signatory-name { border-top: 1px solid #000; font-weight: 700; padding-top: 4px; text-align: center; }
        .signatory-label { font-size: 8px; text-align: center; }
        .generated { font-size: 8px; margin-top: 16px; text-align: right; }
        @media print { html, body { background: #fff !important; color-scheme: light !important; height: auto; overflow: visible; } .toolbar { display: none !important; } .document { margin: 0; padding: 10mm; width: 100%; } }
    </style>
</head>
<body>
    @php
        $money = fn ($value) => number_format((float) $value, 2);
        $periodColumnGroups = collect($periodColumns)->groupBy('month');
    @endphp
    <div class="toolbar"><button type="button" onclick="window.close()">Close</button><button type="button" class="primary" onclick="{{ \App\Support\CompanyExportHeader::printScript() }}">Print / Save PDF</button></div>
    <main class="document">
        <header class="company-header">
            @if ($logo = \App\Support\CompanyExportHeader::logoDataUri())<img src="{{ $logo }}" alt="Philfumes logo" class="company-logo">@else<div></div>@endif
            <div><div class="company-name">{{ \App\Support\CompanyExportHeader::COMPANY_NAME }}</div><div class="company-address">{{ \App\Support\CompanyExportHeader::ADDRESS_LINE }}</div><div class="company-address">{{ \App\Support\CompanyExportHeader::PROVINCE_LINE }}</div></div><div></div>
        </header>
        <h1>{{ $viewMode === 'summary' ? $segmentLabel.' - Summary' : $segmentLabel }}</h1>
        <div class="subtitle">{{ $periodLabel }} | Eligible basic pay divided by {{ $divisor }}</div>

        @if ($viewMode === 'summary')
            <table><thead><tr><th>#</th><th>Branch</th><th>Employees</th><th>Eligible Basic Pay</th><th>Calculated Pay</th><th>Released</th><th>Pending</th></tr></thead>
                <tbody>@forelse ($summaryRows as $row)<tr><td>{{ $row['number'] }}</td><td>{{ $row['branch'] }}</td><td class="numeric">{{ $row['employees'] }}</td><td class="numeric">{{ $money($row['basis_total']) }}</td><td class="numeric">{{ $money($row['calculated_total']) }}</td><td class="numeric">{{ $row['released_count'] }} released{{ $row['partial_count'] ? ', '.$row['partial_count'].' partial' : '' }} / {{ $money($row['released_total']) }}</td><td class="numeric">{{ $row['pending_count'] }} / {{ $money($row['pending_total']) }}</td></tr>@empty<tr><td colspan="7" class="empty">No locked payroll data is available.</td></tr>@endforelse</tbody>
                @if ($summaryRows->isNotEmpty())<tfoot><tr><td colspan="2">GRAND TOTAL</td><td class="numeric">{{ $summaryRows->sum('employees') }}</td><td class="numeric">{{ $money($summaryRows->sum('basis_total')) }}</td><td class="numeric">{{ $money($summaryRows->sum('calculated_total')) }}</td><td class="numeric">{{ $money($summaryRows->sum('released_total')) }}</td><td class="numeric">{{ $money($summaryRows->sum('pending_total')) }}</td></tr></tfoot>@endif
            </table>
        @else
            <table style="font-size: {{ count($periodColumns) > 12 ? '6px' : '8px' }}">
                <thead>
                    <tr><th rowspan="2">#</th><th rowspan="2">Name</th><th rowspan="2">Branch</th><th rowspan="2">Designation</th>@foreach ($periodColumnGroups as $columns)<th colspan="{{ $columns->count() }}">{{ $columns->first()['month_label'] }}</th>@endforeach<th rowspan="2">Eligible Total</th><th rowspan="2">{{ $segmentLabel }}</th><th rowspan="2">Status</th><th rowspan="2">Signature</th></tr>
                    <tr>@foreach ($periodColumns as $column)<th class="{{ $column['status'] === 'Open' ? 'period-open' : ($column['status'] === 'Not Created' ? 'period-missing' : '') }}" title="{{ $column['title'] }}">{{ $column['period_label'] }}<span class="period-state">{{ $column['status'] }}</span></th>@endforeach</tr>
                </thead>
                <tbody>@forelse ($rows as $row)<tr><td>{{ $row['number'] }}</td><td>{{ $row['employee_name'] }}</td><td>{{ $row['branch'] }}</td><td>{{ $row['designation'] }}</td>@foreach ($periodColumns as $key => $column)<td class="numeric">{{ $money($row['period_amounts'][$key] ?? 0) }}</td>@endforeach<td class="numeric">{{ $money($row['basis_total']) }}</td><td class="numeric">{{ $money($row['calculated_amount']) }}</td><td>{{ $row['release_status'] }}</td><td></td></tr>@empty<tr><td colspan="{{ 8 + count($periodColumns) }}" class="empty">No locked payroll data is available.</td></tr>@endforelse</tbody>
            </table>
        @endif

        <div class="signatories"><div><div class="signatory-name">{{ $signatory->prepared_by ?: 'Prepared By' }}</div><div class="signatory-label">Prepared by</div></div><div><div class="signatory-name">{{ $signatory->checked_by ?: 'Checked By' }}</div><div class="signatory-label">Checked by</div></div><div><div class="signatory-name">{{ $signatory->approved_by ?: 'Approved By' }}</div><div class="signatory-label">Approved by</div></div></div>
        <div class="generated">Date Generated: {{ \App\Support\CompanyExportHeader::generatedAt() }}</div>
    </main>
</body>
</html>
