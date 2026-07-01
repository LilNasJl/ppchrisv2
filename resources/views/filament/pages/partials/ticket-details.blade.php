@if ($ticket)
    <div style="display: grid; gap: 12px; font-size: 14px;">
        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Date Sent</div>
                <div style="font-weight: 700;">{{ $ticket->created_at?->format('M d, Y h:i A') }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Status</div>
                <div style="font-weight: 700;">{{ $ticket->status }}</div>
            </div>
        </div>

        <div>
            <div style="color: #94a3b8; font-size: 12px;">Ticket Title</div>
            <div style="font-weight: 700;">{{ $ticket->title }}</div>
        </div>

        <div>
            <div style="color: #94a3b8; font-size: 12px;">Description</div>
            <div style="white-space: pre-line;">{{ $ticket->description }}</div>
        </div>

        @if ($ticket->employee_attachment_url)
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Employee Attached File</div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap; font-weight: 700;">
                    <a href="{{ $ticket->employee_attachment_url }}" target="_blank" rel="noopener" style="color: #60a5fa;">View File</a>
                    <a href="{{ $ticket->employee_attachment_url }}" download style="color: #60a5fa;">Download File</a>
                </div>
            </div>
        @endif

        <div>
            <div style="color: #94a3b8; font-size: 12px;">HR Comment</div>
            <div style="white-space: pre-line;">{{ $ticket->hr_comment ?: 'No comment yet.' }}</div>
        </div>

        @if ($ticket->hr_attachment_url)
            <div>
                <div style="color: #94a3b8; font-size: 12px;">HR Attached File</div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap; font-weight: 700;">
                    <a href="{{ $ticket->hr_attachment_url }}" target="_blank" rel="noopener" style="color: #60a5fa;">View File</a>
                    <a href="{{ $ticket->hr_attachment_url }}" download style="color: #60a5fa;">Download File</a>
                </div>
            </div>
        @endif
    </div>
@else
    <div style="color: #94a3b8;">Ticket not found.</div>
@endif
