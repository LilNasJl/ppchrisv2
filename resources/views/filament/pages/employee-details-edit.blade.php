<x-filament-panels::page>
    <style>
        .fi-panel-hr .fi-page:has(.employee-details-page-shell),
        .fi-panel-hr .fi-page:has(.employee-details-page-shell) .fi-page-content,
        .fi-panel-hr .fi-page:has(.employee-details-page-shell) .fi-page-content > *,
        .fi-panel-hr .employee-details-page-shell,
        .fi-panel-hr .employee-details-page-shell > *,
        .fi-panel-hr .employee-details-page-shell form,
        .fi-panel-hr .employee-details-page-shell .fi-form,
        .fi-panel-hr .employee-details-page-shell .fi-sc,
        .fi-panel-hr .employee-details-page-shell .fi-schema,
        .fi-panel-hr .employee-details-page-shell .fi-tabs {
            max-width: none !important;
            min-width: 0;
            width: 100% !important;
        }

        .fi-panel-hr .employee-details-page-shell {
            display: block;
        }
    </style>

    <div class="employee-details-page-shell">
        {{ $this->form }}
    </div>
</x-filament-panels::page>
