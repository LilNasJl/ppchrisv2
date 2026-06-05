@php
    $columns = $this->getColumns();
    $pollingInterval = $this->getPollingInterval();
    $attritionBreakdown = $this->getAttritionBreakdown();

    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-stats-overview',
            ])
    "
>
    <style>
        .ppc-attrition-modal {
            position: fixed !important;
            inset: 0 !important;
            z-index: 9999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1.5rem !important;
            background: rgba(3, 7, 18, 0.68) !important;
        }

        .ppc-attrition-backdrop {
            position: absolute !important;
            inset: 0 !important;
            border: 0 !important;
            background: transparent !important;
        }

        .ppc-attrition-card {
            position: relative !important;
            width: min(100%, 34rem) !important;
            border-radius: 0.875rem !important;
            border: 1px solid rgba(17, 24, 39, 0.12) !important;
            background: #ffffff !important;
            color: #111827 !important;
            padding: 1.5rem !important;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35) !important;
        }

        .dark .ppc-attrition-card {
            border-color: rgba(255, 255, 255, 0.12) !important;
            background: #18181b !important;
            color: #ffffff !important;
        }

        .ppc-attrition-header {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            gap: 1rem !important;
        }

        .ppc-attrition-title {
            margin: 0 !important;
            font-size: 1rem !important;
            font-weight: 700 !important;
            line-height: 1.5rem !important;
        }

        .ppc-attrition-note {
            margin: 0.25rem 0 0 !important;
            color: #6b7280 !important;
            font-size: 0.875rem !important;
            line-height: 1.25rem !important;
        }

        .dark .ppc-attrition-note {
            color: #a1a1aa !important;
        }

        .ppc-attrition-close {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 2rem !important;
            height: 2rem !important;
            border: 0 !important;
            border-radius: 0.5rem !important;
            background: transparent !important;
            color: #6b7280 !important;
            cursor: pointer !important;
        }

        .ppc-attrition-close:hover {
            background: rgba(107, 114, 128, 0.12) !important;
            color: #111827 !important;
        }

        .dark .ppc-attrition-close {
            color: #d4d4d8 !important;
        }

        .dark .ppc-attrition-close:hover {
            color: #ffffff !important;
        }

        .ppc-attrition-groups {
            margin-top: 1.25rem !important;
            overflow: hidden !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.75rem !important;
        }

        .dark .ppc-attrition-groups {
            border-color: rgba(255, 255, 255, 0.12) !important;
        }

        .ppc-attrition-group {
            padding: 1rem !important;
            border-top: 1px solid #e5e7eb !important;
        }

        .ppc-attrition-group:first-child {
            border-top: 0 !important;
        }

        .dark .ppc-attrition-group {
            border-color: rgba(255, 255, 255, 0.12) !important;
        }

        .ppc-attrition-type {
            margin: 0 !important;
            font-size: 0.925rem !important;
            font-weight: 700 !important;
            line-height: 1.35rem !important;
        }

        .ppc-attrition-text {
            margin: 0.25rem 0 0 !important;
            color: #374151 !important;
            font-size: 0.875rem !important;
            line-height: 1.6 !important;
        }

        .dark .ppc-attrition-text {
            color: #d4d4d8 !important;
        }
    </style>

    {{ $this->content }}

    @if ($this->showAttritionBreakdown)
        <div
            x-data
            x-on:keydown.escape.window="$wire.closeAttritionBreakdown()"
            class="ppc-attrition-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="attrition-breakdown-title"
        >
            <button
                type="button"
                wire:click="closeAttritionBreakdown"
                class="ppc-attrition-backdrop"
                aria-label="Close attrition breakdown"
            ></button>

            <div
                class="ppc-attrition-card"
            >
                <div class="ppc-attrition-header">
                    <div>
                        <h2 id="attrition-breakdown-title" class="ppc-attrition-title">
                            Attrition Breakdown
                        </h2>
                        <p class="ppc-attrition-note">
                            Percentage is based on all employee accounts.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeAttritionBreakdown"
                        class="ppc-attrition-close"
                        aria-label="Close attrition breakdown"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" style="width: 1.25rem; height: 1.25rem;" />
                    </button>
                </div>

                <div class="ppc-attrition-groups">
                    @foreach ($attritionBreakdown as $attrition)
                        <div class="ppc-attrition-group">
                            <p class="ppc-attrition-type">
                                {{ $attrition['type'] }}
                            </p>

                            <p class="ppc-attrition-text">
                                {{ $attrition['label'] }}:
                                <span style="color: {{ $attrition['color'] }}; font-weight: 800;">
                                    {{ $attrition['percentage'] }}
                                </span>
                                from {{ number_format($attrition['count']) }} employee/s.
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
