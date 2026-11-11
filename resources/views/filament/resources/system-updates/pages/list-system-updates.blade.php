<x-filament-panels::page class="system-updates-page">
    @include('filament.pages.partials.system-gradient-page-header-styles')
    @include('filament.pages.partials.compact-management-table-styles')
    <style>
        .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
            content: "SETTINGS • POPUP & RELEASE NOTES";
        }

        .system-updates-page .fi-breadcrumbs {
            display: none !important;
        }

        .su-admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 1024px) {
            .su-admin-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .su-admin-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .su-stat-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.45rem;
            padding: 0.85rem 1.05rem;
            border-radius: 0.7rem;
            border: 1px solid #1e293b;
            background: #000000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .su-stat-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0;
            inset-block-end: 0;
            inline-size: 3.5px;
        }

        .su-stat-card.theme-blue::before {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        }

        .su-stat-card.theme-emerald::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }

        .su-stat-card.theme-amber::before {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }

        .su-stat-card.theme-purple::before {
            background: linear-gradient(180deg, #a855f7 0%, #7c3aed 100%);
        }

        .su-stat-label {
            font-size: 0.68rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
        }

        .su-stat-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.25;
        }

        .su-stat-meta {
            font-size: 0.72rem;
            color: #94a3b8;
        }

        .su-settings-card {
            border-radius: 0.85rem;
            border: 1px solid rgba(59, 130, 246, 0.28);
            background: linear-gradient(180deg, #070d19 0%, #02050b 100%);
            padding: 1.1rem 1.25rem;
            margin-bottom: 1.15rem;
        }

        .su-settings-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.9rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid #1e293b;
        }

        .su-settings-title {
            font-size: 0.98rem;
            font-weight: 750;
            color: #f8fafc;
            margin: 0;
        }

        .su-settings-desc {
            font-size: 0.78rem;
            color: #94a3b8;
            margin: 0.15rem 0 0 0;
        }

        .su-settings-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 0.9rem;
            align-items: start;
        }

        .su-field-col-4 {
            grid-column: span 4 / span 4;
        }

        .su-field-col-5 {
            grid-column: span 5 / span 5;
        }

        .su-field-col-3 {
            grid-column: span 3 / span 3;
        }

        .su-field-col-12 {
            grid-column: span 12 / span 12;
        }

        @media (max-width: 1024px) {
            .su-field-col-4,
            .su-field-col-5,
            .su-field-col-3 {
                grid-column: span 12 / span 12;
            }
        }

        .su-field-label {
            display: block;
            font-size: 0.76rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 0.35rem;
        }

        .su-input,
        .su-select {
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid #1e293b;
            background: #000000;
            color: #f8fafc;
            padding: 0.52rem 0.72rem;
            font-size: 0.84rem;
            outline: none;
        }

        .su-input:focus,
        .su-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }

        .su-field-help {
            margin-top: 0.35rem;
            font-size: 0.74rem;
            color: #93c5fd;
            line-height: 1.4;
        }

        .su-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 2.35rem;
            padding: 0.45rem 0.7rem;
            border-radius: 0.5rem;
            border: 1px solid #1e293b;
            background: #000000;
            cursor: pointer;
            font-size: 0.8rem;
            color: #e2e8f0;
        }

        .su-toggle-wrap input[type="checkbox"] {
            accent-color: #2563eb;
            width: 1rem;
            height: 1rem;
        }

        .su-settings-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 0.85rem;
        }

        .su-error-msg {
            margin-top: 0.25rem;
            font-size: 0.73rem;
            color: #f87171;
        }

        .system-updates-page .fi-ta,
        .system-updates-page .fi-ta-ctn {
            background: #000000 !important;
            border-color: #1e293b !important;
        }

        .system-updates-page .fi-ta-header-toolbar {
            background: #070b12 !important;
            border-bottom: 1px solid #1e293b !important;
        }

        .system-updates-page .fi-ta-header-cell {
            background: #070b12 !important;
            border-bottom: 1px solid #1e293b !important;
        }

        .system-updates-page .fi-ta-header-cell-label {
            color: #93c5fd !important;
        }

        .system-updates-page .fi-ta table tbody tr {
            background: #000000 !important;
        }

        .system-updates-page .fi-ta table tbody tr:nth-child(even) {
            background: #050811 !important;
        }

        .system-updates-page .fi-ta table tbody tr:hover {
            background: #0c1424 !important;
        }
    </style>

    @php
        $metrics = $this->getSystemUpdateMetrics();
        $frequencyOptions = \App\Models\SystemUpdateSetting::frequencyOptions();
        $frequencyDescriptions = \App\Models\SystemUpdateSetting::frequencyDescriptions();
    @endphp

    <div class="su-admin-stats-grid">
        <div class="su-stat-card theme-blue">
            <span class="su-stat-label">Popup Frequency</span>
            <div class="su-stat-value">{{ $frequencyOptions[$popupFrequency] ?? 'Until marked as read' }}</div>
            <div class="su-stat-meta">Applies to HR &amp; Employee logins</div>
        </div>

        <div class="su-stat-card theme-emerald">
            <span class="su-stat-label">Active Entries</span>
            <div class="su-stat-value">{{ number_format($metrics['published']) }} Posted</div>
            <div class="su-stat-meta">Auto-posted across HR &amp; Employee portals</div>
        </div>

        <div class="su-stat-card theme-amber">
            <span class="su-stat-label">Pinned Highlights</span>
            <div class="su-stat-value">{{ number_format($metrics['pinned']) }} Pinned</div>
            <div class="su-stat-meta">Always sorted at top of modal</div>
        </div>

        <div class="su-stat-card theme-purple">
            <span class="su-stat-label">User Confirmations</span>
            <div class="su-stat-value">{{ number_format($metrics['acknowledgments']) }} Reads</div>
            <div class="su-stat-meta">Tracked across HR &amp; Employee users</div>
        </div>
    </div>

    <section class="su-settings-card" aria-labelledby="su-frequency-heading">
        <div class="su-settings-head">
            <div>
                <h2 id="su-frequency-heading" class="su-settings-title">Popup Frequency &amp; Modal Behavior</h2>
                <p class="su-settings-desc">Control how often the Updates &amp; Notes popup triggers when HR or Employees sign in.</p>
            </div>
        </div>

        <form wire:submit="savePopupSettings">
            <div class="su-settings-grid">
                <div class="su-field-col-4">
                    <label for="su-popup-frequency" class="su-field-label">Popup Frequency</label>
                    <select
                        id="su-popup-frequency"
                        wire:model.live="popupFrequency"
                        class="su-select"
                        @disabled(! $metrics['can_manage_settings'])
                    >
                        @foreach ($frequencyOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="su-field-help">
                        {{ $frequencyDescriptions[$popupFrequency] ?? '' }}
                    </p>
                    @error('popupFrequency')
                        <p class="su-error-msg">{{ $message }}</p>
                    @enderror
                </div>

                <div class="su-field-col-5">
                    <label for="su-modal-heading" class="su-field-label">Modal Heading</label>
                    <input
                        id="su-modal-heading"
                        type="text"
                        wire:model="modalHeading"
                        class="su-input"
                        maxlength="120"
                        placeholder="System Updates & Notes"
                        @disabled(! $metrics['can_manage_settings'])
                    />
                    @error('modalHeading')
                        <p class="su-error-msg">{{ $message }}</p>
                    @enderror

                    <label for="su-modal-subtitle" class="su-field-label" style="margin-top: 0.65rem;">Modal Subtitle</label>
                    <input
                        id="su-modal-subtitle"
                        type="text"
                        wire:model="modalSubtitle"
                        class="su-input"
                        maxlength="255"
                        placeholder="Recent enhancements, attendance & payroll updates, and important reminders."
                        @disabled(! $metrics['can_manage_settings'])
                    />
                    @error('modalSubtitle')
                        <p class="su-error-msg">{{ $message }}</p>
                    @enderror
                </div>

                <div class="su-field-col-3">
                    <span class="su-field-label">Topbar Access</span>
                    <label class="su-toggle-wrap">
                        <input
                            type="checkbox"
                            wire:model="showTopbarButton"
                            @disabled(! $metrics['can_manage_settings'])
                        />
                        <span>Show &ldquo;Updates&rdquo; button in topbar</span>
                    </label>
                    <p class="su-field-help" style="color: #94a3b8;">
                        Lets users reopen Updates &amp; Notes anytime after closing the popup.
                    </p>
                </div>
            </div>

            @if ($metrics['can_manage_settings'])
                <div class="su-settings-footer">
                    <x-filament::button
                        type="submit"
                        color="primary"
                        icon="heroicon-m-check"
                        wire:loading.attr="disabled"
                        wire:target="savePopupSettings"
                    >
                        Save Popup Settings
                    </x-filament::button>
                </div>
            @endif
        </form>
    </section>

    {{ $this->content }}
</x-filament-panels::page>
