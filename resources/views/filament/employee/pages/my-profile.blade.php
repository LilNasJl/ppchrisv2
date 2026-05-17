<x-filament-panels::page>
    <form wire:submit.prevent="save" style="display: grid; gap: 16px; width: min(100%, 1180px);">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                Save Profile
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
