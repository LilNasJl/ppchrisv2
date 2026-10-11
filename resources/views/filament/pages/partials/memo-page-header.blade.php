<header class="memo-header">
    <div class="memo-header-copy">
        <p class="memo-eyebrow">{{ $eyebrow ?? 'EMPLOYEE REQUESTS' }}</p>
        <h1 class="memo-title">{{ $title }}</h1>
        <p class="memo-subtitle">{{ $subtitle }}</p>
    </div>
    <div class="memo-header-actions">
        @if (filled($returnUrl ?? null))
            <a href="{{ $returnUrl }}" class="memo-return">
                <x-filament::icon icon="heroicon-o-arrow-left" />
                <span>Return</span>
            </a>
        @endif
        <x-filament::actions :actions="$this->getCachedHeaderActions()" />
    </div>
</header>
