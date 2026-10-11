<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .loan-management-page {
            display: grid;
            gap: 1rem;
            min-width: 0;
        }

        .loan-management-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.1rem 1.4rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #fff;
        }

        .loan-management-header-copy {
            display: flex;
            min-width: 12rem;
            flex-direction: column;
            gap: 0.25rem;
        }

        .loan-management-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .loan-management-title {
            margin: 0;
            color: #fff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .loan-management-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .loan-management-header-actions {
            flex: 1 1 28rem;
            min-width: 0;
        }

        .loan-management-header-actions > div {
            justify-content: flex-end;
        }

        .loan-management-header-actions .fi-btn {
            min-height: 2.3rem;
            padding-inline: 0.75rem;
            border-radius: 0.45rem;
            font-size: 0.76rem;
            font-weight: 700;
        }

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
            min-width: 1050px;
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

        @media (max-width: 700px) {
            .loan-management-header {
                align-items: stretch;
                padding: 1rem;
            }

            .loan-management-header-actions {
                flex-basis: 100%;
            }

            .loan-management-header-actions > div {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="loan-management-page">
        <header class="loan-management-header" aria-labelledby="loan-management-title">
            <div class="loan-management-header-copy">
                <p class="loan-management-eyebrow">COMPENSATION &amp; BENEFITS</p>
                <h1 class="loan-management-title" id="loan-management-title">Loan Management</h1>
                <p class="loan-management-subtitle">Manage employee loans, requests, and amortization.</p>
            </div>
            <div class="loan-management-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

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
                        Create Loan
                    </x-filament::button>
                </div>
            </form>
        @endif
    </div>
</x-filament-panels::page>
