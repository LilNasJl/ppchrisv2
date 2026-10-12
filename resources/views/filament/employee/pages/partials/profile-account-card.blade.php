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
        <div class="emp-avatar-frame">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $displayName }}" class="emp-avatar-img">
            @else
                <div class="emp-avatar-initials">{{ $initials }}</div>
            @endif
        </div>
        @if ($photoUrl)
            <div class="emp-avatar-actions">
                <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer" class="emp-avatar-btn" title="View full image">
                    <x-filament::icon icon="heroicon-m-eye" class="w-3.5 h-3.5" />
                    <span>View</span>
                </a>
                <a href="{{ $photoUrl }}" download class="emp-avatar-btn" title="Download image">
                    <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-3.5 h-3.5" />
                    <span>Save</span>
                </a>
            </div>
        @endif
    </div>

    <div class="emp-account-details">
        <div class="emp-account-header-row">
            <div>
                <h3 class="emp-account-name">{{ $displayName }}</h3>
                <p class="emp-account-sub">
                    <span class="emp-chip primary">
                        <x-filament::icon icon="heroicon-m-identification" class="w-3 h-3" />
                        ID: {{ $companyId }}
                    </span>
                    @if ($record?->email)
                        <span class="emp-chip muted">
                            <x-filament::icon icon="heroicon-m-envelope" class="w-3 h-3" />
                            {{ $record->email }}
                        </span>
                    @endif
                </p>
            </div>
            <span class="emp-badge-status">Active Account</span>
        </div>

        <div class="emp-account-meta-chips">
            @if ($designation)
                <span class="emp-chip violet">
                    <x-filament::icon icon="heroicon-m-briefcase" class="w-3 h-3" />
                    {{ $designation }}
                </span>
            @endif
            @if ($department)
                <span class="emp-chip sky">
                    <x-filament::icon icon="heroicon-m-building-office-2" class="w-3 h-3" />
                    {{ $department }}
                </span>
            @endif
            @if ($branch)
                <span class="emp-chip slate">
                    <x-filament::icon icon="heroicon-m-building-storefront" class="w-3 h-3" />
                    {{ $branch }}
                </span>
            @endif
        </div>
    </div>
</div>
