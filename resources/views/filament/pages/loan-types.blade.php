<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')
    <style>
        .fi-page-header-main-ctn > header.fi-header, .fi-breadcrumbs { display: none !important; }
        .loan-types-page { display: grid; gap: 1rem; min-width: 0; }
        .loan-types-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.4rem; border: 1px solid #3b82f6; border-radius: .75rem; background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50); color: #fff; }
        .loan-types-header h1 { margin: .2rem 0; font-size: 1.55rem; font-weight: 800; }
        .loan-types-header p { margin: 0; color: #e0ecff; font-size: .88rem; }
        .loan-types-actions { min-width: 0; }
        .loan-types-table { overflow-x: auto; border-radius: .65rem; }
    </style>
    <div class="loan-types-page">
        <header class="loan-types-header">
            <div>
                <p>COMPENSATION &amp; BENEFITS</p>
                <h1>Loan Types</h1>
                <p>Manage the choices available when creating employee loans.</p>
            </div>
            <div class="loan-types-actions"><x-filament::actions :actions="$this->getCachedHeaderActions()" /></div>
        </header>
        <div class="loan-types-table">{{ $this->table }}</div>
    </div>
</x-filament-panels::page>
