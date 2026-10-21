@php($wfhEnabled = $this->isPolicyEnabled())
<header class="wfh-header">
    <div>
        <p class="wfh-eyebrow">DTR SETTINGS</p>
        <div class="wfh-title-row">
            <h1 class="wfh-title">Saturday WFH Policy</h1>
            <span class="wfh-status {{ $wfhEnabled ? 'is-enabled' : 'is-disabled' }}" role="status" aria-label="Saturday WFH policy {{ $wfhEnabled ? 'enabled' : 'disabled' }}">
                <span class="wfh-status-dot" aria-hidden="true"></span>
                WFH {{ $wfhEnabled ? 'Enabled' : 'Disabled' }}
            </span>
        </div>
        <p class="wfh-subtitle">
            {{ $wfhEnabled
                ? 'Regular-schedule employees work from home on Saturdays unless assigned on-site below.'
                : 'Saturday WFH is disabled. Use Settings to enable it for regular-schedule employees.' }}
        </p>
    </div>
    <div class="wfh-header-actions">
        <x-filament::actions :actions="$this->getCachedHeaderActions()" />
    </div>
</header>
