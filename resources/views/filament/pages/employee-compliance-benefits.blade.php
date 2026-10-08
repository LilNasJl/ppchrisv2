<x-filament-panels::page>
    @include('filament.pages.partials.compliance-page-chrome')

    @php
        $emp = $this->employee;
    @endphp

    <div class="cb-workspace">
        <header class="cb-topbar">
            <div class="cb-copy">
                <h2 class="cb-title">{{ $emp?->full_name ?? 'Employee Deductions' }}</h2>
                <div class="cb-meta-row">
                    <span class="cb-chip">
                        <x-filament::icon icon="heroicon-m-identification" class="cb-btn-icon" />
                        {{ $emp?->company_id ?? $emp?->uid ?? 'N/A' }}
                    </span>
                    <span class="cb-chip violet">
                        <x-filament::icon icon="heroicon-m-briefcase" class="cb-btn-icon" />
                        {{ $emp?->designation?->title ?? 'No designation' }}
                    </span>
                    <span class="cb-chip">
                        <x-filament::icon icon="heroicon-m-building-storefront" class="cb-btn-icon" />
                        {{ $emp?->branch?->branch_name ?? 'No branch' }}
                    </span>
                </div>
            </div>
            <div class="cb-actions">
                <a href="{{ $this->returnLink() }}" class="cb-btn outline">
                    <x-filament::icon icon="heroicon-m-arrow-left" class="cb-btn-icon" />
                    <span>Return</span>
                </a>
                <button type="button" wire:click="save" wire:loading.attr="disabled" class="cb-btn success">
                    <x-filament::icon icon="heroicon-m-check" class="cb-btn-icon" />
                    <span>Save Deductions</span>
                </button>
            </div>
        </header>

        <div class="cb-form">
            {{ $this->form }}
        </div>
    </div>
</x-filament-panels::page>
