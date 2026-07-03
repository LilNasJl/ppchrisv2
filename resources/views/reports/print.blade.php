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
            size: A4 {{ $isLandscape ? 'landscape' : 'portrait' }};
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
            line-height: 1.35;
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

        .report-document {
            background: #fff;
            color: #000;
            margin: 0 auto;
            max-width: 100%;
            padding: 12mm;
            width: 100%;
        }

        .company-header {
            align-items: start;
            display: grid;
            gap: 10px;
            grid-template-columns: 76px 1fr 76px;
            margin-bottom: 14px;
            text-align: center;
        }

        .company-logo {
            height: 54px;
            object-fit: contain;
            width: 76px;
        }

        .company-name {
            font-size: 16px;
            font-weight: 800;
            line-height: 1.2;
        }

        .company-address {
            font-size: 10px;
            line-height: 1.25;
        }

        .report-title {
            font-size: 14px;
            font-weight: 800;
            margin: 0 0 6px;
            text-align: center;
        }

        .report-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 14px;
            justify-content: center;
            margin-bottom: 12px;
            text-align: center;
        }

        .report-filters strong {
            font-weight: 700;
        }

        table {
            border-collapse: collapse;
            table-layout: auto;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            color: #000;
            overflow-wrap: anywhere;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }

        thead {
            display: table-header-group;
        }

        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .empty-state {
            padding: 18px;
            text-align: center;
        }

        .generated-at {
            font-size: 9px;
            margin: -4px 0 8px;
            text-align: right;
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

            .report-document {
                margin: 0;
                max-width: none;
                padding: 12mm;
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

    <main class="report-document">
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

        <h1 class="report-title">{{ $title }}</h1>

        @if ($filters !== [])
            <div class="report-filters">
                @foreach ($filters as $label => $value)
                    <span><strong>{{ $label }}:</strong> {{ $value }}</span>
                @endforeach
            </div>
        @endif

        <div class="generated-at">
            Date Generated: {{ \App\Support\CompanyExportHeader::generatedAt() }}
        </div>

        <table>
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(count($headers), 1) }}" class="empty-state">No report data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </main>

    <script>
        window.addEventListener('load', () => {
            window.setTimeout(() => window.print(), 150);
        });
    </script>
</body>
</html>
