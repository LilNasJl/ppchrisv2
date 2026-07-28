<x-filament-panels::page>
    <style>
        .kpi-config-card {
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 71, 148, .08);
            overflow: hidden;
        }

        .kpi-config-header {
            align-items: center;
            background: #eff6ff;
            border-bottom: 1px solid #dbeafe;
            display: flex;
            gap: 12px;
            padding: 20px 24px;
        }

        .kpi-config-icon {
            align-items: center;
            background: #1d4ed8;
            border-radius: 8px;
            color: #fff;
            display: flex;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .kpi-config-icon svg {
            height: 21px;
            width: 21px;
        }

        .kpi-config-title {
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
            margin: 0;
        }

        .kpi-config-copy,
        .kpi-config-empty p {
            color: #64748b;
            font-size: 13px;
            line-height: 1.55;
            margin: 5px 0 0;
        }

        .kpi-config-body {
            padding: 24px;
        }

        .kpi-config-empty {
            background: rgba(239, 246, 255, .7);
            border: 1px dashed #93c5fd;
            border-radius: 8px;
            padding: 34px;
            text-align: center;
        }

        .kpi-config-empty strong {
            color: #172554;
        }

        .dark .kpi-config-card {
            background: #111827;
            border-color: rgba(30, 64, 175, .72);
        }

        .dark .kpi-config-header,
        .dark .kpi-config-empty {
            background: rgba(30, 64, 175, .18);
            border-color: rgba(30, 64, 175, .72);
        }

        .dark .kpi-config-title,
        .dark .kpi-config-empty strong {
            color: #f8fafc;
        }

        .dark .kpi-config-copy,
        .dark .kpi-config-empty p {
            color: #cbd5e1;
        }
    </style>

    <section class="kpi-config-card">
        <div class="kpi-config-header">
            <div class="kpi-config-icon">
                <x-filament::icon icon="heroicon-m-adjustments-horizontal" />
            </div>
            <div>
                <h2 class="kpi-config-title">KPI Criteria Configuration</h2>
                <p class="kpi-config-copy">Account scopes and rating rosters are ready for the scoring phase.</p>
            </div>
        </div>

        <div class="kpi-config-body">
            <div class="kpi-config-empty">
                <strong>Scoring configuration is intentionally not active yet.</strong>
                <p>
                    The next phase can define KPI categories, indicators, weights, rating scales, and approval rules without changing the account or rating-cycle connections added now.
                </p>
            </div>
        </div>
    </section>
</x-filament-panels::page>
