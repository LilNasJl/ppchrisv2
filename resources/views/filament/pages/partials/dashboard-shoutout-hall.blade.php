@php
    $shoutOutSamples = [
        ['name' => 'Lorem Ipsum', 'role' => 'Sample employee', 'initials' => 'LI', 'message' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'sent_at' => now()->copy()->subMinutes(18)],
        ['name' => 'Dolor Sit', 'role' => 'Sample employee', 'initials' => 'DS', 'message' => 'Sed do eiusmod tempor incididunt ut labore.', 'sent_at' => now()->copy()->subMinutes(42)],
        ['name' => 'Amet Consectetur', 'role' => 'Sample employee', 'initials' => 'AC', 'message' => 'Ut enim ad minim veniam, quis nostrud exercitation.', 'sent_at' => now()->copy()->subHours(3)],
        ['name' => 'Adipiscing Elit', 'role' => 'Sample employee', 'initials' => 'AE', 'message' => 'Duis aute irure dolor in reprehenderit.', 'sent_at' => now()->copy()->subDay()],
        ['name' => 'Tempor Incididunt', 'role' => 'Sample employee', 'initials' => 'TI', 'message' => 'Excepteur sint occaecat cupidatat non proident.', 'sent_at' => now()->copy()->subDays(2)],
    ];
@endphp

<style>
    .ppc-dashboard-shoutout {
        --shoutout-surface: #000000;
        --shoutout-card-surface: #0a1122;
        --shoutout-muted: #94a3b8;
        --shoutout-border: #1e293b;
        --shoutout-blue: #3b82f6;
        --shoutout-blue-soft: rgba(30, 64, 175, 0.2);
        --shoutout-text: #f8fafc;
        background: #000000 !important;
        border: 1px solid #1e293b !important;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4);
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .ppc-dashboard-shoutout-header {
        align-items: center;
        background: #0a1122 !important;
        border-bottom: 1px solid #1e293b !important;
        display: flex;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 12px;
        flex: 0 0 auto;
    }

    .ppc-dashboard-shoutout-header-title {
        align-items: center;
        display: flex;
        gap: 6px;
    }

    .ppc-dashboard-shoutout-header-title > svg {
        color: #60a5fa;
        height: 16px;
        width: 16px;
    }

    .ppc-dashboard-shoutout-header h2 {
        color: #93c5fd !important;
        font-size: 0.8125rem;
        font-weight: 750;
        margin: 0;
    }

    .ppc-dashboard-shoutout-tag {
        background: rgba(59, 130, 246, 0.2) !important;
        color: #60a5fa !important;
        font-size: 0.625rem;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .ppc-dashboard-shoutout-action {
        align-items: center;
        background: #1d4ed8;
        border: 1px solid #2563eb;
        border-radius: 6px;
        color: #fff;
        display: inline-flex;
        font-size: 0.6875rem;
        font-weight: 700;
        gap: 5px;
        min-height: 24px;
        height: 24px;
        padding: 2px 8px;
        opacity: 0.9;
        transition: opacity 150ms ease;
    }

    .ppc-dashboard-shoutout-action:disabled {
        cursor: not-allowed;
        opacity: 0.7;
    }

    .ppc-dashboard-shoutout-action svg {
        color: currentColor;
        height: 11px;
        width: 11px;
    }

    .ppc-dashboard-shoutout-feed {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 10px;
        overflow-y: auto;
    }

    @media (min-width: 1280px) {
        .ppc-dashboard-shoutout-feed {
            max-height: calc(100vh - 12rem);
            min-height: 500px;
        }
    }

    @media (max-width: 1279px) {
        .ppc-dashboard-shoutout-feed {
            max-height: 340px;
        }
    }

    .ppc-dashboard-shoutout-feed::-webkit-scrollbar {
        width: 5px;
    }

    .ppc-dashboard-shoutout-feed::-webkit-scrollbar-track {
        background: transparent;
    }

    .ppc-dashboard-shoutout-feed::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.3);
        border-radius: 9999px;
    }

    .ppc-dashboard-shoutout-item {
        background: #0a1122 !important;
        border: 1px solid #1e293b !important;
        border-radius: 8px;
        padding: 8px 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        transition: border-color 150ms ease;
    }

    .ppc-dashboard-shoutout-item:hover {
        border-color: rgba(59, 130, 246, 0.4) !important;
    }

    .ppc-dashboard-shoutout-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }

    .ppc-dashboard-shoutout-person {
        align-items: center;
        display: flex;
        gap: 8px;
        min-width: 0;
    }

    .ppc-dashboard-shoutout-avatar {
        align-items: center;
        background: linear-gradient(135deg, #1d4ed8, #2563eb);
        border: 1px solid rgba(147, 197, 253, 0.5);
        border-radius: 50%;
        color: #fff;
        display: inline-flex;
        flex: 0 0 24px;
        font-size: 9px;
        font-weight: 750;
        height: 24px;
        width: 24px;
        justify-content: center;
    }

    .ppc-dashboard-shoutout-meta {
        min-width: 0;
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .ppc-dashboard-shoutout-meta strong {
        color: #f8fafc !important;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ppc-dashboard-shoutout-meta small {
        color: #94a3b8 !important;
        font-size: 0.65rem;
    }

    .ppc-dashboard-shoutout-sent-at {
        align-items: center;
        color: #94a3b8 !important;
        display: flex;
        font-size: 0.625rem;
        gap: 3px;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .ppc-dashboard-shoutout-sent-at svg {
        height: 10px;
        width: 10px;
    }

    .ppc-dashboard-shoutout-message {
        background: #000000 !important;
        border: 1px solid #1e293b !important;
        border-radius: 6px;
        color: #f8fafc !important;
        font-size: 0.75rem;
        line-height: 1.35;
        padding: 6px 9px;
    }

    .ppc-dashboard-shoutout-message p {
        margin: 0;
    }

    .ppc-dashboard-shoutout-footer {
        align-items: center;
        border-top: 1px solid #1e293b !important;
        background: #000000 !important;
        color: #94a3b8 !important;
        display: flex;
        font-size: 0.65rem;
        gap: 5px;
        padding: 6px 10px;
        flex: 0 0 auto;
    }

    .ppc-dashboard-shoutout-dot {
        background: #22c55e;
        border-radius: 50%;
        display: inline-block;
        height: 6px;
        width: 6px;
        box-shadow: 0 0 6px #22c55e;
    }
</style>

<section class="ppc-dashboard-shoutout" aria-labelledby="ppc-shoutout-title">
    <header class="ppc-dashboard-shoutout-header">
        <div class="ppc-dashboard-shoutout-header-title">
            <x-filament::icon icon="heroicon-o-chat-bubble-left-right" />
            <h2 id="ppc-shoutout-title">Shout Out Hall</h2>
            <span class="ppc-dashboard-shoutout-tag">Live Feed</span>
        </div>
        <button type="button" class="ppc-dashboard-shoutout-action" disabled title="Coming soon">
            <x-filament::icon icon="heroicon-o-paper-airplane" />
            <span>Send Message</span>
        </button>
    </header>
    <div class="ppc-dashboard-shoutout-feed" role="feed" aria-label="Recent Shout Out posts">
        @foreach ($shoutOutSamples as $shoutOut)
            <article class="ppc-dashboard-shoutout-item">
                <div class="ppc-dashboard-shoutout-top">
                    <div class="ppc-dashboard-shoutout-person">
                        <span class="ppc-dashboard-shoutout-avatar" aria-hidden="true">{{ $shoutOut['initials'] }}</span>
                        <div class="ppc-dashboard-shoutout-meta">
                            <strong>{{ $shoutOut['name'] }}</strong>
                            <small>{{ $shoutOut['role'] }}</small>
                        </div>
                    </div>
                    <time class="ppc-dashboard-shoutout-sent-at" datetime="{{ $shoutOut['sent_at']->toIso8601String() }}">
                        <x-filament::icon icon="heroicon-o-clock" />
                        {{ $shoutOut['sent_at']->format('M d, g:i A') }}
                    </time>
                </div>
                <div class="ppc-dashboard-shoutout-message">
                    <p>{{ $shoutOut['message'] }}</p>
                </div>
            </article>
        @endforeach
    </div>
    <footer class="ppc-dashboard-shoutout-footer">
        <span class="ppc-dashboard-shoutout-dot" aria-hidden="true"></span>
        <span>Recent employee shout-outs and team recognitions</span>
    </footer>
</section>
