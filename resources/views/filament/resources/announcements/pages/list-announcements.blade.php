<x-filament-panels::page>
    @include('filament.pages.partials.system-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "COMPANY MEMORANDUM";
        }
    </style>
    {{ $this->content }}
</x-filament-panels::page>
