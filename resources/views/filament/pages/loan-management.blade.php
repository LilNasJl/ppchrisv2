<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        .loan-management-table-wrap {
            width: 100%;
            min-width: 0;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            border-radius: 0.65rem;
        }

        .loan-management-table-wrap .fi-ta,
        .loan-management-table-wrap .fi-ta-ctn {
            width: 100%;
            min-width: 0;
        }

        .loan-management-table-wrap .fi-ta table {
            min-width: 1100px;
        }

        .loan-management-table-wrap .fi-ta-header-cell {
            padding-inline: 0.4rem !important;
        }

        .loan-management-table-wrap .fi-ta-cell {
            padding-block: 0.22rem !important;
        }

        .loan-management-table-wrap .fi-ta-text {
            min-height: 2.1rem !important;
            padding: 0.42rem 0.55rem !important;
        }

        .loan-management-table-wrap .fi-ta-cell .fi-ta-text-item {
            white-space: nowrap !important;
        }

        .loan-form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }
    </style>

    @if ($activeLoanTab === 'list')
        <div class="loan-management-table-wrap">
            {{ $this->table }}
        </div>
    @elseif ($activeLoanTab === 'requests')
        <div class="loan-management-table-wrap">
            <livewire:loan-request-management-table />
        </div>
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
