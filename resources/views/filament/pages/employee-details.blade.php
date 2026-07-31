<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    @if (! $branch)
        <x-filament::tabs label="Masterdata view">
            <x-filament::tabs.item
                :active="$viewMode === 'all'"
                wire:click="$set('viewMode', 'all')"
            >
                All Employees
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$viewMode === 'branches'"
                wire:click="$set('viewMode', 'branches')"
            >
                By Branch
            </x-filament::tabs.item>
        </x-filament::tabs>
    @endif

    @if ($branch)
        <x-filament::section compact>
            <div class="flex items-center gap-3">
                <x-filament::icon
                    icon="heroicon-o-building-office-2"
                    class="h-5 w-5 text-primary-600 dark:text-primary-400"
                />

                <div>
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $branch->branch_name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Employee accounts and records assigned to this branch
                    </p>
                </div>
            </div>
        </x-filament::section>

        @livewire(\App\Filament\Widgets\EmployeeDetailsTable::class, ['branchId' => $branchId])
    @elseif ($viewMode === 'all')
        @livewire(\App\Filament\Widgets\EmployeeDetailsTable::class, [], key('masterdata-all-employees'))
    @else
        @livewire(\App\Filament\Widgets\EmployeeManagementBranchTable::class, ['context' => 'records'])
    @endif
</x-filament-panels::page>
