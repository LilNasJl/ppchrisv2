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
            size: A4 landscape;
            margin: 0;
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
            font-size: 10px;
            line-height: 1.3;
        }

        .print-toolbar {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #d1d5db;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            padding: 10px 16px;
        }

        .print-toolbar button {
            background: #fff;
            border: 1px solid #9ca3af;
            border-radius: 6px;
            color: #111827;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 7px 12px;
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
            padding: 8mm;
            width: 100%;
        }

        .company-header {
            align-items: start;
            display: grid;
            gap: 8px;
            grid-template-columns: 68px 1fr 68px;
            margin-bottom: 9px;
            text-align: center;
        }

        .company-logo {
            height: 46px;
            object-fit: contain;
            width: 68px;
        }

        .company-name {
            font-size: 14px;
            font-weight: 800;
            line-height: 1.15;
        }

        .company-address {
            font-size: 9px;
            line-height: 1.2;
        }

        .document-title {
            font-size: 13px;
            font-weight: 800;
            margin: 0 0 3px;
            text-align: center;
        }

        .period-label {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 3px;
            text-align: center;
        }

        .generated-at {
            font-size: 8px;
            margin-bottom: 6px;
            text-align: right;
        }

        .payroll-section {
            margin-top: 10px;
        }

        .payroll-section:first-of-type {
            margin-top: 0;
        }

        .payroll-section-title {
            color: #000;
            font-size: 11px;
            font-weight: 800;
            margin: 0 0 4px;
        }

        .payroll-table thead {
            display: table-header-group;
        }

        .payroll-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .signatories {
            break-inside: avoid;
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 20px;
        }

        .signatory-name {
            border-top: 1px solid #000;
            font-size: 9px;
            font-weight: 700;
            padding-top: 4px;
            text-align: center;
        }

        .signatory-label {
            font-size: 7px;
            margin-top: 2px;
            text-align: center;
        }

        @media print {
            html,
            body {
                background: #fff !important;
                color-scheme: light !important;
                height: auto;
                overflow: visible;
            }

            .print-toolbar {
                display: none !important;
            }

            .payroll-document {
                margin: 0;
                max-width: none;
                padding: 8mm;
                width: 100%;
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

        <h1 class="document-title">{{ $branch->branch_name }}</h1>
        <div class="period-label">Payroll Period: {{ $period->title }}</div>
        <div class="generated-at">Date Generated: {{ \App\Support\CompanyExportHeader::generatedAt() }}</div>

        <section class="payroll-section">
            <h2 class="payroll-section-title">ATM Payroll</h2>
            @include('filament.pages.partials.payroll-detail-table', ['rows' => $atmRows])
        </section>

        <section class="payroll-section">
            <h2 class="payroll-section-title">Cash Payroll</h2>
            @include('filament.pages.partials.payroll-detail-table', ['rows' => $cashRows])
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
