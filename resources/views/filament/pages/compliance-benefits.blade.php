<x-filament-panels::page>
    <style>
        .compliance-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }

        .compliance-card {
            display: grid;
            gap: 10px;
            min-height: 150px;
            padding: 22px;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 8px;
            background: rgba(255, 255, 255, .72);
            color: inherit;
            text-decoration: none;
            transition: border-color .16s ease, transform .16s ease, box-shadow .16s ease;
        }

        .dark .compliance-card {
            background: rgba(15, 23, 42, .35);
        }

        .compliance-card:hover {
            border-color: rgb(59, 130, 246);
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .10);
        }

        .compliance-card__icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(59, 130, 246, .12);
            color: rgb(37, 99, 235);
        }

        .compliance-card__title {
            font-size: 18px;
            font-weight: 800;
        }

        .compliance-card__copy {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }

        .dark .compliance-card__copy {
            color: #cbd5e1;
        }
    </style>

    <div class="compliance-card-grid">
        <a class="compliance-card" href="{{ \App\Filament\Pages\DeductionsManagement::getUrl() }}">
            <div class="compliance-card__icon">
                <x-filament::icon icon="heroicon-m-minus-circle" style="width:24px;height:24px;" />
            </div>
            <div>
                <div class="compliance-card__title">Deductions Management</div>
                <div class="compliance-card__copy">
                    View employees, manage their linked deductions, and maintain deduction types.
                </div>
            </div>
        </a>

        <a class="compliance-card" href="{{ \App\Filament\Pages\LoanManagement::getUrl() }}">
            <div class="compliance-card__icon">
                <x-filament::icon icon="heroicon-m-banknotes" style="width:24px;height:24px;" />
            </div>
            <div>
                <div class="compliance-card__title">Loan Management</div>
                <div class="compliance-card__copy">
                    Create employee loans, set terms, and monitor loan amount, payment, and balance.
                </div>
            </div>
        </a>
    </div>
</x-filament-panels::page>
