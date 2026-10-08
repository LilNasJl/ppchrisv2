<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.pages.partials.compliance-page-chrome')

    <div class="cb-workspace" x-data="{ tab: 'company' }">
        <header class="cb-topbar">
            <div class="cb-copy">
                <h2 class="cb-title">Manage Deductions</h2>
                <p class="cb-subtitle">Define company deductions, government remittances, and other deduction types used in payroll.</p>
            </div>
            <div class="cb-actions">
                <a href="{{ \App\Filament\Pages\ComplianceBenefits::getUrl() }}" class="cb-btn outline">
                    <x-filament::icon icon="heroicon-m-arrow-left" class="cb-btn-icon" />
                    <span>Return</span>
                </a>
            </div>
        </header>

        <nav class="cb-tabs" aria-label="Deduction categories">
            <button type="button" class="cb-tab" :class="{ 'active': tab === 'company' }" x-on:click="tab = 'company'">
                <x-filament::icon icon="heroicon-m-building-office-2" class="cb-btn-icon" /> Company Deductions
            </button>
            <button type="button" class="cb-tab" :class="{ 'active': tab === 'remittance' }" x-on:click="tab = 'remittance'">
                <x-filament::icon icon="heroicon-m-shield-check" class="cb-btn-icon" /> Remittances
            </button>
            <button type="button" class="cb-tab" :class="{ 'active': tab === 'other' }" x-on:click="tab = 'other'">
                <x-filament::icon icon="heroicon-m-minus-circle" class="cb-btn-icon" /> Other Deductions
            </button>
        </nav>

        <div x-show="tab === 'company'">
            @livewire(\App\Filament\Widgets\CompanyDeductionsTable::class, key('cb-company'))
        </div>
        <div x-show="tab === 'remittance'" x-cloak>
            @livewire(\App\Filament\Widgets\RemittanceDeductionsTable::class, key('cb-remittance'))
        </div>
        <div x-show="tab === 'other'" x-cloak>
            @livewire(\App\Filament\Widgets\OtherDeductionsTable::class, key('cb-other'))
        </div>
    </div>
</x-filament-panels::page>
