<x-filament-panels::page>
    @include('filament.pages.partials.memo-page-styles')

    <div class="memo-workspace leave-workspace">
        @include('filament.pages.partials.memo-page-header', [
            'eyebrow' => 'EMPLOYEE REQUESTS',
            'title' => 'Leave Tracking',
            'subtitle' => 'Review leave requests, follow approval progress, and manage employee leave history.',
        ])

        <div class="memo-content leave-content">
            {{ $this->content }}
        </div>
    </div>

    <style>
        .leave-content {
            min-width: 0;
        }

        .leave-content .fi-ta-ctn {
            overflow: hidden !important;
        }

        .leave-content .fi-ta-header-cell {
            padding: 0.42rem 0.65rem !important;
        }

        .leave-content .fi-ta-text {
            min-height: 1.9rem !important;
            padding: 0.3rem 0.65rem !important;
        }

        .leave-content .fi-ta-actions {
            gap: 0.2rem !important;
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .leave-content .fi-ta-ctn {
                overflow-x: auto !important;
            }

            .leave-content .fi-ta table {
                min-width: 980px;
            }
        }
    </style>
</x-filament-panels::page>
