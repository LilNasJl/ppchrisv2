@php
    $employee = $record->employee;
    $initials = collect([$employee?->firstname, $employee?->lastname])
        ->filter()
        ->map(fn (string $name) => strtoupper(substr($name, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<div style="display: grid; place-items: center; gap: 10px; padding: 8px 0 18px;">
    @if ($record->profile_photo_url)
        <img
            src="{{ $record->profile_photo_url }}"
            alt="{{ $record->name }}"
            style="width: 118px; height: 118px; border-radius: 999px; object-fit: cover; border: 2px solid rgba(96, 165, 250, .7);"
        >
    @else
        <div style="width: 118px; height: 118px; border-radius: 999px; display: grid; place-items: center; background: rgba(37, 99, 235, .18); color: #60a5fa; font-size: 32px; font-weight: 800;">
            {{ $initials ?: 'NA' }}
        </div>
    @endif

    <div style="text-align: center;">
        <div style="font-weight: 800;">{{ $record->name }}</div>
    </div>
</div>
