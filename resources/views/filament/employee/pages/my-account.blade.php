<x-filament-panels::page>
    @php
        $user = auth()->user();
        $employee = $user?->employee;
        $displayName = $employee?->full_name ?? $user?->name ?? 'Employee';
        $initials = collect(explode(' ', str_replace(',', '', (string) $displayName)))
            ->filter()
            ->map(fn (string $part) => str($part)->substr(0, 1)->upper())
            ->take(2)
            ->implode('');
    @endphp

    <div style="display: grid; gap: 18px;">
        <section style="display: flex; align-items: center; gap: 16px; border: 1px solid rgba(148, 163, 184, .22); border-radius: 8px; padding: 18px;">
            @if ($user?->profile_photo_url)
                <img
                    src="{{ $user->profile_photo_url }}"
                    alt="{{ $displayName }}"
                    style="width: 86px; height: 86px; border-radius: 999px; object-fit: cover; border: 2px solid rgba(96, 165, 250, .7);"
                >
            @else
                <div style="width: 86px; height: 86px; border-radius: 999px; display: grid; place-items: center; background: rgba(37, 99, 235, .18); color: #60a5fa; font-size: 24px; font-weight: 800;">
                    {{ $initials ?: 'EP' }}
                </div>
            @endif

            <div>
                <div style="font-size: 13px; color: #94a3b8;">Profile Picture</div>
                <div style="font-size: 20px; font-weight: 800;">{{ $displayName }}</div>
            </div>
        </section>

        <form wire:submit.prevent="save" style="display: grid; gap: 16px;">
            {{ $this->form }}

            <div>
                <x-filament::button type="submit">
                    Save Account
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
