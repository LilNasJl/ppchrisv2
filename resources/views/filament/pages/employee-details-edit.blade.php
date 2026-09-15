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

        .employee-details-save-actions {
            align-items: center;
            border-top: 1px solid rgba(148, 163, 184, .25);
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.25rem;
        }

        .employee-details-save-actions button {
            min-width: 10rem;
        }

        @media (max-width: 640px) {
            .employee-details-save-actions button {
                width: 100%;
            }
        }
    </style>

    <form wire:submit="save" class="employee-details-page-shell">
        {{ $this->form }}

        <div class="employee-details-save-actions">
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
