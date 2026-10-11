<x-filament-panels::page>
    @include('filament.employee.pages.partials.employee-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    @include('filament.employee.pages.partials.blue-table-page-styles')

    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "STATION MANAGEMENT";
        }

        .station-management-page {
            min-width: 0;
        }

        .station-management-page .fi-ta-ctn {
            overflow-x: auto;
            overscroll-behavior-inline: contain;
        }

        .station-management-page .fi-ta table {
            min-width: 720px;
        }

        @media (max-width: 640px) {
            .station-management-page .fi-ta-header-toolbar {
                align-items: stretch;
                flex-wrap: wrap;
            }

            .station-management-page .fi-ta-header-toolbar .fi-input-wrp {
                min-width: 0;
                width: 100%;
            }

            .station-management-page .fi-tabs {
                max-width: 100%;
            }
        }
    </style>

    <div class="employee-blue-page station-management-page">
        {{ $this->content }}
    </div>
</x-filament-panels::page>
