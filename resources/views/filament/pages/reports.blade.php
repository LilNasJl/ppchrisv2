<x-filament-panels::page>
    <style>
        .reports-shell {
            display: grid;
            gap: 18px;
        }

        .reports-intro {
            align-items: end;
            display: flex;
            gap: 14px;
            justify-content: space-between;
        }

        .reports-kicker {
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .dark .reports-kicker {
            color: #93c5fd;
        }

        .reports-title {
            color: rgb(15, 23, 42);
            font-size: 22px;
            font-weight: 850;
            line-height: 1.2;
            margin-top: 4px;
        }

        .dark .reports-title {
            color: #f8fafc;
        }

        .reports-description {
            color: #64748b;
            font-size: 13px;
            margin-top: 6px;
            max-width: 720px;
        }

        .dark .reports-description {
            color: #94a3b8;
        }

        .reports-layout {
            align-items: start;
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(240px, 300px) minmax(0, 1fr);
        }

        .reports-side,
        .reports-main-panel,
        .reports-preview-panel {
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 8px;
            background: rgba(255, 255, 255, .82);
        }

        .dark .reports-side,
        .dark .reports-main-panel,
        .dark .reports-preview-panel {
            background: rgba(17, 24, 39, .55);
            border-color: rgba(148, 163, 184, .18);
        }

        .reports-side {
            padding: 10px;
            position: sticky;
            top: 90px;
        }

        .reports-section-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            padding: 4px 4px 10px;
            text-transform: uppercase;
        }

        .dark .reports-section-label {
            color: #94a3b8;
        }

        .reports-main {
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .reports-main-panel {
            padding: 16px;
        }

        .reports-form-header,
        .reports-preview-header {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .reports-panel-title {
            color: rgb(15, 23, 42);
            font-size: 15px;
            font-weight: 800;
        }

        .dark .reports-panel-title {
            color: #f8fafc;
        }

        .reports-panel-subtitle {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }

        .dark .reports-panel-subtitle {
            color: #94a3b8;
        }

        .reports-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .reports-pill {
            align-items: center;
            background: rgba(37, 99, 235, .10);
            border: 1px solid rgba(37, 99, 235, .18);
            border-radius: 999px;
            color: #1d4ed8;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
            padding: 5px 10px;
            white-space: nowrap;
        }

        .dark .reports-pill {
            background: rgba(59, 130, 246, .16);
            border-color: rgba(147, 197, 253, .22);
            color: #bfdbfe;
        }

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
            position: sticky;
            top: 0;
            z-index: 1;
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
            gap: 8px;
        }

        .report-card {
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 8px;
            padding: 12px;
            text-align: left;
            transition: border-color .15s, background .15s;
            width: 100%;
        }

        .report-card:hover {
            border-color: #2563eb;
            background: rgba(37, 99, 235, .08);
        }

        .report-card.active {
            border-color: #2563eb;
            background: rgba(37, 99, 235, .14);
            box-shadow: inset 3px 0 0 #2563eb;
        }

        .report-card.active .report-card-title {
            color: #2563eb;
        }

        .dark .report-card.active .report-card-title {
            color: #93c5fd;
        }

        .report-card-title {
            color: rgb(15, 23, 42);
            font-weight: 800;
        }

        .dark .report-card-title {
            color: #f8fafc;
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
            max-height: 620px;
            overflow-x: auto;
            overflow-y: auto;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 8px;
        }

        .reports-export-only {
            display: none;
        }

        .report-table tbody tr:nth-child(even) td {
            background: rgba(248, 250, 252, .72);
        }

        .dark .report-table tbody tr:nth-child(even) td {
            background: rgba(30, 41, 59, .42);
        }

        .report-table tbody tr:hover td {
            background: rgba(37, 99, 235, .08);
        }

        .dark .report-table tbody tr:hover td {
            background: rgba(96, 165, 250, .14);
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

        @media (max-width: 1100px) {
            .reports-layout {
                grid-template-columns: 1fr;
            }

            .reports-side {
                position: static;
            }

            .report-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .reports-intro,
            .reports-form-header,
            .reports-preview-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .reports-meta {
                justify-content: flex-start;
            }

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

            .reports-export-only {
                display: block !important;
            }

            .reports-screen-only {
                display: none !important;
            }

            .report-table-scroll {
                overflow: visible;
                max-height: none;
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

    <div class="reports-shell">
        <div class="reports-intro">
            <div>
                <div class="reports-kicker">Reports Center</div>
                <div class="reports-title">Generate HR, payroll, DTR, and activity reports</div>
                <div class="reports-description">
                    Choose a report group, set the needed period or month, then preview the generated data before exporting.
                </div>
            </div>

            <div class="reports-meta">
                <span class="reports-pill">{{ count($this->reportRows) }} row/s</span>
                <span class="reports-pill">{{ $this->reportTitle }}</span>
            </div>
        </div>

        <div class="reports-layout">
            <aside class="reports-side">
                <div class="reports-section-label">Report Groups</div>
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
            </aside>

            <div class="reports-main">
                <section class="reports-main-panel">
                    <div class="reports-form-header">
                        <div>
                            <div class="reports-panel-title">Configure Report</div>
                            <div class="reports-panel-subtitle">Only required filters appear for the selected report.</div>
                        </div>
                    </div>

                    {{ $this->form }}
                </section>

                <section class="report-print-area reports-preview-panel" style="display: grid; gap: 12px; padding: 16px;">
                    <div class="reports-export-only">
                        @include('filament.pages.partials.company-export-header')
                    </div>

                    <div class="reports-preview-header reports-screen-only">
                        <div>
                            <div class="reports-panel-title">{{ $this->reportTitle }}</div>
                            <div class="reports-panel-subtitle">Preview is based on your selected report type and filters.</div>
                        </div>

                        <div class="reports-meta">
                            <span class="reports-pill">{{ count($this->reportRows) }} row/s</span>
                        </div>
                    </div>

                    <h2 class="export-report-title reports-export-only">{{ $this->reportTitle }}</h2>

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

                    <div class="reports-export-only">
                        @include('filament.pages.partials.export-generated-at')
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
