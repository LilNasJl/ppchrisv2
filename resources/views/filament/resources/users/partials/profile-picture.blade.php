@php
    $employee = $record->employee;
    $displayName = $employee?->full_name ?? $record->name ?? 'Employee';
    $photoUrl = $record->profile_photo_url;
    $initials = collect([$employee?->firstname, $employee?->lastname])
        ->filter()
        ->map(fn (string $name) => strtoupper(substr($name, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<style data-employee-profile-preview>
    .employee-profile-preview {
        --profile-preview-blue: #2563eb;
        --profile-preview-blue-soft: #eff6ff;
        --profile-preview-border: rgba(37, 99, 235, .24);
        --profile-preview-text: #0f172a;

        color: var(--profile-preview-text);
        display: grid;
        gap: .85rem;
        width: 100%;
    }

    .dark .employee-profile-preview {
        --profile-preview-blue: #60a5fa;
        --profile-preview-blue-soft: rgba(30, 64, 175, .22);
        --profile-preview-border: rgba(96, 165, 250, .28);
        --profile-preview-text: #f8fafc;
    }

    .employee-profile-preview-media {
        align-items: center;
        background: var(--profile-preview-blue-soft);
        border: 2px solid var(--profile-preview-border);
        border-radius: 999px;
        display: flex;
        height: 180px;
        justify-content: center;
        justify-self: center;
        overflow: hidden;
        width: 180px;
    }

    .employee-profile-preview-media img {
        border-radius: inherit;
        height: 100%;
        object-fit: cover;
        width: 100%;
    }

    .employee-profile-preview-fallback {
        align-items: center;
        color: var(--profile-preview-blue);
        display: flex;
        font-size: clamp(2.5rem, 8vw, 5rem);
        font-weight: 800;
        height: 100%;
        justify-content: center;
        width: 100%;
    }

    .employee-profile-preview-footer {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        justify-content: space-between;
    }

    .employee-profile-preview-name {
        font-size: .95rem;
        font-weight: 800;
    }

    .employee-profile-preview-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .employee-profile-preview-action {
        align-items: center;
        border: 1px solid var(--profile-preview-border);
        border-radius: 6px;
        color: var(--profile-preview-blue);
        display: inline-flex;
        font-size: .78rem;
        font-weight: 750;
        gap: .4rem;
        min-height: 2.2rem;
        padding: .45rem .75rem;
        text-decoration: none;
    }

    .employee-profile-preview-action svg {
        height: 1rem;
        width: 1rem;
    }

    .employee-profile-preview-action.is-primary {
        background: var(--profile-preview-blue);
        border-color: var(--profile-preview-blue);
        color: #ffffff;
    }

    @media (max-width: 640px) {
        .employee-profile-preview-media {
            height: 150px;
            width: 150px;
        }

        .employee-profile-preview-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .employee-profile-preview-actions,
        .employee-profile-preview-action {
            justify-content: center;
        }

        .employee-profile-preview-action {
            flex: 1;
        }
    }
</style>

<div class="employee-profile-preview">
    @if ($photoUrl)
        <a
            href="{{ $photoUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="View {{ $displayName }} profile picture"
            class="employee-profile-preview-media"
        >
            <img src="{{ $photoUrl }}" alt="{{ $displayName }}">
        </a>
    @else
        <div class="employee-profile-preview-media">
            <div class="employee-profile-preview-fallback">{{ $initials ?: 'NA' }}</div>
        </div>
    @endif

    <div class="employee-profile-preview-footer">
        <div class="employee-profile-preview-name">{{ $displayName }}</div>

        @if ($photoUrl)
            <div class="employee-profile-preview-actions">
                <a
                    href="{{ $photoUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="employee-profile-preview-action"
                >
                    <x-filament::icon icon="heroicon-m-eye" />
                    <span>View</span>
                </a>
                <a href="{{ $photoUrl }}" download class="employee-profile-preview-action is-primary">
                    <x-filament::icon icon="heroicon-m-arrow-down-tray" />
                    <span>Download</span>
                </a>
            </div>
        @endif
    </div>
</div>
