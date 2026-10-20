<x-filament-panels::page>
    @include('filament.pages.partials.system-gradient-page-header-styles')
    <style>
        .fi-page,
        .fi-page-header-main-ctn,
        .fi-page-main,
        .fi-page-content {
            max-width: none !important;
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "WORKFORCE ONBOARDING";
            letter-spacing: 0.05em;
        }

        /* Section Cards Elevation & Styling */
        .fi-section {
            border-radius: 0.875rem !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            background: #ffffff !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05) !important;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        :is(.dark) .fi-section {
            border-color: rgba(51, 65, 85, 0.8) !important;
            background: rgb(24 24 27 / 0.85) !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3) !important;
        }

        /* Section Header Polish */
        .fi-section-header {
            padding-bottom: 0.75rem !important;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }
        :is(.dark) .fi-section-header {
            border-bottom-color: rgba(51, 65, 85, 0.6);
        }

        .fi-section-header-heading {
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            letter-spacing: -0.01em;
        }

        .fi-section-header-description {
            font-size: 0.8125rem !important;
            line-height: 1.4 !important;
            color: #64748b !important;
        }
        :is(.dark) .fi-section-header-description {
            color: #94a3b8 !important;
        }

        /* Form Actions Container */
        .fi-form-actions {
            margin-top: 1.5rem !important;
            padding: 1rem 1.25rem !important;
            background: rgba(248, 250, 252, 0.9) !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            border-radius: 0.75rem !important;
            backdrop-filter: blur(8px);
        }
        :is(.dark) .fi-form-actions {
            background: rgba(24, 24, 27, 0.85) !important;
            border-color: rgba(51, 65, 85, 0.8) !important;
        }

        @media (max-width: 640px) {
            .fi-form-actions {
                flex-direction: column-reverse;
                gap: 0.75rem;
            }
            .fi-form-actions .fi-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="space-y-6">
        {{ $this->content }}
    </div>
</x-filament-panels::page>
