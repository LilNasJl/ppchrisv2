<x-filament-panels::page>
    <form wire:submit.prevent="save" class="employee-profile-form">
        {{ $this->form }}

        <div class="employee-profile-actions">
            <x-filament::button type="submit" icon="heroicon-m-check">
                Save Profile
            </x-filament::button>
        </div>
    </form>

    <style>
        .employee-profile-form {
            display: grid;
            gap: 18px;
            width: min(100%, 1320px);
        }

        .employee-profile-actions {
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 640px) {
            .employee-profile-actions {
                justify-content: stretch;
            }

            .employee-profile-actions .fi-btn {
                width: 100%;
            }
        }
    </style>
</x-filament-panels::page>
