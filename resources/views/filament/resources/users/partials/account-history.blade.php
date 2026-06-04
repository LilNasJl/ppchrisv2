@if ($histories->isNotEmpty())
    <div style="display: grid; gap: 10px;">
        @foreach ($histories as $history)
            <div style="border: 1px solid rgba(148, 163, 184, .35); border-radius: 8px; padding: 12px;">
                <div style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <strong style="color: {{ $history->is_disabled ? '#f87171' : '#34d399' }};">
                        {{ $history->is_disabled ? 'Disabled' : 'Enabled' }}
                    </strong>
                    <span style="color: #94a3b8; font-size: 12px;">
                        {{ $history->created_at?->format('M d, Y h:i A') }}
                    </span>
                </div>

                <div style="margin-top: 6px; color: #94a3b8; font-size: 12px;">
                    Changed by: {{ $history->changedBy?->username ?: $history->changedBy?->name ?: 'System' }}
                </div>

                <div style="margin-top: 8px; white-space: pre-line;">
                    {{ $history->remarks ?: 'No remarks provided.' }}
                </div>
            </div>
        @endforeach
    </div>
@else
    <div style="color: #94a3b8;">No account status history yet.</div>
@endif
