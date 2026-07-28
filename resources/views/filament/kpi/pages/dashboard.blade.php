<x-filament-panels::page>
    <style>
        .kpi-dashboard-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .kpi-dashboard-card,
        .kpi-coverage {
            background: var(--color-white, #fff);
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 71, 148, .08);
        }

        .kpi-dashboard-card {
            min-height: 132px;
            padding: 20px;
        }

        .kpi-dashboard-card.is-blue {
            background: #eff6ff;
        }

        .kpi-card-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            margin: 0;
        }

        .kpi-dashboard-card.is-blue .kpi-card-label,
        .kpi-coverage-kicker {
            color: #1d4ed8;
        }

        .kpi-card-value {
            color: #0f172a;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.15;
            margin: 10px 0 0;
            overflow-wrap: anywhere;
        }

        .kpi-card-value.is-blue {
            color: #1d4ed8;
        }

        .kpi-card-value.is-warning {
            color: #d97706;
        }

        .kpi-coverage {
            margin-top: 22px;
            overflow: hidden;
        }

        .kpi-coverage-header {
            border-bottom: 1px solid #dbeafe;
            padding: 18px 20px;
        }

        .kpi-coverage-title {
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
            margin: 0;
        }

        .kpi-coverage-copy {
            color: #64748b;
            font-size: 13px;
            line-height: 1.55;
            margin: 5px 0 0;
        }

        .kpi-coverage-body {
            align-items: center;
            display: grid;
            gap: 20px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 20px;
        }

        .kpi-coverage-kicker {
            font-size: 13px;
            font-weight: 800;
            margin: 0;
        }

        .kpi-coverage-list {
            color: #334155;
            line-height: 1.6;
            margin: 7px 0 0;
        }

        .kpi-target-count {
            background: #eff6ff;
            border-radius: 8px;
            min-width: 150px;
            padding: 16px 20px;
            text-align: center;
        }

        .kpi-target-count span {
            color: #1d4ed8;
            display: block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .kpi-target-count strong {
            color: #0f172a;
            display: block;
            font-size: 30px;
            margin-top: 3px;
        }

        .dark .kpi-dashboard-card,
        .dark .kpi-coverage {
            background: #111827;
            border-color: rgba(30, 64, 175, .72);
        }

        .dark .kpi-dashboard-card.is-blue,
        .dark .kpi-target-count {
            background: rgba(30, 64, 175, .18);
        }

        .dark .kpi-card-value,
        .dark .kpi-coverage-title,
        .dark .kpi-target-count strong {
            color: #f8fafc;
        }

        .dark .kpi-card-label,
        .dark .kpi-coverage-copy,
        .dark .kpi-coverage-list {
            color: #cbd5e1;
        }

        .dark .kpi-coverage-header {
            border-color: rgba(30, 64, 175, .55);
        }

        @media (max-width: 1024px) {
            .kpi-dashboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .kpi-dashboard-grid {
                grid-template-columns: 1fr;
            }

            .kpi-coverage-body {
                align-items: stretch;
                grid-template-columns: 1fr;
            }

            .kpi-target-count {
                min-width: 0;
            }
        }
    </style>

    <div class="kpi-dashboard-grid">
        <section class="kpi-dashboard-card">
            <p class="kpi-card-label">Signed in as</p>
            <p class="kpi-card-value is-blue">{{ $account->username }}</p>
        </section>

        <section class="kpi-dashboard-card is-blue">
            <p class="kpi-card-label">Account Scope</p>
            <p class="kpi-card-value">{{ $account->scope_label }}</p>
        </section>

        <section class="kpi-dashboard-card">
            <p class="kpi-card-label">KPI Rating Cycles</p>
            <p class="kpi-card-value is-blue">{{ number_format($cycleCount) }}</p>
        </section>

        <section class="kpi-dashboard-card">
            <p class="kpi-card-label">Pending Targets</p>
            <p class="kpi-card-value is-warning">{{ number_format($pendingCount) }}</p>
        </section>
    </div>

    <section class="kpi-coverage">
        <header class="kpi-coverage-header">
            <h2 class="kpi-coverage-title">Assigned Rating Coverage</h2>
            <p class="kpi-coverage-copy">
                Branch accounts rate each assigned branch as one target. Department and employee accounts rate individual employees.
            </p>
        </header>

        <div class="kpi-coverage-body">
            <div>
                <p class="kpi-coverage-kicker">{{ $account->scope_label }}</p>
                <p class="kpi-coverage-list">{{ $account->scope_summary }}</p>
            </div>
            <div class="kpi-target-count">
                <span>Captured Targets</span>
                <strong>{{ number_format($targetCount) }}</strong>
            </div>
        </div>
    </section>
</x-filament-panels::page>
