<x-filament-panels::page>
    <div class="grid gap-4">
        <x-filament::tabs label="Employee loan records">
            <x-filament::tabs.item
                :active="$activeLoanSection === 'loans'"
                icon="heroicon-m-banknotes"
                wire:click="showLoanSection('loans')"
            >
                My Loans
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeLoanSection === 'requests'"
                icon="heroicon-m-clock"
                wire:click="showLoanSection('requests')"
            >
                Loan Request History
            </x-filament::tabs.item>
        </x-filament::tabs>

        @if ($activeLoanSection === 'loans')
            <div wire:key="employee-my-loans">
                {{ $this->table }}
            </div>
        @else
            <div wire:key="employee-loan-request-history">
                <livewire:employee-loan-request-history-table />
            </div>
        @endif
    </div>
</x-filament-panels::page>
