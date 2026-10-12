<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getMemoStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "COMPANY NOTICES & CIRCULARS";
        }

        .emp-memo-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        .emp-memo-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .emp-memo-kpi {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            padding: 0.7rem 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            transition: all 120ms ease;
        }

        .emp-memo-kpi:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .dark .emp-memo-kpi {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .emp-memo-kpi:hover {
            border-color: #3b82f6;
        }

        .emp-memo-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .emp-memo-kpi.primary {
            background: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .emp-memo-icon {
            padding: 0.45rem;
            border-radius: 0.45rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .emp-memo-icon {
            background: #1e293b;
            border-color: #334155;
        }

        .emp-memo-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.12rem;
        }

        .emp-memo-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .emp-memo-label {
            color: #94a3b8;
        }

        .emp-memo-val {
            font-size: 1.15rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .emp-memo-val {
            color: #f8fafc;
        }

        .emp-memo-subtext {
            font-size: 0.67rem;
            color: #64748b;
        }

        .dark .emp-memo-subtext {
            color: #94a3b8;
        }

        @media (max-width: 640px) {
            .emp-memo-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="emp-memo-page employee-blue-page">
        {{-- KPI Cards --}}
        <div class="emp-memo-kpis">
            <div class="emp-memo-kpi primary">
                <div class="emp-memo-icon">
                    <x-filament::icon icon="heroicon-m-megaphone" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="emp-memo-body">
                    <span class="emp-memo-label">TOTAL NOTICES</span>
                    <div class="emp-memo-val text-blue-700 dark:text-blue-300">{{ $stats['total'] }}</div>
                    <span class="emp-memo-subtext">All issued notices</span>
                </div>
            </div>

            <div class="emp-memo-kpi">
                <div class="emp-memo-icon">
                    <x-filament::icon icon="heroicon-m-calendar" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="emp-memo-body">
                    <span class="emp-memo-label">RECENT NOTICES</span>
                    <div class="emp-memo-val text-emerald-700 dark:text-emerald-300">{{ $stats['this_month'] }}</div>
                    <span class="emp-memo-subtext">Issued past 30 days</span>
                </div>
            </div>

            <div class="emp-memo-kpi">
                <div class="emp-memo-icon">
                    <x-filament::icon icon="heroicon-m-paper-clip" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="emp-memo-body">
                    <span class="emp-memo-label">WITH ATTACHMENTS</span>
                    <div class="emp-memo-val text-indigo-700 dark:text-indigo-300">{{ $stats['with_attachment'] }}</div>
                    <span class="emp-memo-subtext">Official files attached</span>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
