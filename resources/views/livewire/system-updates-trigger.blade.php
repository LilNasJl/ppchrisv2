<div class="su-topbar-trigger-root">
    @if ($shouldRender)
        <style>
            .su-topbar-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.42rem;
                height: 2.15rem;
                padding: 0 0.72rem;
                border-radius: 9999px;
                border: 1px solid rgba(96, 165, 250, 0.28);
                background: rgba(15, 23, 42, 0.75);
                color: #e2e8f0;
                font-size: 0.78rem;
                font-weight: 650;
                cursor: pointer;
                transition: all 150ms ease;
                margin-inline-end: 0.25rem;
                white-space: nowrap;
            }

            .su-topbar-btn:hover {
                border-color: rgba(96, 165, 250, 0.65);
                background: rgba(30, 58, 138, 0.35);
                color: #ffffff;
            }

            .su-topbar-btn-icon {
                width: 1rem;
                height: 1rem;
                color: #60a5fa;
                flex-shrink: 0;
            }

            .su-topbar-btn-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 1.15rem;
                height: 1.15rem;
                padding: 0 0.32rem;
                border-radius: 9999px;
                background: #2563eb;
                color: #ffffff;
                font-size: 0.68rem;
                font-weight: 800;
                line-height: 1;
            }

            @media (max-width: 640px) {
                .su-topbar-btn-label {
                    display: none;
                }

                .su-topbar-btn {
                    padding: 0 0.55rem;
                }
            }
        </style>

        <button
            type="button"
            class="su-topbar-btn"
            wire:click="openUpdatesModal"
            title="View System Updates & Notes"
            aria-label="View System Updates and Notes"
        >
            <x-filament::icon icon="heroicon-o-sparkles" class="su-topbar-btn-icon" />
            <span class="su-topbar-btn-label">Updates</span>
            @if ($unreadCount > 0)
                <span class="su-topbar-btn-badge">{{ $unreadCount }}</span>
            @endif
        </button>
    @endif
</div>
