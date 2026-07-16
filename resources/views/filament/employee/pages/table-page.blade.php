<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    <div class="employee-blue-page">
        {{ $this->content }}
    </div>
</x-filament-panels::page>
