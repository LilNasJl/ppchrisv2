<div class="master-data-toolbar">
    <div class="master-data-header-copy">
        <div class="master-data-eyebrow">WORKFORCE RECORDS</div>
        <h1 class="master-data-title">{{ $branch ? $branch->branch_name : 'Master Data' }}</h1>
        <p class="master-data-subtitle">
            {{ $branch ? 'Employee accounts assigned to this branch.' : 'Employee profiles, assignments, and account records.' }}
        </p>
    </div>

    <div class="master-data-header-controls">
        @if (! $branch)
            <x-filament::tabs label="Master Data view" class="master-data-tabs">
                <x-filament::tabs.item :active="$viewMode === 'all'" wire:click="$set('viewMode', 'all')" icon="heroicon-o-users">
                    All Employees
                </x-filament::tabs.item>
                <x-filament::tabs.item :active="$viewMode === 'branches'" wire:click="$set('viewMode', 'branches')" icon="heroicon-o-building-office-2">
                    By Branch
                </x-filament::tabs.item>
            </x-filament::tabs>
        @endif
        <x-filament::actions :actions="$headerActions" />
    </div>
</div>
