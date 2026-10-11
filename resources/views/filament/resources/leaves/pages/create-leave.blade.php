<x-filament-panels::page>
    @include('filament.pages.partials.memo-page-styles')

    <div class="memo-workspace memo-form-page leave-create-page">
        @include('filament.pages.partials.memo-page-header', [
            'eyebrow' => 'EMPLOYEE REQUESTS',
            'title' => 'New Leave Request',
            'subtitle' => 'Submit a leave request for HR review and approval.',
            'returnUrl' => route('filament.hr.resources.leaves.index'),
        ])

        <div class="memo-content leave-create-content">
            {{ $this->content }}
        </div>
    </div>

    <style>
        .leave-create-page .leave-create-content form,
        .leave-create-page .leave-create-content .fi-sc-section {
            width: 100%;
            max-width: none;
        }

        .leave-create-content .fi-sc-section {
            padding: 0.85rem 1rem;
        }

        .leave-create-content .fi-sc {
            gap: 0.75rem;
        }

        .leave-create-content .fi-fo-field-wrp {
            min-width: 0;
        }

        .leave-create-content .fi-btn {
            min-height: 2.25rem;
        }

        @media (max-width: 640px) {
            .leave-create-content .fi-sc-section {
                padding: 0.65rem;
            }
        }
    </style>
</x-filament-panels::page>
