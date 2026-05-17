<div style="display: grid; gap: 10px;">
    @forelse ($holidays as $holiday)
        <div style="display: flex; justify-content: space-between; gap: 12px; border: 1px solid rgba(148, 163, 184, .22); border-radius: 8px; padding: 10px;">
            <div>
                <div style="font-weight: 700;">{{ $holiday->title }}</div>
                <div style="font-size: 12px; color: #94a3b8;">
                    {{ $holiday->date->format('M d, Y') }} -
                    {{ $holiday->type?->type }} ({{ $holiday->type?->rate }}%)
                </div>
            </div>

            <button
                type="button"
                wire:click="deleteHoliday({{ $holiday->id }})"
                style="color: #f87171; font-weight: 700;"
            >
                Delete
            </button>
        </div>
    @empty
        <p style="color: #94a3b8;">No holidays saved.</p>
    @endforelse
</div>
