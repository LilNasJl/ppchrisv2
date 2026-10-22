<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')
    <style>
        .master-data-page { width: 100%; min-width: 0; }
        .master-data-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; width: 100%; padding: .85rem 1.15rem; border: 1px solid #3b82f6; border-radius: .75rem; background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50); color: #fff; }
        .master-data-header-copy { min-width: 0; }
        .master-data-eyebrow { color: #d9f722; font-size: .64rem; font-weight: 800; }
        .master-data-title { margin: .2rem 0 0; font-size: 1.4rem; line-height: 1.2; font-weight: 800; color: #fff; }
        .master-data-subtitle { margin: .25rem 0 0; font-size: .8rem; line-height: 1.35; color: #e0ecff; }
        .master-data-header-controls { display: flex; flex-direction: row; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: .55rem; }
        .master-data-header-controls .fi-tabs { background: rgb(10 31 68 / .6); border-color: rgb(147 197 253 / .35); }
        .master-data-header-controls .fi-tabs { gap: .2rem; padding: .25rem; }
        .master-data-header-controls .fi-tabs-item { gap: .4rem; padding: .45rem .65rem; font-size: .8rem; }
        .master-data-header-controls .fi-tabs-item > .fi-icon { width: 1rem; height: 1rem; }
        .master-data-header-controls .fi-btn { min-height: 2.25rem; gap: .4rem; padding: .45rem .7rem; font-size: .8rem; }
        .master-data-page .fi-ta,
        .master-data-page .fi-ta-ctn { max-width: 100%; min-width: 0; background: #08090d !important; border-color: #303949 !important; border-radius: .5rem; }
        .master-data-page .fi-ta-ctn { overflow-x: auto; }
        .master-data-page .fi-ta-header,
        .master-data-page .fi-ta-header-toolbar { background: #0b0d12 !important; }
        .master-data-page .fi-ta-header-toolbar { padding: .75rem 1rem !important; gap: .75rem !important; }
        .master-data-page .fi-ta-header-toolbar > div:last-child { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: .75rem; min-width: 0; margin-left: auto; }
        .master-data-page .fi-ta-search-field { width: clamp(13rem, 24vw, 20rem); max-width: 100%; }
        .master-data-page .fi-ta-filters-trigger-action-ctn { display: inline-flex; align-items: center; justify-content: center; min-width: 2.5rem; }
        .master-data-page .fi-ta table { width: 100%; min-width: 1040px; }
        .master-data-page.master-data-branches .fi-ta table { min-width: 620px; }
        .master-data-page .fi-ta-header-cell { padding: .75rem 1rem !important; white-space: nowrap; }
        .master-data-page .fi-ta-cell { padding: .4rem 1rem !important; vertical-align: middle; }
        .master-data-page .fi-ta-text { min-height: 2rem; }
        .master-data-page .fi-ta-text-item { font-size: .84rem; line-height: 1.4; }
        .master-data-page .fi-ta-image img { width: 2.25rem; height: 2.25rem; }
        .master-data-page .fi-ta-actions { white-space: nowrap; }

        .master-data-page .fi-ta-table tbody,
        .master-data-page .fi-ta table tbody,
        .master-data-page .fi-ta-table tbody tr,
        .master-data-page .fi-ta table tbody tr { background: #08090d !important; }
        .master-data-page .fi-ta-table tbody tr:nth-child(even),
        .master-data-page .fi-ta table tbody tr:nth-child(even) {
            background: #0d0f14 !important;
        }
        .master-data-page .fi-ta-table tbody tr:hover,
        .master-data-page .fi-ta table tbody tr:hover {
            background: #181d26 !important;
        }
        .master-data-page .fi-ta-table tbody tr.employee-employment-ended > td { background: #fee2e2 !important; color: #7f1d1d; }
        .master-data-page .fi-ta-table tbody tr.employee-employment-ended:hover > td { background: #fecaca !important; }
        .dark .master-data-page .fi-ta-table tbody tr.employee-employment-ended > td { background: rgb(127 29 29 / .55) !important; color: #fee2e2; }
        .dark .master-data-page .fi-ta-table tbody tr.employee-employment-ended:hover > td { background: rgb(153 27 27 / .72) !important; }
        @media (max-width: 640px) {
            .master-data-toolbar { align-items: stretch; gap: .75rem; padding: .8rem .9rem; }
            .master-data-header-controls { width: 100%; align-items: flex-start; }
            .master-data-title { font-size: 1.25rem; }
            .master-data-page .fi-ta-header-toolbar { padding: .75rem !important; }
            .master-data-page .fi-ta-header-toolbar > div:last-child { width: 100%; }
            .master-data-page .fi-ta-search-field { flex: 1 1 12rem; width: auto; }
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
