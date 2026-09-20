<x-filament-panels::page>
    <style>
        .station-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
            width: 100%;
        }

        .station-stat-card {
            border: 1px solid rgb(191 219 254 / 0.85);
            border-radius: 0.75rem;
            background: rgb(255, 255, 255);
            padding: 1.5rem;
            box-shadow: 0 8px 20px rgb(15 71 148 / 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .station-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgb(15 71 148 / 0.12);
        }

        .dark .station-stat-card {
            border-color: rgb(59 130 246 / 0.28);
            background: rgb(17, 24, 39);
        }

        .station-stat-label {
            color: rgb(71, 85, 105);
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dark .station-stat-label,
        .dark .station-stat-copy {
            color: rgb(203, 213, 225);
        }

        .station-stat-value {
            margin-top: 0.75rem;
            color: rgb(37, 99, 235);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
            line-height: 1;
        }

        .station-stat-copy {
            margin-top: 0.65rem;
            color: rgb(100, 116, 139);
            font-size: 0.9rem;
        }

        @media (max-width: 640px) {
            .station-stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="station-stat-grid">
        <section class="station-stat-card">
            <p class="station-stat-label">Assigned Stations</p>
            <p class="station-stat-value">{{ number_format($this->branchCount()) }}</p>
            <p class="station-stat-copy">Station branches connected to your management account.</p>
        </section>

        <section class="station-stat-card">
            <p class="station-stat-label">Station Employees</p>
            <p class="station-stat-value">{{ number_format($this->employeeCount()) }}</p>
            <p class="station-stat-copy">Active employees under your managed station branches.</p>
        </section>
    </div>
</x-filament-panels::page>
