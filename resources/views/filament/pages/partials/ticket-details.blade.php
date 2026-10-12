@if ($ticket)
    @php
        $isDone = $ticket->status === \App\Models\Ticket::STATUS_DONE;
    @endphp

    <div class="tk-details-container">
        <style>
            .tk-details-container {
                display: flex;
                flex-direction: column;
                gap: 0.65rem;
                font-size: 0.8125rem;
                color: #0f172a;
            }

            .dark .tk-details-container {
                color: #f8fafc;
            }

            .tk-details-header {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding: 0.6rem 0.8rem;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 0.5rem;
            }

            .dark .tk-details-header {
                background: #1e293b;
                border-color: #334155;
            }

            .tk-details-date {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                font-size: 0.73rem;
                font-weight: 700;
                color: #64748b;
            }

            .dark .tk-details-date {
                color: #94a3b8;
            }

            .tk-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.15rem 0.55rem;
                border-radius: 9999px;
                font-size: 0.68rem;
                font-weight: 750;
                line-height: 1.2;
            }

            .tk-badge.done {
                background: #dcfce7;
                color: #15803d;
                border: 1px solid #86efac;
            }

            .dark .tk-badge.done {
                background: rgba(34, 197, 94, 0.2);
                color: #86efac;
                border-color: rgba(34, 197, 94, 0.4);
            }

            .tk-badge.pending {
                background: #fef3c7;
                color: #b45309;
                border: 1px solid #fcd34d;
            }

            .dark .tk-badge.pending {
                background: rgba(245, 158, 11, 0.2);
                color: #fcd34d;
                border-color: rgba(245, 158, 11, 0.4);
            }

            .tk-card {
                padding: 0.65rem 0.8rem;
                border: 1px solid #e2e8f0;
                border-radius: 0.5rem;
                background: #ffffff;
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }

            .dark .tk-card {
                border-color: #334155;
                background: #0f172a;
            }

            .tk-label {
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #2563eb;
            }

            .dark .tk-label {
                color: #60a5fa;
            }

            .tk-title-text {
                font-size: 0.95rem;
                font-weight: 800;
                line-height: 1.3;
                color: #0f172a;
            }

            .dark .tk-title-text {
                color: #f8fafc;
            }

            .tk-body-text {
                font-size: 0.8125rem;
                line-height: 1.45;
                white-space: pre-line;
                color: #334155;
            }

            .dark .tk-body-text {
                color: #cbd5e1;
            }

            .tk-attachment-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.4rem;
                margin-top: 0.2rem;
            }

            .tk-btn-action {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.2rem 0.5rem;
                border: 1px solid #cbd5e1;
                border-radius: 0.35rem;
                background: #f8fafc;
                color: #2563eb;
                font-size: 0.72rem;
                font-weight: 700;
                text-decoration: none;
                transition: all 120ms ease;
            }

            .tk-btn-action:hover {
                background: #2563eb;
                color: #ffffff;
                border-color: #2563eb;
            }

            .dark .tk-btn-action {
                background: #1e293b;
                border-color: #475569;
                color: #93c5fd;
            }

            .dark .tk-btn-action:hover {
                background: #3b82f6;
                color: #ffffff;
                border-color: #3b82f6;
            }

            .tk-hr-section {
                border-left: 3px solid #2563eb;
                background: #eff6ff;
            }

            .dark .tk-hr-section {
                border-left-color: #60a5fa;
                background: rgba(30, 64, 175, 0.15);
            }
        </style>

        {{-- Header Strip --}}
        <div class="tk-details-header">
            <span class="tk-details-date">
                <x-filament::icon icon="heroicon-m-calendar" class="w-3.5 h-3.5" />
                Sent on {{ $ticket->created_at?->format('M d, Y h:i A') }}
            </span>
            <span class="tk-badge {{ $isDone ? 'done' : 'pending' }}">
                <x-filament::icon icon="{{ $isDone ? 'heroicon-m-check-circle' : 'heroicon-m-clock' }}" class="w-3 h-3" />
                {{ $ticket->status }}
            </span>
        </div>

        {{-- Ticket Title --}}
        <div class="tk-card">
            <span class="tk-label">Ticket Subject</span>
            <div class="tk-title-text">{{ $ticket->title }}</div>
        </div>

        {{-- Ticket Description --}}
        <div class="tk-card">
            <span class="tk-label">Inquiry & Description</span>
            <div class="tk-body-text">{{ $ticket->description }}</div>

            @if ($ticket->employee_attachment_url)
                <div style="margin-top: 0.4rem; padding-top: 0.4rem; border-top: 1px solid rgba(226, 232, 240, 0.7);">
                    <span style="font-size: 0.69rem; font-weight: 700; color: #64748b;">
                        Attached Document: {{ $ticket->employee_attachment_name }}
                    </span>
                    <div class="tk-attachment-row">
                        <a href="{{ $ticket->employee_attachment_url }}" target="_blank" rel="noopener" class="tk-btn-action">
                            <x-filament::icon icon="heroicon-m-eye" class="w-3 h-3" />
                            <span>View Document</span>
                        </a>
                        <a href="{{ $ticket->employee_attachment_url }}" download class="tk-btn-action">
                            <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-3 h-3" />
                            <span>Download</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- HR Feedback Section --}}
        <div class="tk-card tk-hr-section">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.4rem;">
                <span class="tk-label" style="display: inline-flex; align-items: center; gap: 0.25rem;">
                    <x-filament::icon icon="heroicon-m-chat-bubble-left-ellipsis" class="w-3.5 h-3.5" />
                    Human Resources Feedback
                </span>
                @if ($ticket->done_at)
                    <span style="font-size: 0.68rem; color: #64748b;">Resolved on {{ $ticket->done_at->format('M d, Y') }}</span>
                @endif
            </div>

            @if ($ticket->hr_comment)
                <div class="tk-body-text" style="font-weight: 550;">{{ $ticket->hr_comment }}</div>
            @else
                <div class="tk-body-text" style="color: #64748b; font-style: italic;">
                    No HR feedback recorded yet. Your ticket is currently queued for HR review.
                </div>
            @endif

            @if ($ticket->hr_attachment_url)
                <div style="margin-top: 0.4rem; padding-top: 0.4rem; border-top: 1px solid rgba(37, 99, 235, 0.15);">
                    <span style="font-size: 0.69rem; font-weight: 700; color: #2563eb;">
                        HR Attached File: {{ $ticket->hr_attachment_name }}
                    </span>
                    <div class="tk-attachment-row">
                        <a href="{{ $ticket->hr_attachment_url }}" target="_blank" rel="noopener" class="tk-btn-action">
                            <x-filament::icon icon="heroicon-m-eye" class="w-3 h-3" />
                            <span>View File</span>
                        </a>
                        <a href="{{ $ticket->hr_attachment_url }}" download class="tk-btn-action">
                            <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-3 h-3" />
                            <span>Download</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@else
    <p style="font-size: 0.8125rem; color: #94a3b8; text-align: center; padding: 1rem;">Ticket details could not be found.</p>
@endif
