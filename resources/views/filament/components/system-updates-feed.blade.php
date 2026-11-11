@props([
    'updates',
    'emptyMessage' => 'No updates or notes available right now.',
])

<style>
    .su-feed-stack {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .su-card {
        border-radius: 0.75rem;
        border: 1px solid rgba(51, 65, 85, 0.85);
        background: rgba(15, 23, 42, 0.72);
        padding: 1rem 1.15rem;
    }

    .su-card-head {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem 0.75rem;
        margin-bottom: 0.35rem;
    }

    .su-card-title {
        font-size: 0.98rem;
        font-weight: 700;
        color: #f8fafc;
        line-height: 1.35;
        margin: 0;
    }

    .su-card-meta {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: #93c5fd;
        white-space: nowrap;
    }

    .su-card-summary {
        font-size: 0.84rem;
        color: #cbd5e1;
        line-height: 1.5;
        margin: 0 0 0.65rem 0;
        white-space: pre-line;
    }

    .su-items-list {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .su-item-row {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        font-size: 0.82rem;
        line-height: 1.48;
    }

    .su-item-dot {
        flex-shrink: 0;
        width: 0.42rem;
        height: 0.42rem;
        border-radius: 9999px;
        margin-top: 0.48rem;
        background: #60a5fa;
    }

    .su-item-dot--policy {
        background: #fbbf24;
    }

    .su-item-dot--fix {
        background: #34d399;
    }

    .su-item-dot--feature {
        background: #c084fc;
    }

    .su-item-dot--note {
        background: #f87171;
    }

    .su-item-body {
        min-width: 0;
        flex: 1 1 auto;
    }

    .su-item-title {
        font-size: 0.84rem;
        font-weight: 650;
        color: #f1f5f9;
        margin: 0;
    }

    .su-item-desc {
        font-size: 0.8rem;
        color: #94a3b8;
        margin: 0.1rem 0 0 0;
        white-space: pre-line;
    }

    .su-notes-line {
        margin-top: 0.7rem;
        padding: 0.45rem 0.75rem;
        border-left: 2px solid #f59e0b;
        background: rgba(245, 158, 11, 0.08);
        border-radius: 0 0.4rem 0.4rem 0;
        font-size: 0.8rem;
        color: #fde68a;
        line-height: 1.48;
        white-space: pre-line;
    }

    .su-notes-line strong {
        color: #fcd34d;
        font-weight: 700;
    }

    .su-empty-state {
        padding: 2rem 1.25rem;
        text-align: center;
        border-radius: 0.75rem;
        border: 1px dashed rgba(148, 163, 184, 0.3);
        background: rgba(15, 23, 42, 0.5);
        color: #94a3b8;
        font-size: 0.85rem;
    }
</style>

<div class="su-feed-stack">
    @forelse ($updates as $update)
        @php
            $items = is_array($update->items)
                ? array_values(array_filter($update->items, fn ($row) => is_array($row) && filled($row['title'] ?? null)))
                : [];
            $categoryLabel = \App\Models\SystemUpdate::categoryLabel((string) ($update->category ?: 'update'));
        @endphp

        <article class="su-card">
            <div class="su-card-head">
                <h3 class="su-card-title">{{ $update->title }}</h3>

                <span class="su-card-meta">{{ $categoryLabel }}</span>
            </div>

            @if (filled($update->summary))
                <p class="su-card-summary">{{ $update->summary }}</p>
            @endif

            @if ($items !== [])
                <ul class="su-items-list">
                    @foreach ($items as $item)
                        @php
                            $itemType = (string) ($item['type'] ?? 'update');
                        @endphp
                        <li class="su-item-row">
                            <span class="su-item-dot su-item-dot--{{ $itemType }}" aria-hidden="true"></span>
                            <div class="su-item-body">
                                <p class="su-item-title">{{ $item['title'] }}</p>
                                @if (filled($item['description'] ?? null))
                                    <p class="su-item-desc">{{ $item['description'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (filled($update->notes))
                <div class="su-notes-line">
                    <strong>Note:</strong> {{ $update->notes }}
                </div>
            @endif
        </article>
    @empty
        <div class="su-empty-state">
            {{ $emptyMessage }}
        </div>
    @endforelse
</div>
