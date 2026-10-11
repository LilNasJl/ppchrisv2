<x-filament-panels::page class="branch-page">
    @include('filament.pages.partials.system-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "ORGANIZATIONAL SETUP";
        }

        .branch-page .fi-ta-cell {
            padding-block: 0.25rem !important;
        }

        .branch-page .fi-ta-text {
            min-height: 2.4rem !important;
            padding: 0.52rem 0.7rem !important;
        }
    </style>
    {{ $this->content }}
</x-filament-panels::page>
