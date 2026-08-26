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

    <form wire:submit="save" class="employee-details-page-shell">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button
                type="submit"
                icon="heroicon-o-check"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                Save Changes
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
