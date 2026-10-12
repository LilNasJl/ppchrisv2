@if ($memo)
    <div class="memo-details-container">
        <style>
            .memo-details-container {
                display: flex;
                flex-direction: column;
                gap: 0.65rem;
                font-size: 0.8125rem;
                color: #0f172a;
            }

            .dark .memo-details-container {
                color: #f8fafc;
            }

            .memo-details-header {
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

            .dark .memo-details-header {
                background: #1e293b;
                border-color: #334155;
            }

            .memo-details-date {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                font-size: 0.73rem;
                font-weight: 700;
                color: #64748b;
            }

            .dark .memo-details-date {
                color: #94a3b8;
            }

            .memo-type-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.15rem 0.55rem;
                border-radius: 9999px;
                font-size: 0.68rem;
                font-weight: 750;
                background: #eff6ff;
                color: #1d4ed8;
                border: 1px solid #bfdbfe;
            }

            .dark .memo-type-chip {
                background: rgba(30, 64, 175, 0.2);
                color: #93c5fd;
                border-color: rgba(96, 165, 250, 0.4);
            }

            .memo-card {
                padding: 0.65rem 0.8rem;
                border: 1px solid #e2e8f0;
                border-radius: 0.5rem;
                background: #ffffff;
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }

            .dark .memo-card {
                border-color: #334155;
                background: #0f172a;
            }

            .memo-label {
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #2563eb;
            }

            .dark .memo-label {
                color: #60a5fa;
            }

            .memo-title-text {
                font-size: 0.95rem;
                font-weight: 800;
                line-height: 1.3;
                color: #0f172a;
            }

            .dark .memo-title-text {
                color: #f8fafc;
            }

            .memo-body-text {
                font-size: 0.8125rem;
                line-height: 1.45;
                white-space: pre-line;
                color: #334155;
            }

            .dark .memo-body-text {
                color: #cbd5e1;
            }

            .memo-attachment-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.4rem;
                margin-top: 0.2rem;
            }

            .memo-btn-action {
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

            .memo-btn-action:hover {
                background: #2563eb;
                color: #ffffff;
                border-color: #2563eb;
            }

            .dark .memo-btn-action {
                background: #1e293b;
                border-color: #475569;
                color: #93c5fd;
            }

            .dark .memo-btn-action:hover {
                background: #3b82f6;
                color: #ffffff;
                border-color: #3b82f6;
            }
        </style>

        {{-- Header Strip --}}
        <div class="memo-details-header">
            <span class="memo-details-date">
                <x-filament::icon icon="heroicon-m-calendar" class="w-3.5 h-3.5" />
                Date Filed: {{ $memo->created_at?->format('M d, Y h:i A') }}
            </span>
            <span class="memo-type-chip">
                <x-filament::icon icon="heroicon-m-tag" class="w-3 h-3" />
                {{ $memo->type?->title ?: 'General Notice' }}
            </span>
        </div>

        {{-- Notice Title --}}
        <div class="memo-card">
            <span class="memo-label">Notice Subject / Title</span>
            <div class="memo-title-text">{{ $memo->title }}</div>
        </div>

        {{-- Notice Description --}}
        <div class="memo-card">
            <span class="memo-label">Official Details / Description</span>
            <div class="memo-body-text">{{ $memo->description ?: 'No detailed message provided.' }}</div>

            @if ($memo->attachment_url)
                <div style="margin-top: 0.4rem; padding-top: 0.4rem; border-top: 1px solid rgba(226, 232, 240, 0.7);">
                    <span style="font-size: 0.69rem; font-weight: 700; color: #64748b;">
                        Attached Document: {{ $memo->attachment_name }}
                    </span>
                    <div class="memo-attachment-row">
                        <a href="{{ $memo->attachment_url }}" target="_blank" rel="noopener" class="memo-btn-action">
                            <x-filament::icon icon="heroicon-m-eye" class="w-3 h-3" />
                            <span>View Document</span>
                        </a>
                        <a href="{{ $memo->attachment_url }}" download class="memo-btn-action">
                            <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-3 h-3" />
                            <span>Download</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@else
    <p style="font-size: 0.8125rem; color: #94a3b8; text-align: center; padding: 1rem;">Notice details could not be found.</p>
@endif
