<x-filament-panels::page>
    <style>
        .master-data-page { width: 100%; min-width: 0; }
        .master-data-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem; width: 100%; padding: 1.25rem 1.5rem; border: 1px solid #3b82f6; border-radius: .75rem; background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50); color: #fff; }
        .master-data-header-copy { min-width: 0; }
        .master-data-eyebrow { color: #d9f722; font-size: .7rem; font-weight: 800; }
        .master-data-title { margin: .25rem 0 0; font-size: 1.65rem; line-height: 1.2; font-weight: 800; color: #fff; }
        .master-data-subtitle { margin: .35rem 0 0; font-size: .9rem; color: #e0ecff; }
        .master-data-header-controls { display: flex; flex-direction: row; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: .75rem; }
        .master-data-header-controls .fi-tabs { background: rgb(10 31 68 / .6); border-color: rgb(147 197 253 / .35); }
        .master-data-page .fi-ta-ctn { max-width: 100%; overflow-x: auto; border-radius: .5rem; }
        .master-data-page .fi-ta table { width: 100%; min-width: 1040px; }
        .master-data-page.master-data-branches .fi-ta table { min-width: 620px; }
        .master-data-page .fi-ta-header-cell { padding: .75rem .8rem !important; white-space: nowrap; }
        .master-data-page .fi-ta-cell { padding: .35rem .8rem !important; vertical-align: middle; }
        .master-data-page .fi-ta-text { min-height: 2rem; }
        .master-data-page .fi-ta-text-item { font-size: .84rem; line-height: 1.4; }
        .master-data-page .fi-ta-image img { width: 2.25rem; height: 2.25rem; }
        .master-data-page .fi-ta-actions { white-space: nowrap; }
        .master-data-page .fi-ta-table tbody tr:hover { background: rgb(59 130 246 / .12); }
        .master-data-page .fi-ta-table tbody tr.employee-employment-ended > td { background: #fee2e2 !important; color: #7f1d1d; }
        .master-data-page .fi-ta-table tbody tr.employee-employment-ended:hover > td { background: #fecaca !important; }
        .dark .master-data-page .fi-ta-table tbody tr.employee-employment-ended > td { background: rgb(127 29 29 / .55) !important; color: #fee2e2; }
        .dark .master-data-page .fi-ta-table tbody tr.employee-employment-ended:hover > td { background: rgb(153 27 27 / .72) !important; }
        @media (max-width: 640px) {
            .master-data-toolbar { align-items: stretch; padding: 1rem; }
            .master-data-header-controls { width: 100%; align-items: flex-start; }
            .master-data-title { font-size: 1.35rem; }
            .master-data-page .fi-ta table { min-width: 980px; }
        }
    </style>

    <div @class(['master-data-page', 'master-data-branches' => ! $branch && $viewMode === 'branches'])>
        @if ($branch)
            @livewire(\App\Filament\Widgets\EmployeeDetailsTable::class, ['branchId' => $branchId])
        @elseif ($viewMode === 'all')
            @livewire(\App\Filament\Widgets\EmployeeDetailsTable::class, [], key('masterdata-all-employees'))
        @else
            @livewire(\App\Filament\Widgets\EmployeeManagementBranchTable::class, ['context' => 'records'])
        @endif
    </div>
</x-filament-panels::page>
