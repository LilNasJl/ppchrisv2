<x-filament-panels::page>
    @include('filament.pages.partials.memo-page-styles')

    <div class="memo-workspace hr-leave-detail">
        @include('filament.pages.partials.memo-page-header', [
            'eyebrow' => 'EMPLOYEE REQUESTS',
            'title' => 'Leave Request Details',
            'subtitle' => 'Review request information, approval progress, and decision history.',
            'returnUrl' => route('filament.hr.resources.leaves.index'),
        ])

        <div class="memo-content hr-leave-detail-content">
            {{ $this->content }}
        </div>
    </div>

    <style>
        .hr-leave-detail-content,
        .hr-leave-detail-content .leave-review {
            min-width: 0;
            width: 100%;
        }

        .hr-leave-detail .memo-header-actions .fi-btn,
        .hr-leave-detail .memo-return {
            min-height: 2.15rem;
        }
    </style>
</x-filament-panels::page>
