<x-filament-panels::page>
    @include('filament.pages.partials.system-gradient-page-header-styles')
    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "HOLIDAY TYPES";
        }
    </style>
    {{ $this->content }}
</x-filament-panels::page>
