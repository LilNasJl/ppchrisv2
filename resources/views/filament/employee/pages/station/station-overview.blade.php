<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "STATION MANAGEMENT";
        }

        .station-overview-content {
            --station-surface: #fff;
            --station-muted-surface: #f8fafc;
            --station-border: #dbe5f1;
            --station-text: #0f172a;
            --station-muted: #64748b;
            --station-blue: #2563eb;
            --station-blue-soft: #eff6ff;
            display: grid;
            gap: .85rem;
            min-width: 0;
        }

        .dark .station-overview-content {
            --station-surface: #0f172a;
            --station-muted-surface: #111827;
            --station-border: rgba(148, 163, 184, .24);
            --station-text: #f8fafc;
            --station-muted: #94a3b8;
            --station-blue: #60a5fa;
            --station-blue-soft: rgba(37, 99, 235, .16);
        }

        .station-stat-grid {
            display: grid;
            gap: .85rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }

        .station-stat-card {
            align-content: start;
            background: var(--station-surface);
            border: 1px solid var(--station-border);
            border-radius: 8px;
            display: grid;
            gap: .7rem;
            min-width: 0;
            padding: 1rem 1.1rem;
        }

        .station-stat-top {
            align-items: center;
            display: flex;
            gap: .75rem;
            justify-content: space-between;
        }

        .station-stat-label {
            color: var(--station-muted);
            font-size: .75rem;
            font-weight: 750;
            line-height: 1.25;
            margin: 0;
            text-transform: uppercase;
        }

        .station-stat-value {
            color: var(--station-text);
            font-size: 1.65rem;
            font-weight: 850;
            line-height: 1;
            margin: .4rem 0 0;
        }

        .station-stat-icon {
            align-items: center;
            background: var(--station-blue-soft);
            border: 1px solid var(--station-border);
            border-radius: 8px;
            color: var(--station-blue);
            display: inline-flex;
            flex: 0 0 38px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .station-stat-icon svg {
            height: 19px;
            width: 19px;
        }

        .station-stat-copy {
            color: var(--station-muted);
            font-size: .8rem;
            line-height: 1.4;
            margin: 0;
        }

        @media (max-width: 640px) {
            .station-stat-grid {
                grid-template-columns: 1fr;
            }

            .station-stat-card {
                padding: .85rem .95rem;
            }
        }
    </style>

    <div class="station-overview-content">
        <div class="station-stat-grid" aria-label="Station management summary">
            <section class="station-stat-card">
                <div class="station-stat-top">
                    <div>
                        <p class="station-stat-label">Assigned Stations</p>
                        <p class="station-stat-value">{{ number_format($this->branchCount()) }}</p>
                    </div>
                    <span class="station-stat-icon" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-building-storefront" />
                    </span>
                </div>
                <p class="station-stat-copy">Station branches connected to your management account.</p>
            </section>

            <section class="station-stat-card">
                <div class="station-stat-top">
                    <div>
                        <p class="station-stat-label">Station Employees</p>
                        <p class="station-stat-value">{{ number_format($this->employeeCount()) }}</p>
                    </div>
                    <span class="station-stat-icon" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-users" />
                    </span>
                </div>
                <p class="station-stat-copy">Active employees under your managed station branches.</p>
            </section>
        </div>
    </div>
</x-filament-panels::page>
