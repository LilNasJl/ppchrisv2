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
        --shoutout-surface: var(--dashboard-surface, #111827);
        --shoutout-muted: var(--dashboard-muted, #94a3b8);
        --shoutout-border: var(--dashboard-border, rgba(148, 163, 184, .24));
        --shoutout-blue: var(--dashboard-blue, #60a5fa);
        --shoutout-blue-soft: var(--dashboard-blue-soft, rgba(37, 99, 235, .16));
        --shoutout-text: var(--dashboard-text, var(--ed-text, #0f172a));
        background: var(--shoutout-surface);
        border: 1px solid var(--shoutout-border);
        border-radius: 8px;
        overflow: hidden;
    }

    .dark .ppc-dashboard-shoutout {
        --shoutout-text: var(--dashboard-text, var(--ed-text, #f8fafc));
    }

    .ppc-dashboard-shoutout-header {
        align-items: center;
        background: var(--shoutout-blue-soft);
        border-bottom: 1px solid var(--shoutout-border);
        display: flex;
        gap: 8px;
        padding: 9px 12px;
    }

    .ppc-dashboard-shoutout-header > svg {
        color: var(--shoutout-blue);
        height: 17px;
        width: 17px;
    }

    .ppc-dashboard-shoutout-header h2 {
        color: var(--dashboard-blue-strong, #93c5fd);
        font-size: 14px;
        font-weight: 800;
        margin: 0;
    }

    .ppc-dashboard-shoutout-action {
        align-items: center;
        background: var(--shoutout-blue);
        border: 1px solid var(--shoutout-blue);
        border-radius: 6px;
        color: #fff;
        display: inline-flex;
        font-size: 11px;
        font-weight: 750;
        gap: 6px;
        margin-left: auto;
        min-height: 30px;
        padding: 5px 9px;
    }

    .ppc-dashboard-shoutout-action:disabled { cursor: not-allowed; opacity: .75; }

    .ppc-dashboard-shoutout-action svg { color: currentColor; height: 14px; width: 14px; }

    .ppc-dashboard-shoutout table { border-collapse: collapse; width: 100%; }

    .ppc-dashboard-shoutout tbody,
    .ppc-dashboard-shoutout tbody tr { background: var(--shoutout-surface) !important; }

    .ppc-dashboard-shoutout td {
        background: transparent !important;
        padding: 7px 12px;
        vertical-align: middle;
    }

    .ppc-dashboard-shoutout tr + tr { border-top: 1px solid var(--shoutout-border); }

    .ppc-dashboard-shoutout td:first-child { width: 190px; }

    .ppc-dashboard-shoutout-person { align-items: center; display: flex; gap: 8px; min-width: 0; }

    .ppc-dashboard-shoutout-avatar {
        align-items: center;
        background: #1d4ed8;
        border: 1px solid #60a5fa;
        border-radius: 50%;
        color: #fff;
        display: inline-flex;
        flex: 0 0 28px;
        font-size: 10px;
        font-weight: 800;
        height: 28px;
        justify-content: center;
    }

    .ppc-dashboard-shoutout-person strong,
    .ppc-dashboard-shoutout-person small { display: block; overflow-wrap: anywhere; }

    .ppc-dashboard-shoutout-person strong { color: var(--shoutout-text); font-size: 12px; }

    .ppc-dashboard-shoutout-person small { color: var(--shoutout-muted); font-size: 10px; }

    .ppc-dashboard-shoutout-message {
        background: var(--shoutout-surface);
        border: 1px solid var(--shoutout-border);
        border-radius: 8px;
        color: var(--shoutout-text);
        font-size: 12px;
        line-height: 1.35;
        padding: 7px 10px;
    }

    .ppc-dashboard-shoutout-sent-at {
        align-items: center;
        color: var(--shoutout-muted);
        display: flex;
        font-size: 10px;
        gap: 5px;
        justify-content: flex-end;
        margin-top: 4px;
        white-space: nowrap;
    }

    .ppc-dashboard-shoutout-sent-at svg { height: 12px; width: 12px; }

    @media (max-width: 640px) {
        .ppc-dashboard-shoutout tr { display: grid; }
        .ppc-dashboard-shoutout td { display: block; padding: 7px 10px 0; }
        .ppc-dashboard-shoutout td:first-child { width: auto; }
        .ppc-dashboard-shoutout td:last-child { padding-bottom: 8px; }
    }
</style>

<section class="ppc-dashboard-shoutout" aria-labelledby="ppc-shoutout-title">
    <header class="ppc-dashboard-shoutout-header">
        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" />
        <h2 id="ppc-shoutout-title">Shout Out Hall</h2>
        <button type="button" class="ppc-dashboard-shoutout-action" disabled title="Coming soon">
            <x-filament::icon icon="heroicon-o-paper-airplane" />
            Send Message
        </button>
    </header>
    <table aria-label="Shout Out Hall sample posts">
        <tbody>
            @foreach (array_slice($shoutOutSamples, 0, 5) as $shoutOut)
                <tr>
                    <td>
                        <div class="ppc-dashboard-shoutout-person">
                            <span class="ppc-dashboard-shoutout-avatar" aria-hidden="true">{{ $shoutOut['initials'] }}</span>
                            <span>
                                <strong>{{ $shoutOut['name'] }}</strong>
                                <small>{{ $shoutOut['role'] }}</small>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="ppc-dashboard-shoutout-message">{{ $shoutOut['message'] }}</div>
                        <time class="ppc-dashboard-shoutout-sent-at" datetime="{{ $shoutOut['sent_at']->toIso8601String() }}">
                            <x-filament::icon icon="heroicon-o-clock" />
                            {{ $shoutOut['sent_at']->format('M d, Y, g:i A') }}
                        </time>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
