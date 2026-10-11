<x-filament-panels::page>
    @php
        $categoryIcons = [
            'employee' => 'heroicon-m-users',
            'payroll' => 'heroicon-m-banknotes',
            'dtr' => 'heroicon-m-clock',
            'calendar' => 'heroicon-m-calendar-days',
            'activities' => 'heroicon-m-sparkles',
            'memo' => 'heroicon-m-document-text',
        ];
        $filterLabels = $this->reportFilterLabels;
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header,
        .fi-breadcrumbs {
            display: none !important;
        }

        .reports-workspace {
            --rpt-bg-card: #ffffff;
            --rpt-bg-subtle: #f8fafc;
            --rpt-bg-muted: #f1f5f9;
            --rpt-border: #e2e8f0;
            --rpt-border-strong: #cbd5e1;
            --rpt-text-main: #0f172a;
            --rpt-text-secondary: #334155;
            --rpt-text-muted: #64748b;
            --rpt-accent: #2563eb;
            --rpt-accent-soft: rgba(37, 99, 235, 0.1);
            --rpt-row-even: rgba(248, 250, 252, 0.75);
            --rpt-row-hover: rgba(59, 130, 246, 0.06);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            min-width: 0;
            color: var(--rpt-text-main);
        }

        .dark .reports-workspace {
            --rpt-bg-card: #0f172a;
            --rpt-bg-subtle: #1e293b;
            --rpt-bg-muted: rgba(30, 41, 59, 0.7);
            --rpt-border: #1e293b;
            --rpt-border-strong: #334155;
            --rpt-text-main: #f8fafc;
            --rpt-text-secondary: #cbd5e1;
            --rpt-text-muted: #94a3b8;
            --rpt-accent: #60a5fa;
            --rpt-accent-soft: rgba(59, 130, 246, 0.18);
            --rpt-row-even: rgba(30, 41, 59, 0.35);
            --rpt-row-hover: rgba(59, 130, 246, 0.14);
        }

        /* 1. Compact Topbar Header */
        .reports-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            width: 100%;
            padding: 1.25rem 1.5rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #fff;
            overflow: hidden;
        }

        .reports-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .reports-header-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 0.6rem;
            background: rgb(10 31 68 / 0.45);
            color: #fff;
            border: 1px solid rgb(147 197 253 / 0.35);
            flex-shrink: 0;
        }

        .reports-header-icon svg {
            width: 1.2rem;
            height: 1.2rem;
        }

        .reports-title {
            margin: 0;
            color: #fff;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .reports-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .reports-subtitle {
            margin: 0.35rem 0 0;
            color: #e0ecff;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* 2. Unified Compact Control Card (Categories + Inline Filters + Generate Button) */
        .reports-control-card {
            border: 1px solid var(--rpt-border-strong);
            border-radius: 0.7rem;
            background: var(--rpt-bg-card);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: visible;
        }

        .reports-cat-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem 0.75rem;
            padding: 0.55rem 0.9rem;
            background: var(--rpt-bg-subtle);
            border-bottom: 1px solid var(--rpt-border-strong);
            border-top-left-radius: 0.7rem;
            border-top-right-radius: 0.7rem;
        }

        .reports-category-list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem;
        }

        .reports-category {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.34rem 0.72rem;
            border: 1px solid var(--rpt-border-strong);
            border-radius: 0.45rem;
            background: var(--rpt-bg-card);
            color: var(--rpt-text-secondary);
            font-size: 0.74rem;
            font-weight: 700;
            line-height: 1.2;
            cursor: pointer;
            transition: all 120ms ease;
            white-space: nowrap;
        }

        .reports-category svg {
            width: 0.85rem;
            height: 0.85rem;
            opacity: 0.8;
            flex-shrink: 0;
        }

        .reports-category:hover {
            border-color: var(--rpt-accent);
            color: var(--rpt-text-main);
            background: var(--rpt-bg-muted);
        }

        .reports-category.active {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
        }

        .reports-category.active svg {
            opacity: 1;
        }

        .reports-filter-body {
            padding: 0.75rem 0.95rem;
        }

        .reports-config {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: end;
            gap: 0.75rem;
        }

        /* Compact Filament Form Overrides inside .reports-config */
        .reports-config .fi-sc {
            gap: 0.6rem !important;
        }

        .reports-config .fi-fo-field-wrp {
            gap: 0.2rem !important;
        }

        .reports-config .fi-fo-field-label-content,
        .reports-config .fi-fo-field-wrp-label label {
            font-size: 0.675rem !important;
            font-weight: 750 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.035em !important;
            color: var(--rpt-text-muted) !important;
        }

        .reports-config .fi-input-wrp {
            min-height: 2.15rem !important;
            border-radius: 0.45rem !important;
        }

        .reports-config input.fi-input,
        .reports-config select.fi-select-input {
            padding-block: 0.28rem !important;
            font-size: 0.78rem !important;
        }

        .reports-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: nowrap;
            gap: 0.45rem;
        }

        .reports-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            height: 2.15rem;
            padding: 0 0.85rem;
            border: 1px solid #2563eb;
            border-radius: 0.45rem;
            background: #2563eb;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            transition: all 130ms ease;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
        }

        .reports-button:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .reports-button.secondary {
            border-color: var(--rpt-border-strong);
            background: var(--rpt-bg-card);
            color: var(--rpt-text-secondary);
            box-shadow: none;
        }

        .reports-button.secondary:hover {
            background: var(--rpt-bg-muted);
            border-color: var(--rpt-accent);
            color: var(--rpt-text-main);
        }

        .reports-button svg {
            width: 0.875rem;
            height: 0.875rem;
            flex-shrink: 0;
        }

        .reports-button:disabled {
            cursor: wait;
            opacity: 0.65;
        }

        /* 3. Viewable Report Preview Card */
        .reports-panel {
            min-width: 0;
            border: 1px solid var(--rpt-border-strong);
            border-radius: 0.7rem;
            background: var(--rpt-bg-card);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .reports-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.6rem 1rem;
            padding: 0.6rem 0.95rem;
            border-bottom: 1px solid var(--rpt-border-strong);
            background: var(--rpt-bg-subtle);
        }

        .reports-preview-title-group {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.45rem;
            min-width: 0;
        }

        .reports-panel-title {
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--rpt-text-main);
            font-size: 0.84rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .reports-panel-title svg {
            width: 0.95rem;
            height: 0.95rem;
            color: var(--rpt-accent);
            flex-shrink: 0;
        }

        .reports-preview-count {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.5rem;
            border-radius: 9999px;
            background: var(--rpt-accent-soft);
            color: var(--rpt-accent);
            border: 1px solid rgba(59, 130, 246, 0.28);
            font-size: 0.68rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .reports-filter-chips {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.3rem;
        }

        .reports-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.1rem 0.48rem;
            border-radius: 0.35rem;
            background: rgb(10 31 68 / 0.45);
            border: 1px solid rgb(147 197 253 / 0.35);
            color: #fff;
            font-size: 0.675rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .reports-filter-chip strong {
            color: #bfdbfe;
            font-weight: 700;
        }

        .reports-table-wrap {
            max-height: min(620px, 74vh);
            overflow: auto;
        }

        .reports-table {
            width: 100%;
            min-width: 580px;
            border-collapse: collapse;
            font-size: 0.775rem;
            line-height: 1.3;
        }

        .reports-table th,
        .reports-table td {
            padding: 0.45rem 0.75rem;
            border-bottom: 1px solid var(--rpt-border);
            text-align: left;
            vertical-align: middle;
        }

        .reports-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--rpt-bg-subtle);
            border-bottom: 1px solid var(--rpt-border-strong);
            color: var(--rpt-text-muted);
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .reports-table td {
            color: var(--rpt-text-main);
        }

        .reports-table tbody tr:nth-child(even) {
            background: var(--rpt-row-even);
        }

        .reports-table tbody tr:hover {
            background: var(--rpt-row-hover);
        }

        .reports-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 2rem 1.25rem;
            color: var(--rpt-text-muted);
            text-align: center;
            font-size: 0.8rem;
        }

        .reports-empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            background: var(--rpt-bg-muted);
            color: var(--rpt-accent);
        }

        .reports-empty-icon svg {
            width: 1.1rem;
            height: 1.1rem;
        }

        .reports-preview-note {
            padding: 0.45rem 0.95rem;
            border-top: 1px solid var(--rpt-border-strong);
            background: var(--rpt-bg-subtle);
            color: var(--rpt-text-muted);
            font-size: 0.7rem;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .reports-config {
                grid-template-columns: minmax(0, 1fr);
            }
            .reports-config .reports-actions {
                justify-content: flex-end;
            }
        }

        @media (max-width: 640px) {
            .reports-header {
                padding: 1rem;
            }
            .reports-cat-strip,
            .reports-filter-body,
            .reports-panel-header {
                padding: 0.6rem 0.75rem;
            }
            .reports-actions {
                width: 100%;
                justify-content: flex-start;
            }
            .reports-actions .reports-button {
                flex: 1 1 auto;
            }
        }
    </style>

    <div class="reports-workspace">
        {{-- 1. Compact Topbar Header --}}
        <header class="reports-header">
            <div class="reports-header-left">
                <div class="reports-header-icon">
                    <x-filament::icon icon="heroicon-o-document-chart-bar" />
                </div>
                <div>
                    <p class="reports-eyebrow">WORKFORCE REPORTING</p>
                    <h1 class="reports-title">Analytics &amp; Reports</h1>
                    <p class="reports-subtitle">Select a report category, configure filters, and preview or export live HR, payroll, and DTR data.</p>
                </div>
            </div>

            @if (! empty($filterLabels))
                <div class="reports-filter-chips">
                    @foreach ($filterLabels as $fLabel => $fVal)
                        <span class="reports-filter-chip">
                            <strong>{{ $fLabel }}:</strong> {{ $fVal }}
                        </span>
                    @endforeach
                </div>
            @endif
        </header>

        {{-- 2. Unified Category Tabs + Compact Inline Filter Bar --}}
        <section class="reports-control-card" aria-label="Report selection and filters">
            <div class="reports-cat-strip">
                <div class="reports-category-list" role="group" aria-label="Report categories">
                    @foreach ($this->reportCards as $category => $reportCard)
                        <button
                            type="button"
                            wire:click="selectReportCategory('{{ $category }}')"
                            @class(['reports-category', 'active' => $this->report_category === $category])
                            aria-pressed="{{ $this->report_category === $category ? 'true' : 'false' }}"
                            title="{{ $reportCard['subtitle'] }}"
                        >
                            <x-filament::icon :icon="$categoryIcons[$category] ?? 'heroicon-m-document-chart-bar'" />
                            <span>{{ $reportCard['title'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="reports-filter-body">
                <form class="reports-config" wire:submit="generateReport">
                    <div>
                        {{ $this->form }}
                    </div>
                    <div class="reports-actions">
                        <button type="submit" class="reports-button" wire:loading.attr="disabled" wire:target="generateReport">
                            <x-filament::icon icon="heroicon-o-arrow-path" wire:loading.class="animate-spin" wire:target="generateReport" />
                            <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                            <span wire:loading wire:target="generateReport">Generating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- 3. Viewable Report Preview Card --}}
        <section class="reports-panel" aria-labelledby="reports-preview-title">
            <div class="reports-panel-header">
                <div class="reports-preview-title-group">
                    <h2 id="reports-preview-title" class="reports-panel-title">
                        <x-filament::icon icon="heroicon-m-table-cells" />
                        <span>{{ $this->reportTitle }}</span>
                    </h2>
                    @if ($this->hasCurrentPreview)
                        <span class="reports-preview-count">
                            {{ number_format($this->previewCount) }} {{ \Illuminate\Support\Str::plural('row', $this->previewCount) }}
                        </span>
                    @endif
                </div>

                @if ($this->hasCurrentPreview)
                    <div class="reports-actions">
                        <a class="reports-button secondary" href="{{ $this->printUrl() }}" target="_blank" rel="noopener">
                            <x-filament::icon icon="heroicon-o-printer" />
                            <span>Print / PDF</span>
                        </a>
                        <button type="button" class="reports-button secondary" wire:click="exportExcel">
                            <x-filament::icon icon="heroicon-o-table-cells" />
                            <span>Export Excel</span>
                        </button>
                    </div>
                @endif
            </div>

            @if ($this->hasCurrentPreview)
                <div class="reports-table-wrap">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                @foreach ($this->reportHeaders as $header)
                                    <th scope="col">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->previewRows as $row)
                                <tr>
                                    @foreach ($row as $value)
                                        <td>{{ $value }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ max(count($this->reportHeaders), 1) }}" class="reports-empty">
                                        No results for these filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($this->previewCount > count($this->previewRows))
                    <div class="reports-preview-note">
                        Showing the first {{ count($this->previewRows) }} rows. Export or print includes all {{ number_format($this->previewCount) }} rows.
                    </div>
                @endif
            @else
                <div class="reports-empty">
                    <div class="reports-empty-icon">
                        <x-filament::icon icon="heroicon-o-arrow-path" />
                    </div>
                    <div>
                        {{ $this->generatedSignature ? 'Filters changed. Generate again to refresh the preview.' : 'Select a report and generate it to view results.' }}
                    </div>
                    <button type="button" class="reports-button" wire:click="generateReport" wire:loading.attr="disabled" wire:target="generateReport">
                        <x-filament::icon icon="heroicon-o-play" />
                        <span>Generate Report Now</span>
                    </button>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
