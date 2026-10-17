@php
    $selectedBranchCount = count($this->branch_ids);
    $selectedDeptCount = count($this->department_ids);
    $totalSelectedCount = $selectedBranchCount + $selectedDeptCount;
@endphp

<div class="summary-filter-modal-wrapper">
    <div class="summary-filter-btn-container">
        <label class="summary-filter-field-label">
            Branch &amp; Department Filters
        </label>
        <button
            type="button"
            x-on:click="$dispatch('open-modal', { id: 'payroll-summary-filter-modal' })"
            class="summary-filter-trigger-btn"
            title="Open branch and department selection modal"
            aria-haspopup="dialog"
        >
            <div class="summary-filter-trigger-content">
                <x-filament::icon icon="heroicon-m-adjustments-horizontal" class="summary-filter-trigger-icon" />
                <span class="summary-filter-trigger-text">
                    Select Branches &amp; Departments
                </span>
            </div>

            @if ($totalSelectedCount > 0)
                <span class="summary-filter-trigger-badge">
                    {{ $totalSelectedCount }} active
                </span>
            @else
                <span class="summary-filter-trigger-pill">
                    All Included
                </span>
            @endif
        </button>
    </div>

    <x-filament::modal
        id="payroll-summary-filter-modal"
        width="3xl"
        :close-button="true"
    >
        <x-slot name="heading">
            <div class="summary-modal-title-row">
                <x-filament::icon icon="heroicon-o-building-office-2" class="summary-modal-header-icon" />
                <span>Branch &amp; Department Selection</span>
            </div>
        </x-slot>

        <div class="summary-modal-content">
            @if ($this->selectedPeriod?->is_locked)
                <div class="summary-modal-locked-banner">
                    <x-filament::icon icon="heroicon-m-lock-closed" class="summary-modal-locked-icon" />
                    <span>This payroll period is locked. Summary settings are read-only and cannot be modified.</span>
                </div>
            @endif

            <div class="summary-modal-callout">
                <x-filament::icon icon="heroicon-m-information-circle" class="summary-modal-callout-icon" />
                <div class="summary-modal-callout-text">
                    <span class="summary-modal-callout-title">Department Prioritization Rule</span>
                    <p>
                        Employees matching both a selected branch and a selected department are assigned to the <strong>department</strong> grouping and excluded from branch counts to prevent duplicate calculations.
                    </p>
                </div>
            </div>

            <div class="summary-modal-fields">
                {{ $getChildSchema() }}
            </div>
        </div>

        <x-slot name="footer">
            <div class="summary-modal-footer">
                <div class="summary-modal-footer-actions">
                    <x-filament::button
                        color="gray"
                        wire:click="clearFilterSelection"
                        wire:loading.attr="disabled"
                        :disabled="$totalSelectedCount === 0 || (bool) $this->selectedPeriod?->is_locked"
                        icon="heroicon-m-x-circle"
                    >
                        Clear Section
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        x-on:click="$dispatch('close-modal', { id: 'payroll-summary-filter-modal' })"
                    >
                        Cancel
                    </x-filament::button>

                    <x-filament::button
                        color="primary"
                        wire:click="saveSummarySettings"
                        wire:loading.attr="disabled"
                        x-on:click="$dispatch('close-modal', { id: 'payroll-summary-filter-modal' })"
                        :disabled="(bool) ($this->selectedPeriod?->is_locked)"
                        icon="heroicon-m-check"
                    >
                        Save
                    </x-filament::button>
                </div>
            </div>
        </x-slot>
    </x-filament::modal>
</div>
