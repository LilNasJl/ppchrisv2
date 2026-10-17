@php
    $record = auth()->user();
    $employee = $record?->employee;
    $displayName = $employee?->full_name ?? $record?->name ?? 'Employee';
    $companyId = $employee ? \App\Models\Employee::companyIdFromUid($employee->uid) : ($record?->name ?? 'N/A');
    $photoUrl = $record?->profile_photo_url;
    $initials = collect([$employee?->firstname, $employee?->lastname])
        ->filter()
        ->map(fn (string $name) => strtoupper(substr($name, 0, 1)))
        ->take(2)
        ->implode('');
    if (empty($initials)) {
        $initials = strtoupper(substr($record?->name ?? 'EM', 0, 2));
    }
    $designation = $employee?->designation?->title ?? 'Employee';
    $department = $employee?->department?->name;
    $branch = $employee?->branch?->branch_name;
@endphp

<div class="emp-account-card">
    <div class="emp-avatar-block">
        <button
            type="button"
            class="emp-avatar-frame emp-avatar-clickable"
            x-on:click="$dispatch('open-modal', { id: 'emp-profile-photo-modal' })"
            title="Click to view and download profile photo"
            aria-label="View and download profile photo"
            aria-haspopup="dialog"
        >
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $displayName }}" class="emp-avatar-img">
            @else
                <div class="emp-avatar-initials">{{ $initials }}</div>
            @endif

            <div class="emp-avatar-overlay" aria-hidden="true">
                <x-filament::icon icon="heroicon-m-magnifying-glass-plus" class="w-5 h-5 text-white" />
                <span class="emp-avatar-overlay-text">View</span>
            </div>
        </button>
    </div>

    <div class="emp-account-details">
        <div class="emp-account-header-row">
            <div>
                <h3 class="emp-account-name">{{ $displayName }}</h3>
            </div>
            <span class="emp-badge-status">
                <span class="emp-status-dot" aria-hidden="true"></span>
                Active Account
            </span>
        </div>

        <div class="emp-account-badges-group">
            <div class="emp-account-badges-row">
                <span class="emp-chip emp-chip-id" title="Employee ID">
                    <x-filament::icon icon="heroicon-m-identification" class="w-3.5 h-3.5" />
                    <span>ID: {{ $companyId }}</span>
                </span>
                @if ($record?->email)
                    <span class="emp-chip" title="Work Email">
                        <x-filament::icon icon="heroicon-m-envelope" class="w-3.5 h-3.5" />
                        <span>{{ $record->email }}</span>
                    </span>
                @endif
            </div>

            <div class="emp-account-badges-row">
                @if ($designation)
                    <span class="emp-chip" title="Designation">
                        <x-filament::icon icon="heroicon-m-briefcase" class="w-3.5 h-3.5" />
                        <span>{{ $designation }}</span>
                    </span>
                @endif
                @if ($department)
                    <span class="emp-chip" title="Department">
                        <x-filament::icon icon="heroicon-m-building-office-2" class="w-3.5 h-3.5" />
                        <span>{{ $department }}</span>
                    </span>
                @endif
                @if ($branch)
                    <span class="emp-chip" title="Branch / Location">
                        <x-filament::icon icon="heroicon-m-building-storefront" class="w-3.5 h-3.5" />
                        <span>{{ $branch }}</span>
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

<x-filament::modal id="emp-profile-photo-modal" width="md" :close-button="true">
    <x-slot name="heading">
        Profile Photo
    </x-slot>

    <x-slot name="description">
        {{ $displayName }} &bull; {{ $companyId }}
    </x-slot>

    <div class="emp-modal-photo-content">
        @if ($photoUrl)
            <div class="emp-modal-photo-frame">
                <img src="{{ $photoUrl }}" alt="{{ $displayName }}" class="emp-modal-photo-img">
            </div>
        @else
            <div class="emp-modal-initials-frame">
                <span class="emp-modal-initials">{{ $initials }}</span>
            </div>
        @endif

        <div class="emp-modal-meta-info">
            <span class="emp-modal-meta-name">{{ $displayName }}</span>
            <span class="emp-modal-meta-sub">{{ $designation }} &bull; {{ $companyId }}</span>
        </div>
    </div>

    <x-slot name="footer">
        <div class="emp-modal-footer">
            @if ($photoUrl)
                <x-filament::button
                    tag="a"
                    href="{{ $photoUrl }}"
                    download="profile-{{ $companyId }}.png"
                    icon="heroicon-m-arrow-down-tray"
                    color="primary"
                >
                    Download Photo
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="{{ $photoUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    icon="heroicon-m-arrow-top-right-on-square"
                    color="gray"
                >
                    Open Original
                </x-filament::button>
            @endif

            <x-filament::button
                type="button"
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'emp-profile-photo-modal' })"
            >
                Close
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
