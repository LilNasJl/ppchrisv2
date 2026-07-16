<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    <style>
        .employee-loan-page {
            --el-surface: #ffffff;
            --el-surface-muted: #f8fafc;
            --el-text: #0f172a;
            --el-muted: #64748b;
            --el-border: rgba(15, 23, 42, .12);
            --el-blue: #2563eb;
            --el-blue-soft: #eff6ff;

            color: var(--el-text);
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .dark .employee-loan-page {
            --el-surface: #0f172a;
            --el-surface-muted: #111827;
            --el-text: #f8fafc;
            --el-muted: #94a3b8;
            --el-border: rgba(148, 163, 184, .24);
            --el-blue: #60a5fa;
            --el-blue-soft: rgba(37, 99, 235, .16);
        }

        .employee-loan-nav {
            align-items: center;
            background: var(--el-surface);
            border: 1px solid var(--el-border);
            border-top: 3px solid var(--el-blue);
            border-radius: 8px;
            display: flex;
            gap: 6px;
            padding: 10px;
        }

        .employee-loan-tab {
            align-items: center;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 6px;
            color: var(--el-muted);
            cursor: pointer;
            display: inline-flex;
            font-size: 13px;
            font-weight: 750;
            gap: 7px;
            justify-content: center;
            min-height: 38px;
            padding: 8px 13px;
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
        }

        .employee-loan-tab svg {
            height: 18px;
            width: 18px;
        }

        .employee-loan-tab:hover,
        .employee-loan-tab:focus-visible {
            background: var(--el-blue-soft);
            border-color: var(--el-border);
            color: var(--el-blue);
            outline: none;
        }

        .employee-loan-tab.is-active {
            background: var(--el-blue-soft);
            border-color: var(--el-blue);
            color: var(--el-blue);
        }

        .employee-loan-content {
            min-width: 0;
        }

        .employee-loan-page .fi-ta,
        .employee-loan-page .fi-ta-ctn {
            max-width: 100%;
            min-width: 0;
        }

        .employee-loan-page .fi-ta-ctn {
            overflow-x: auto;
        }

        @media (max-width: 640px) {
            .employee-loan-nav {
                align-items: stretch;
                flex-direction: column;
            }

            .employee-loan-tab {
                justify-content: flex-start;
                width: 100%;
            }
        }
    </style>

    <div class="employee-loan-page employee-blue-page">
        <nav class="employee-loan-nav" aria-label="Employee loan records">
            <button
                type="button"
                class="employee-loan-tab {{ $activeLoanSection === 'loans' ? 'is-active' : '' }}"
                wire:click="showLoanSection('loans')"
                wire:loading.attr="disabled"
                wire:target="showLoanSection"
                aria-selected="{{ $activeLoanSection === 'loans' ? 'true' : 'false' }}"
            >
                <x-filament::icon icon="heroicon-m-banknotes" />
                <span>My Loans</span>
            </button>

            <button
                type="button"
                class="employee-loan-tab {{ $activeLoanSection === 'requests' ? 'is-active' : '' }}"
                wire:click="showLoanSection('requests')"
                wire:loading.attr="disabled"
                wire:target="showLoanSection"
                aria-selected="{{ $activeLoanSection === 'requests' ? 'true' : 'false' }}"
            >
                <x-filament::icon icon="heroicon-m-clock" />
                <span>Loan Request History</span>
            </button>
        </nav>

        <section class="employee-loan-content">
            @if ($activeLoanSection === 'loans')
                <div wire:key="employee-my-loans">
                    {{ $this->table }}
                </div>
            @else
                <div wire:key="employee-loan-request-history">
                    <livewire:employee-loan-request-history-table />
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
