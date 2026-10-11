@if ($ticket)
    <style>
        .ticket-details {
            display: grid;
            gap: 0.7rem;
            color: #e5e7eb;
            font-size: 0.82rem;
        }

        .ticket-details-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.55rem;
        }

        .ticket-details-item {
            min-width: 0;
            padding: 0.55rem 0.65rem;
            border: 1px solid #374151;
            border-radius: 6px;
            background: #18181b;
        }

        .ticket-details-label {
            display: block;
            margin-bottom: 0.18rem;
            color: #93c5fd;
            font-size: 0.66rem;
            font-weight: 750;
            text-transform: uppercase;
        }

        .ticket-details-value {
            overflow-wrap: anywhere;
            font-weight: 650;
        }

        .ticket-details-body {
            white-space: pre-line;
            line-height: 1.45;
        }

        .ticket-details-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .ticket-details-links a {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.55rem;
            border: 1px solid #3b82f6;
            border-radius: 5px;
            color: #bfdbfe;
            font-size: 0.74rem;
            font-weight: 650;
            text-decoration: none;
        }

        .ticket-details-links a:hover {
            background: rgb(37 99 235 / 0.18);
            color: #fff;
        }

        @media (max-width: 480px) {
            .ticket-details-meta { grid-template-columns: 1fr; }
        }
    </style>

    @php
        $statusColor = $ticket->status === \App\Models\Ticket::STATUS_DONE ? '#86efac' : '#fcd34d';
    @endphp

    <div class="ticket-details">
        <div class="ticket-details-meta">
            <div class="ticket-details-item">
                <span class="ticket-details-label">Date Sent</span>
                <span class="ticket-details-value">{{ $ticket->created_at?->format('M d, Y h:i A') }}</span>
            </div>
            <div class="ticket-details-item">
                <span class="ticket-details-label">Status</span>
                <span class="ticket-details-value" style="color: {{ $statusColor }}">{{ $ticket->status }}</span>
            </div>
        </div>

        <div class="ticket-details-item">
            <span class="ticket-details-label">Ticket Title</span>
            <div class="ticket-details-value">{{ $ticket->title }}</div>
        </div>

        <div class="ticket-details-item">
            <span class="ticket-details-label">Description</span>
            <div class="ticket-details-body">{{ $ticket->description }}</div>
        </div>

        @if ($ticket->employee_attachment_url)
            <div class="ticket-details-item">
                <span class="ticket-details-label">Employee Attachment</span>
                <div class="ticket-details-links">
                    <a href="{{ $ticket->employee_attachment_url }}" target="_blank" rel="noopener">View File</a>
                    <a href="{{ $ticket->employee_attachment_url }}" download>Download</a>
                </div>
            </div>
        @endif

        <div class="ticket-details-item">
            <span class="ticket-details-label">HR Comment</span>
            <div class="ticket-details-body">{{ $ticket->hr_comment ?: 'No comment yet.' }}</div>
        </div>

        @if ($ticket->hr_attachment_url)
            <div class="ticket-details-item">
                <span class="ticket-details-label">HR Attachment</span>
                <div class="ticket-details-links">
                    <a href="{{ $ticket->hr_attachment_url }}" target="_blank" rel="noopener">View File</a>
                    <a href="{{ $ticket->hr_attachment_url }}" download>Download</a>
                </div>
            </div>
        @endif
    </div>
@else
    <p class="text-sm text-gray-500">Ticket not found.</p>
@endif
