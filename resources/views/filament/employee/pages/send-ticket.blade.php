<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    @php
        $stats = $this->getTicketStats();
    @endphp

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "EMPLOYEE HELP DESK";
        }

        .emp-ticket-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 1600px;
            margin-inline: auto;
        }

        .emp-ticket-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.65rem;
            width: 100%;
        }

        .emp-ticket-kpi {
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

        .emp-ticket-kpi:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }

        .dark .emp-ticket-kpi {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .emp-ticket-kpi:hover {
            border-color: #3b82f6;
        }

        .emp-ticket-kpi.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .emp-ticket-kpi.primary {
            background: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .emp-kpi-icon {
            padding: 0.45rem;
            border-radius: 0.45rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .emp-kpi-icon {
            background: #1e293b;
            border-color: #334155;
        }

        .emp-kpi-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.12rem;
        }

        .emp-kpi-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .emp-kpi-label {
            color: #94a3b8;
        }

        .emp-kpi-val {
            font-size: 1.15rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .emp-kpi-val {
            color: #f8fafc;
        }

        .emp-kpi-subtext {
            font-size: 0.67rem;
            color: #64748b;
        }

        .dark .emp-kpi-subtext {
            color: #94a3b8;
        }

        .emp-kpi-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.08rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.64rem;
            font-weight: 750;
            width: fit-content;
            margin-top: 0.1rem;
        }

        .emp-kpi-badge.amber {
            background: #fef3c7;
            color: #b45309;
        }

        .dark .emp-kpi-badge.amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .emp-kpi-badge.green {
            background: #dcfce7;
            color: #15803d;
        }

        .dark .emp-kpi-badge.green {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        @media (max-width: 640px) {
            .emp-ticket-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="emp-ticket-page employee-blue-page">
        {{-- KPI Cards --}}
        <div class="emp-ticket-kpis">
            <div class="emp-ticket-kpi primary">
                <div class="emp-kpi-icon">
                    <x-filament::icon icon="heroicon-m-ticket" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="emp-kpi-body">
                    <span class="emp-kpi-label">TOTAL TICKETS</span>
                    <div class="emp-kpi-val text-blue-700 dark:text-blue-300">{{ $stats['total'] }}</div>
                    <span class="emp-kpi-subtext">All submitted tickets</span>
                </div>
            </div>

            <div class="emp-ticket-kpi">
                <div class="emp-kpi-icon">
                    <x-filament::icon icon="heroicon-m-clock" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="emp-kpi-body">
                    <span class="emp-kpi-label">PENDING REVIEW</span>
                    <div class="emp-kpi-val {{ $stats['pending'] > 0 ? 'text-amber-700 dark:text-amber-300' : '' }}">
                        {{ $stats['pending'] }}
                    </div>
                    <span class="emp-kpi-badge amber">Max 3 allowed ({{ $stats['pending'] }}/3)</span>
                </div>
            </div>

            <div class="emp-ticket-kpi">
                <div class="emp-kpi-icon">
                    <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="emp-kpi-body">
                    <span class="emp-kpi-label">RESOLVED TICKETS</span>
                    <div class="emp-kpi-val text-emerald-700 dark:text-emerald-300">{{ $stats['done'] }}</div>
                    <span class="emp-kpi-badge green">Completed</span>
                </div>
            </div>

            <div class="emp-ticket-kpi">
                <div class="emp-kpi-icon">
                    <x-filament::icon icon="heroicon-m-chat-bubble-left-ellipsis" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="emp-kpi-body">
                    <span class="emp-kpi-label">HR RESPONSES</span>
                    <div class="emp-kpi-val text-indigo-700 dark:text-indigo-300">{{ $stats['with_comments'] }}</div>
                    <span class="emp-kpi-subtext">Tickets with feedback</span>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
