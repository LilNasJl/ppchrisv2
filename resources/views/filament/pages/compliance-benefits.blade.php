<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

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
                        Employee deductions assigned to this branch
                    </p>
                </div>
            </div>
        </x-filament::section>

        @livewire(\App\Filament\Widgets\ComplianceBenefitsEmployeeTable::class, ['branchId' => $branchId])
    @else
        @livewire(\App\Filament\Widgets\EmployeeManagementBranchTable::class, ['context' => 'deductions'])
    @endif
</x-filament-panels::page>
