<div>
    @if ($shouldRender)
        <div
            x-data="{
                modalId: @js($modalId),
                autoOpen: @js($autoOpen),
                closingHandled: false,
                init() {
                    if (this.autoOpen) {
                        this.$nextTick(() => {
                            this.$dispatch('open-modal', { id: this.modalId });
                        });
                    }
                },
            }"
            x-on:close-modal.window="
                if ($event.detail?.id === modalId) {
                    if (closingHandled) {
                        closingHandled = false;
                        return;
                    }
                    $wire.dismissForSession();
                }
            "
        >
            <x-filament::modal
                :id="$modalId"
                width="2xl"
                sticky-header
                sticky-footer
                :icon="\Filament\Support\Icons\Heroicon::OutlinedSparkles"
                icon-color="primary"
            >
                <x-slot name="heading">
                    {{ $setting?->modal_heading ?: 'System Updates & Notes' }}
                </x-slot>

                @if (filled($setting?->modal_subtitle))
                    <x-slot name="description">
                        {{ $setting->modal_subtitle }}
                    </x-slot>
                @endif

                <style>
                    .su-modal-footer-bar {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: space-between;
                        gap: 0.75rem;
                        width: 100%;
                    }

                    .su-modal-footer-meta {
                        font-size: 0.76rem;
                        color: #94a3b8;
                    }

                    .su-modal-footer-buttons {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: center;
                        gap: 0.5rem;
                        margin-left: auto;
                    }
                </style>

                @include('filament.components.system-updates-feed', [
                    'updates' => $visibleUpdates,
                    'emptyMessage' => 'No updates or notes for your account right now.',
                ])

                <x-slot name="footer">
                    <div class="su-modal-footer-bar">
                        @if ($isImpersonating)
                            <span class="su-modal-footer-meta">
                                Viewing while impersonating — read status will not be altered.
                            </span>
                        @endif

                        <div class="su-modal-footer-buttons">
                            <x-filament::button
                                type="button"
                                color="gray"
                                x-on:click="closingHandled = true; $wire.dismissModal()"
                            >
                                {{ $unreadCount > 0 ? 'Remind Me Later' : 'Close' }}
                            </x-filament::button>

                            @if ($unreadCount > 0 && ! $isImpersonating)
                                <x-filament::button
                                    type="button"
                                    color="primary"
                                    icon="heroicon-m-check-circle"
                                    x-on:click="closingHandled = true; $wire.acknowledgeAll()"
                                >
                                    Got it, Mark as Read
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </x-slot>
            </x-filament::modal>
        </div>
    @endif
</div>
