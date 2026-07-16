<x-filament-panels::page>
    <style>
        .employee-profile-form {
            --emp-profile-border: rgba(15, 23, 42, .12);
            --emp-profile-blue: #2563eb;
            --emp-profile-blue-strong: #1d4ed8;
            --emp-profile-blue-soft: #eff6ff;
            --emp-profile-surface: #ffffff;
            --emp-profile-text: #0f172a;

            color: var(--emp-profile-text);
            display: grid;
            gap: 20px;
            max-width: 1440px;
            min-width: 0;
            width: 100%;
        }

        .dark .employee-profile-form {
            --emp-profile-border: rgba(148, 163, 184, .24);
            --emp-profile-blue: #60a5fa;
            --emp-profile-blue-strong: #93c5fd;
            --emp-profile-blue-soft: rgba(30, 64, 175, .26);
            --emp-profile-surface: #0f172a;
            --emp-profile-text: #f8fafc;
        }

        .employee-profile-form .fi-tabs {
            background: var(--emp-profile-surface);
            border: 1px solid var(--emp-profile-border);
            border-top: 3px solid var(--emp-profile-blue);
            border-radius: 8px;
            box-shadow: none;
            gap: .25rem;
            max-width: 100%;
            overflow-x: auto;
            padding: .45rem;
            scrollbar-width: thin;
        }

        .employee-profile-form .fi-tabs-item {
            border-radius: 6px;
            color: #64748b;
            min-height: 2.4rem;
        }

        .dark .employee-profile-form .fi-tabs-item {
            color: #94a3b8;
        }

        .employee-profile-form .fi-tabs-item:hover,
        .employee-profile-form .fi-tabs-item:focus-visible {
            background: var(--emp-profile-blue-soft);
            color: var(--emp-profile-blue-strong);
        }

        .employee-profile-form .fi-tabs-item.fi-active {
            background: var(--emp-profile-blue);
            color: #ffffff;
        }

        .employee-profile-form .fi-tabs-item.fi-active :is(svg, span) {
            color: #ffffff;
        }

        .employee-profile-form .fi-sc-tabs-tab.fi-active {
            display: grid;
            gap: 1rem;
        }

        .employee-profile-form .fi-sc-section {
            border-color: var(--emp-profile-border);
            border-radius: 8px;
            box-shadow: none;
        }

        .employee-profile-form .fi-sc-section-header {
            background: var(--emp-profile-blue-soft);
            border-bottom-color: var(--emp-profile-border);
        }

        .employee-profile-form .fi-sc-fieldset {
            border-color: var(--emp-profile-border);
        }

        .employee-profile-form .fi-sc-fieldset-legend {
            color: var(--emp-profile-blue-strong);
        }

        .employee-profile-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 2px;
        }

        .employee-profile-actions .fi-btn {
            min-width: 150px;
        }

        @media (max-width: 640px) {
            .employee-profile-actions {
                justify-content: stretch;
            }

            .employee-profile-actions .fi-btn {
                width: 100%;
            }
        }
    </style>

    <form wire:submit.prevent="save" class="employee-profile-form">
        {{ $this->form }}

        <div class="employee-profile-actions">
            <x-filament::button type="submit" icon="heroicon-m-check">
                Save Profile
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
