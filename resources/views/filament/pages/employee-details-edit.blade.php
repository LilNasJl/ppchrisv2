<x-filament-panels::page>
    <style>
        .fi-panel-hr .fi-page:has(.employee-details-page-shell),
        .fi-panel-hr .fi-page:has(.employee-details-page-shell) .fi-page-content,
        .fi-panel-hr .fi-page:has(.employee-details-page-shell) .fi-page-content > *,
        .fi-panel-hr .employee-details-page-shell,
        .fi-panel-hr .employee-details-page-shell > *,
        .fi-panel-hr .employee-details-page-shell form,
        .fi-panel-hr .employee-details-page-shell .fi-form,
        .fi-panel-hr .employee-details-page-shell .fi-sc,
        .fi-panel-hr .employee-details-page-shell .fi-schema,
        .fi-panel-hr .employee-details-page-shell .fi-tabs {
            max-width: none !important;
            min-width: 0;
            width: 100% !important;
        }

        .fi-panel-hr .employee-details-page-shell {
            display: block;
        }

        .employee-record-header {
            align-items: center;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            border: 1px solid #3b82f6;
            border-radius: .75rem;
            color: #fff;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 2rem;
            justify-content: space-between;
            min-width: 0;
            padding: 1.25rem 1.5rem;
            width: 100%;
        }

        .employee-record-header-copy { min-width: 0; }
        .employee-record-eyebrow { color: #d9f722; font-size: .7rem; font-weight: 800; margin: 0 0 .3rem; }
        .employee-record-header h1 { color: #fff; font-size: 1.6rem; font-weight: 800; line-height: 1.2; margin: 0; overflow-wrap: anywhere; }
        .employee-record-header-actions { flex: 0 0 auto; }
        .employee-record-header-actions .fi-ac { align-items: center; display: flex; flex-wrap: wrap; gap: .5rem; justify-content: flex-end; }

        .employee-details-page-shell .employee-account-status {
            align-items: center;
            border: 1px solid;
            border-radius: .375rem;
            display: inline-flex;
            font-size: .8rem;
            font-weight: 700;
            line-height: 1.25;
            padding: .3rem .6rem;
        }

        .employee-details-page-shell .employee-account-status.is-enabled { background: rgb(5 150 105 / .18); border-color: rgb(52 211 153 / .45); color: #6ee7b7; }
        .employee-details-page-shell .employee-account-status.is-disabled { background: rgb(220 38 38 / .17); border-color: rgb(248 113 113 / .45); color: #fca5a5; }
        .employee-details-page-shell .employee-account-status.is-missing { background: rgb(100 116 139 / .2); border-color: rgb(148 163 184 / .45); color: #cbd5e1; }

        .fi-panel-hr .employee-details-page-shell .fi-sc-tabs > .fi-tabs {
            background: #101b31;
            border: 1px solid #2f4e7e;
            border-radius: .5rem .5rem 0 0;
            gap: .25rem;
            margin-bottom: 0;
            overflow-x: auto;
            padding: .5rem;
            scrollbar-width: thin;
        }

        .fi-panel-hr .employee-details-page-shell .fi-sc-tabs > .fi-tabs .fi-tabs-item {
            flex: 0 0 auto;
            font-size: .85rem;
            min-height: 2.5rem;
            white-space: nowrap;
        }

        .fi-panel-hr .employee-details-page-shell .fi-sc-tabs > .fi-tabs .fi-tabs-item.fi-active {
            background: #3b82f6;
            color: #fff;
        }

        .fi-panel-hr .employee-details-page-shell .fi-sc-tabs > .fi-sc-tabs-tab.fi-active {
            background: #151a24;
            border: 1px solid #2f405e;
            border-radius: 0 0 .5rem .5rem;
            border-top: 0;
            min-width: 0;
            padding: 1.25rem;
        }

        .fi-panel-hr .employee-details-page-shell .fi-sc-section > .fi-section {
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .fi-panel-hr .employee-details-page-shell .fi-sc-section .fi-section-header {
            background: transparent;
            border-bottom: 1px solid #334155;
            padding: .65rem 0;
        }

        .fi-panel-hr .employee-details-page-shell .fi-sc-section .fi-section-header-heading {
            color: #93c5fd;
            font-size: .9rem;
            font-weight: 750;
        }

        .fi-panel-hr .employee-details-page-shell .fi-sc-section .fi-section-content {
            padding: 1rem 0 1.25rem;
        }

        .employee-details-page-shell :is(.employee-deductions-table, .employee-loans-table) {
            border: 1px solid #334155;
            border-radius: .5rem;
            max-width: 100%;
        }

        .employee-details-page-shell .employee-deductions-table table { min-width: 720px; }
        .employee-details-page-shell .employee-loans-table table { min-width: 1120px; }

        .employee-details-page-shell :is(.employee-deductions-table, .employee-loans-table) th {
            background: #132b4e;
            color: #bfdbfe;
            font-size: .72rem;
            font-weight: 700;
            padding: .7rem .85rem !important;
            white-space: nowrap;
        }

        .employee-details-page-shell :is(.employee-deductions-table, .employee-loans-table) td {
            border-bottom: 1px solid #334155 !important;
            color: #e2e8f0;
            padding: .7rem .85rem !important;
            vertical-align: top;
        }

        .employee-details-page-shell :is(.employee-deductions-table, .employee-loans-table) tbody tr:nth-child(even) {
            background: rgb(30 41 59 / .4);
        }

        .employee-details-save-actions {
            align-items: center;
            border-top: 1px solid rgba(148, 163, 184, .25);
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.25rem;
        }

        .employee-details-save-actions button {
            min-width: 10rem;
        }

        @media (max-width: 640px) {
            .employee-record-header { align-items: stretch; padding: 1rem; }
            .employee-record-header h1 { font-size: 1.3rem; }
            .employee-record-header-actions { width: 100%; }
            .employee-record-header-actions .fi-ac { justify-content: flex-start; }
            .fi-panel-hr .employee-details-page-shell .fi-sc-tabs > .fi-sc-tabs-tab.fi-active { padding: .85rem; }
            .fi-panel-hr .employee-details-page-shell .fi-sc-section .fi-section-content { padding: .85rem 0 1rem; }
            .employee-details-save-actions button {
                width: 100%;
            }
        }
    </style>

    <form wire:submit="save" class="employee-details-page-shell">
        {{ $this->form }}

        <div class="employee-details-save-actions">
            <x-filament::button
                type="submit"
                icon="heroicon-o-check"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                Save Changes
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
