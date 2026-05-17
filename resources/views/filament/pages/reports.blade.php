<x-filament-panels::page>
    <style>
        .report-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid rgba(148, 163, 184, .35);
            padding: 8px 10px;
            text-align: left;
        }

        .report-table th {
            background: rgb(248, 250, 252);
            color: rgb(15, 23, 42);
            font-weight: 700;
        }

        .dark .report-table th {
            background: rgb(31, 41, 55);
            color: #f8fafc;
        }

        .report-table td {
            color: rgb(15, 23, 42);
        }

        .dark .report-table td {
            color: #e5e7eb;
        }

        .report-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .report-card {
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 8px;
            padding: 14px;
            text-align: left;
            transition: border-color .15s, background .15s;
        }

        .report-card:hover {
            border-color: #2563eb;
            background: rgba(37, 99, 235, .08);
        }

        .report-card.active {
            border-color: #2563eb;
            background: rgba(37, 99, 235, .14);
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .22);
        }

        .report-card.active .report-card-title {
            color: #2563eb;
        }

        .dark .report-card.active .report-card-title {
            color: #93c5fd;
        }

        .report-card-title {
            font-weight: 800;
        }

        .report-card-subtitle {
            margin-top: 4px;
            font-size: 12px;
            color: #64748b;
        }

        .dark .report-card-subtitle {
            color: #94a3b8;
        }

        .report-table-scroll {
            overflow-x: auto;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 8px;
        }

        .export-report-title {
            color: rgb(15, 23, 42);
            font-size: 20px;
            font-weight: 800;
            text-align: center;
        }

        .dark .export-report-title {
            color: #f8fafc;
        }

        @media (max-width: 900px) {
            .report-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .report-cards {
                grid-template-columns: 1fr;
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

            .report-print-area,
            .report-print-area * {
                visibility: visible !important;
            }

            .report-print-area {
                background: #fff !important;
                color: #000 !important;
                position: absolute;
                inset: 0 auto auto 0;
                padding: 10mm;
                width: 100%;
            }

            .report-print-area * {
                color: #000 !important;
            }

            .report-table-scroll {
                overflow: visible;
                border: 0;
            }

            .report-table th,
            .report-table td {
                background: #fff !important;
                border-color: #000 !important;
                color: #000 !important;
            }

            .report-table th {
                background: #f3f4f6 !important;
                color: #000 !important;
            }

            .export-report-title {
                color: #000 !important;
            }
        }
    </style>

    <div style="display: grid; gap: 16px;">
        <div class="report-cards">
            @foreach ($this->reportCards as $category => $reportCard)
                <button
                    type="button"
                    wire:click="selectReportCategory('{{ $category }}')"
                    @class(['report-card', 'active' => $this->report_category === $category])
                >
                    <div class="report-card-title">{{ $reportCard['title'] }}</div>
                    <div class="report-card-subtitle">{{ $reportCard['subtitle'] }}</div>
                </button>
            @endforeach
        </div>

        <div style="max-width: 760px;">
            {{ $this->form }}
        </div>

        <div class="report-print-area" style="display: grid; gap: 12px;">
            @include('filament.pages.partials.company-export-header')

            <h2 class="export-report-title">{{ $this->reportTitle }}</h2>

            <div class="report-table-scroll">
                <table class="report-table">
                    <thead>
                        <tr>
                            @foreach ($this->reportHeaders as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->reportRows as $row)
                            <tr>
                                @foreach ($row as $value)
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(count($this->reportHeaders), 1) }}" style="text-align: center;">No report data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('filament.pages.partials.export-generated-at')
        </div>
    </div>
</x-filament-panels::page>
