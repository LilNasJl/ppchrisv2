<x-filament-panels::page>
    <style>
        .sicrc-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            width: 100%;
        }

        .sicrc-stat-card {
            border: 1px solid rgb(191 219 254 / 0.85);
            border-radius: 0.5rem;
            background: rgb(255, 255, 255);
            padding: 1.5rem;
            box-shadow: 0 8px 20px rgb(15 71 148 / 0.08);
        }

        .dark .sicrc-stat-card {
            border-color: rgb(59 130 246 / 0.28);
            background: rgb(17, 24, 39);
        }

        .sicrc-stat-label {
            color: rgb(71, 85, 105);
            font-size: 0.85rem;
            font-weight: 700;
        }

        .dark .sicrc-stat-label,
        .dark .sicrc-stat-copy {
            color: rgb(203, 213, 225);
        }

        .sicrc-stat-value {
            margin-top: 0.75rem;
            color: rgb(37, 99, 235);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
            line-height: 1;
        }

        .sicrc-stat-copy {
            margin-top: 0.65rem;
            color: rgb(100, 116, 139);
            font-size: 0.9rem;
        }

        @media (max-width: 640px) {
            .sicrc-stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="sicrc-stat-grid">
        <section class="sicrc-stat-card">
            <p class="sicrc-stat-label">Branches</p>
            <p class="sicrc-stat-value">{{ number_format($this->branchCount()) }}</p>
            <p class="sicrc-stat-copy">Branches connected to your account.</p>
        </section>

        <section class="sicrc-stat-card">
            <p class="sicrc-stat-label">Employees</p>
            <p class="sicrc-stat-value">{{ number_format($this->employeeCount()) }}</p>
            <p class="sicrc-stat-copy">Active employees from your connected branches.</p>
        </section>
    </div>
</x-filament-panels::page>
