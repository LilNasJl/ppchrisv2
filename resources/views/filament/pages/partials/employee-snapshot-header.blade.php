<header class="ppc-dashboard-group-header {{ $title === 'Attendance & Leave Overview' ? 'ppc-dashboard-group-header-attendance' : '' }} {{ isset($dashboardActionUrl) ? 'ppc-dashboard-group-header--with-action' : '' }}">
    <div class="ppc-dashboard-group-copy">
        <p class="ppc-dashboard-group-eyebrow">{{ $eyebrow }}</p>
        <h2>{{ $title }}</h2>
        <p class="ppc-dashboard-group-description">{{ $description }}</p>
    </div>
    @isset($dashboardActionUrl)
        <a href="{{ $dashboardActionUrl }}" class="ppc-dashboard-header-action" aria-label="Dashboard Analytics" title="Dashboard Analytics">
            <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5" />
        </a>
    @endisset
</header>
