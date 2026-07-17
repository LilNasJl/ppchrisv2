<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Time Record - {{ $employee->full_name }}</title>
    <style>
        :root { color-scheme: light only; }
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #e5e7eb; color: #111827; font-family: Arial, Helvetica, sans-serif; }
        body { padding: 24px; }
        .toolbar { align-items: center; display: flex; gap: 10px; justify-content: flex-end; margin: 0 auto 14px; max-width: 210mm; }
        .toolbar button { background: #1d4ed8; border: 0; border-radius: 6px; color: #fff; cursor: pointer; font-size: 13px; font-weight: 700; padding: 9px 14px; }
        .toolbar button.secondary { background: #374151; }
        .sheet { background: #fff; margin: 0 auto; min-height: 297mm; padding: 12mm; width: 210mm; }
        .company-header { align-items: center; display: grid; grid-template-columns: 72px 1fr 72px; margin-bottom: 12px; text-align: center; }
        .company-header img { height: 60px; object-fit: contain; width: 60px; }
        .company-name { font-size: 17px; font-weight: 800; }
        .company-address { font-size: 10px; line-height: 1.4; margin-top: 3px; }
        h1 { font-size: 16px; letter-spacing: 0; margin: 14px 0 12px; text-align: center; }
        .employee-details { display: grid; font-size: 11px; gap: 6px 18px; grid-template-columns: 1fr 1fr; margin-bottom: 14px; }
        .detail { border-bottom: 1px solid #6b7280; display: grid; grid-template-columns: 90px 1fr; padding: 3px 0; }
        .detail-label { font-weight: 700; }
        table { border-collapse: collapse; font-size: 10px; table-layout: fixed; width: 100%; }
        th, td { border: 1px solid #4b5563; color: #111827; padding: 5px 4px; text-align: center; vertical-align: middle; }
        th { background: #eaf2ff; font-weight: 800; }
        th:nth-child(1), td:nth-child(1) { width: 5%; }
        th:nth-child(2), td:nth-child(2), th:nth-child(4), td:nth-child(4) { width: 17%; }
        th:nth-child(3), td:nth-child(3), th:nth-child(5), td:nth-child(5) { width: 13%; }
        th:nth-child(6), td:nth-child(6) { width: 35%; }
        .empty { color: #4b5563; padding: 18px; }
        .signatures { display: grid; gap: 48px; grid-template-columns: 1fr 1fr; margin-top: 48px; text-align: center; }
        .signature-line { border-top: 1px solid #111827; font-size: 10px; padding-top: 5px; }
        .generated { color: #374151; font-size: 9px; margin-top: 34px; text-align: right; }
        @page { margin: 10mm; size: A4 portrait; }
        @media print {
            html, body { background: #fff !important; color: #000 !important; }
            body { padding: 0; }
            .toolbar { display: none !important; }
            .sheet { box-shadow: none; min-height: auto; padding: 0; width: auto; }
            th { background: #eaf2ff !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; page-break-inside: avoid; }
        }
        @media (max-width: 860px) {
            body { padding: 0; }
            .toolbar { padding: 10px; }
            .sheet { min-height: 100vh; overflow-x: auto; padding: 18px; width: 100%; }
            .company-header { grid-template-columns: 58px 1fr 20px; }
            .employee-details { grid-template-columns: 1fr; }
            table { min-width: 720px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="secondary" type="button" onclick="window.close()">Close</button>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="sheet">
        <header class="company-header">
            <div>@if ($logo)<img src="{{ $logo }}" alt="Philfumes logo">@endif</div>
            <div>
                <div class="company-name">{{ $companyName }}</div>
                <div class="company-address">{{ $companyAddress }}</div>
            </div>
            <div></div>
        </header>

        <h1>DAILY TIME RECORD</h1>

        <section class="employee-details">
            <div class="detail"><span class="detail-label">Employee ID</span><span>{{ $employee->company_id }}</span></div>
            <div class="detail"><span class="detail-label">Payroll Period</span><span>{{ $period->title }}</span></div>
            <div class="detail"><span class="detail-label">Name</span><span>{{ $employee->full_name }}</span></div>
            <div class="detail"><span class="detail-label">Branch</span><span>{{ $branch->branch_name }}</span></div>
            <div class="detail"><span class="detail-label">Designation</span><span>{{ $employee->designation?->title ?: '-' }}</span></div>
            <div class="detail"><span class="detail-label">Period Range</span><span>{{ \Illuminate\Support\Carbon::parse($period->date_start)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($period->date_end)->format('M d, Y') }}</span></div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date In</th>
                    <th>Time In</th>
                    <th>Date Out</th>
                    <th>Time Out</th>
                    <th>Status / Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['date_in'] }}</td>
                        <td>{{ $row['time_in'] }}</td>
                        <td>{{ $row['date_out'] }}</td>
                        <td>{{ $row['time_out'] }}</td>
                        <td>{{ $row['status'] }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="6">No D.T.R entries are available for this payroll period.</td></tr>
                @endforelse
            </tbody>
        </table>

        <section class="signatures">
            <div class="signature-line">Employee Signature</div>
            <div class="signature-line">Verified By / HR</div>
        </section>

        <div class="generated">Date Generated: {{ $generatedAt }}</div>
    </main>
</body>
</html>
