<x-filament-panels::page>
    <style>
        .loan-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .loan-tab {
            border: 1px solid rgba(148, 163, 184, .32);
            border-radius: 8px;
            padding: 9px 14px;
            font-weight: 700;
            color: inherit;
            background: rgba(255, 255, 255, .68);
        }

        .dark .loan-tab {
            background: rgba(15, 23, 42, .38);
        }

        .loan-tab[aria-selected="true"] {
            border-color: rgb(37, 99, 235);
            background: rgb(37, 99, 235);
            color: #fff;
        }

        .loan-form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }
    </style>

    <div class="loan-tabs" role="tablist" aria-label="Loan management tabs">
        <button
            type="button"
            class="loan-tab"
            aria-selected="{{ $activeLoanTab === 'list' ? 'true' : 'false' }}"
            wire:click="showLoanTab('list')"
        >
            Loan List
        </button>

        <button
            type="button"
            class="loan-tab"
            aria-selected="{{ $activeLoanTab === 'information' ? 'true' : 'false' }}"
            wire:click="showLoanTab('information')"
        >
            Loan Information
        </button>
    </div>

    @if ($activeLoanTab === 'list')
        {{ $this->table }}
    @else
        <form wire:submit.prevent="createLoan">
            {{ $this->form }}

            <div class="loan-form-actions">
                <x-filament::button type="submit" icon="heroicon-m-banknotes">
                    Generate Amortization
                </x-filament::button>
            </div>
        </form>
    @endif
</x-filament-panels::page>
