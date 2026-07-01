<div style="display: grid; gap: 14px;">
    <form wire:submit.prevent="createHolidayType" style="border: 1px solid rgba(96, 165, 250, .35); border-radius: 8px; padding: 12px; display: grid; gap: 10px;">
        <div style="font-weight: 700;">Create Holiday Type</div>

        <div style="display: grid; grid-template-columns: minmax(0, 1fr) 120px; gap: 10px;">
            <label style="display: grid; gap: 4px; font-size: 12px; font-weight: 600;">
                Holiday Type
                <input
                    type="text"
                    wire:model="newHolidayType"
                    placeholder="e.g., Local Holiday"
                    style="width: 100%; border: 1px solid rgba(148, 163, 184, .35); border-radius: 8px; padding: 8px 10px; background: rgba(15, 23, 42, .35);"
                >
            </label>

            <label style="display: grid; gap: 4px; font-size: 12px; font-weight: 600;">
                Rate (%)
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    wire:model="newHolidayRate"
                    placeholder="100"
                    style="width: 100%; border: 1px solid rgba(148, 163, 184, .35); border-radius: 8px; padding: 8px 10px; background: rgba(15, 23, 42, .35);"
                >
            </label>
        </div>

        <label style="display: grid; gap: 4px; font-size: 12px; font-weight: 600;">
            Description
            <textarea
                rows="2"
                wire:model="newHolidayDescription"
                placeholder="Optional description"
                style="width: 100%; border: 1px solid rgba(148, 163, 184, .35); border-radius: 8px; padding: 8px 10px; background: rgba(15, 23, 42, .35);"
            ></textarea>
        </label>

        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" style="color: #60a5fa; font-weight: 700;">
                Create Type
            </button>
        </div>
    </form>

    @foreach ($holidayTypes as $holidayType)
        <div style="border: 1px solid rgba(148, 163, 184, .22); border-radius: 8px; padding: 12px; display: grid; gap: 10px;">
            <div style="display: grid; grid-template-columns: minmax(0, 1fr) 120px; gap: 10px;">
                <label style="display: grid; gap: 4px; font-size: 12px; font-weight: 600;">
                    Holiday Type
                    <input
                        type="text"
                        wire:model="holidayTypeEdits.{{ $holidayType->id }}.type"
                        style="width: 100%; border: 1px solid rgba(148, 163, 184, .35); border-radius: 8px; padding: 8px 10px; background: rgba(15, 23, 42, .35);"
                    >
                </label>

                <label style="display: grid; gap: 4px; font-size: 12px; font-weight: 600;">
                    Rate (%)
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="holidayTypeEdits.{{ $holidayType->id }}.rate"
                        style="width: 100%; border: 1px solid rgba(148, 163, 184, .35); border-radius: 8px; padding: 8px 10px; background: rgba(15, 23, 42, .35);"
                    >
                </label>
            </div>

            <label style="display: grid; gap: 4px; font-size: 12px; font-weight: 600;">
                Description
                <textarea
                    rows="2"
                    wire:model="holidayTypeEdits.{{ $holidayType->id }}.description"
                    style="width: 100%; border: 1px solid rgba(148, 163, 184, .35); border-radius: 8px; padding: 8px 10px; background: rgba(15, 23, 42, .35);"
                ></textarea>
            </label>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                @if (! in_array($holidayType->type, $defaultTypes, true))
                    <button
                        type="button"
                        wire:click="deleteHolidayType({{ $holidayType->id }})"
                        style="color: #f87171; font-weight: 700;"
                    >
                        Delete
                    </button>
                @endif

                <button
                    type="button"
                    wire:click="updateHolidayType({{ $holidayType->id }})"
                    style="color: #60a5fa; font-weight: 700;"
                >
                    Save
                </button>
            </div>
        </div>
    @endforeach
</div>
