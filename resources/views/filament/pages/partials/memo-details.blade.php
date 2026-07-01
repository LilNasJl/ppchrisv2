@if ($memo)
    <div style="display: grid; gap: 12px; font-size: 14px;">
        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Date Filed</div>
                <div style="font-weight: 700;">{{ $memo->created_at?->format('M d, Y h:i A') }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Memo Type</div>
                <div style="font-weight: 700;">{{ $memo->type?->title ?: 'N/A' }}</div>
            </div>
        </div>

        <div>
            <div style="color: #94a3b8; font-size: 12px;">Memo Title</div>
            <div style="font-weight: 700;">{{ $memo->title }}</div>
        </div>

        <div>
            <div style="color: #94a3b8; font-size: 12px;">Memo Description</div>
            <div style="white-space: pre-line;">{{ $memo->description ?: 'N/A' }}</div>
        </div>

        @if ($memo->attachment_url)
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Attached File</div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap; font-weight: 700;">
                    <a href="{{ $memo->attachment_url }}" target="_blank" rel="noopener" style="color: #60a5fa;">View File</a>
                    <a href="{{ $memo->attachment_url }}" download style="color: #60a5fa;">Download File</a>
                </div>
            </div>
        @endif
    </div>
@else
    <div style="color: #94a3b8;">Memo not found.</div>
@endif
