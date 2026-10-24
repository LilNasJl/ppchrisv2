<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>&#8203;</title>

    <style>
        :root {
            color-scheme: light only;
        }

        @page {
            size: landscape;
            margin: 4mm 5mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            background: #fff !important;
            color: #000 !important;
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.2;
        }

        .print-toolbar {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #d1d5db;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            padding: 8px 16px;
        }

        .print-toolbar button {
            background: #fff;
            border: 1px solid #9ca3af;
            border-radius: 6px;
            color: #111827;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 6px 12px;
        }

        .print-toolbar .primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .payroll-document {
            background: #fff;
            color: #000;
            margin: 0 auto;
            max-width: 100%;
            padding: 4mm 6mm;
            width: 100%;
        }

        .payroll-scroll {
            overflow: visible !important;
            border: 0 !important;
            max-width: 100% !important;
        }

        .payroll-table {
            min-width: 0 !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            font-family: 'Arial Narrow', Arial, sans-serif !important;
            font-size: 5pt !important;
        }

        .payroll-table th,
        .payroll-table td {
            background: #fff !important;
            border: 0.5pt solid #444 !important;
            color: #000 !important;
            padding: 1.5px 1px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            text-overflow: clip !important;
            font-size: 5pt !important;
            line-height: 1.1 !important;
        }

        .payroll-table th {
            background: #f1f5f9 !important;
            font-weight: 700 !important;
            text-align: center !important;
            line-height: 1.05 !important;
            white-space: normal !important;
        }

        .payroll-table td.col-name {
            text-align: left !important;
            white-space: nowrap !important;
        }

        .payroll-table td.col-rate {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-table td.col-bank-id {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-table td.col-branch {
            text-align: left !important;
            white-space: nowrap !important;
        }

        .payroll-table td.col-designation {
            text-align: left !important;
            white-space: normal !important;
            line-height: 1.05 !important;
        }

        .payroll-table td.text-right {
            text-align: right !important;
            white-space: nowrap !important;
            font-variant-numeric: tabular-nums !important;
        }

        .payroll-table td.text-center {
            text-align: center !important;
            white-space: nowrap !important;
        }

        .payroll-col-index,
        .payroll-col-bank-id,
        .payroll-col-name {
            position: static !important;
            inset-inline-start: auto !important;
            left: auto !important;
            width: auto !important;
            min-width: 0 !important;
            max-width: none !important;
            box-shadow: none !important;
        }

        .company-header {
            align-items: start;
            display: grid;
            gap: 8px;
            grid-template-columns: 60px 1fr 60px;
            margin-bottom: 4px;
            text-align: center;
        }

        .company-logo {
            height: 36px;
            object-fit: contain;
            width: 60px;
        }

        .company-name {
            font-size: 13px;
            font-weight: 800;
            line-height: 1.15;
        }

        .company-address {
            font-size: 8.5px;
            line-height: 1.2;
        }

        .document-title {
            font-size: 12px;
            font-weight: 800;
            margin: 0 0 2px;
            text-align: center;
        }

        .period-label {
            font-size: 8.5px;
            font-weight: 700;
            margin-bottom: 2px;
            text-align: center;
        }

        .generated-at {
            font-size: 7.5px;
            margin-bottom: 4px;
            text-align: right;
        }

        .payroll-section {
            margin-top: 4px;
        }

        .signatories {
            break-inside: avoid;
            page-break-inside: avoid;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 8px;
            padding-top: 4px;
        }

        .signatory-name {
            border-top: 1px solid #000;
            font-size: 8.5px;
            font-weight: 700;
            padding-top: 3px;
            text-align: center;
        }

        .signatory-label {
            font-size: 7.5px;
            margin-top: 1px;
            text-align: center;
        }

        @media print {
            html,
            body {
                background: #fff !important;
                color-scheme: light !important;
                height: auto;
                overflow: visible;
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .payroll-document {
                margin: 0 !important;
                max-width: none !important;
                padding: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.close()">Close</button>
        <button type="button" class="primary" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="payroll-document">
        <header class="company-header">
            @if ($logo = \App\Support\CompanyExportHeader::logoDataUri())
                <img src="{{ $logo }}" alt="Philfumes logo" class="company-logo">
            @else
                <div></div>
            @endif

            <div>
                <div class="company-name">{{ \App\Support\CompanyExportHeader::COMPANY_NAME }}</div>
                <div class="company-address">{{ \App\Support\CompanyExportHeader::ADDRESS_LINE }}</div>
                <div class="company-address">{{ \App\Support\CompanyExportHeader::PROVINCE_LINE }}</div>
            </div>

            <div></div>
        </header>

        <h1 class="document-title">{{ $scopeName }}</h1>
        <div class="period-label">Payroll Period: {{ $period->title }}</div>
        <div class="generated-at">Date Generated: {{ \App\Support\CompanyExportHeader::generatedAt() }}</div>

        <section class="payroll-section">
            @include('filament.pages.partials.payroll-detail-table', ['rows' => $rows, 'loanTypeColumns' => $loanTypeColumns])
        </section>

        <div class="signatories">
            <div>
                <div class="signatory-name">{{ $signatory->prepared_by ?: 'Prepared By' }}</div>
                <div class="signatory-label">Prepared by</div>
            </div>
            <div>
                <div class="signatory-name">{{ $signatory->checked_by ?: 'Checked By' }}</div>
                <div class="signatory-label">Checked by</div>
            </div>
            <div>
                <div class="signatory-name">{{ $signatory->approved_by ?: 'Approved By' }}</div>
                <div class="signatory-label">Approved by</div>
            </div>
        </div>
    </main>

    <script>
        window.addEventListener('load', () => {
            window.setTimeout(() => window.print(), 150);
        });
    </script>
</body>
</html>
