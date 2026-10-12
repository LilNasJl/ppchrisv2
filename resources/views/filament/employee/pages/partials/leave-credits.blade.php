<div class="leave-credits-modal">
    <style>
        .leave-credits-modal {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.65rem;
            padding: 0.25rem 0;
        }

        .credit-stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            padding: 0.8rem 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .dark .credit-stat-card {
            background: #0f172a;
            border-color: #334155;
        }

        .credit-stat-card.primary {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.04), rgba(59, 130, 246, 0.02));
            border-color: rgba(37, 99, 235, 0.28);
        }

        .dark .credit-stat-card.primary {
            background: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .credit-icon {
            padding: 0.45rem;
            border-radius: 0.45rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .credit-icon {
            background: #1e293b;
            border-color: #334155;
        }

        .credit-body {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }

        .credit-label {
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #64748b;
            text-transform: uppercase;
        }

        .dark .credit-label {
            color: #94a3b8;
        }

        .credit-value {
            font-size: 1.35rem;
            font-weight: 850;
            line-height: 1.2;
            color: #0f172a;
        }

        .dark .credit-value {
            color: #f8fafc;
        }

        .credit-sub {
            font-size: 0.68rem;
            color: #64748b;
        }

        .dark .credit-sub {
            color: #94a3b8;
        }
    </style>

    <div class="credit-stat-card primary">
        <div class="credit-icon">
            <x-filament::icon icon="heroicon-m-calendar-days" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
        </div>
        <div class="credit-body">
            <span class="credit-label">REGULAR LEAVE CREDITS</span>
            <div class="credit-value text-blue-700 dark:text-blue-300">
                {{ number_format((float) $employee->leave_credits, 2) }}
            </div>
            <span class="credit-sub">Vacation & Sick leave balance</span>
        </div>
    </div>

    <div class="credit-stat-card">
        <div class="credit-icon">
            <x-filament::icon icon="heroicon-m-cake" class="w-5 h-5 text-pink-600 dark:text-pink-400" />
        </div>
        <div class="credit-body">
            <span class="credit-label">BIRTHDAY LEAVE</span>
            <div class="credit-value text-pink-700 dark:text-pink-300">
                {{ number_format((float) $employee->birthday_leave_credits, 2) }}
            </div>
            <span class="credit-sub">Annual birthday leave credit</span>
        </div>
    </div>
</div>
