<header class="ppc-dashboard-group-header {{ $title === 'Attendance & Leave Overview' ? 'ppc-dashboard-group-header-attendance' : '' }} {{ isset($dashboardActionUrl) ? 'ppc-dashboard-group-header--with-action' : '' }}">
    <div class="ppc-dashboard-group-copy">
        <div class="ppc-dashboard-group-eyebrow-wrap">
            <span class="ppc-dashboard-group-eyebrow">{{ $eyebrow }}</span>
        </div>
        <h2>{{ $title }}</h2>
        <p class="ppc-dashboard-group-description">{{ $description }}</p>
    </div>
    @isset($dashboardActionUrl)
        <a href="{{ $dashboardActionUrl }}" class="ppc-dashboard-header-action" aria-label="Dashboard Analytics" title="View Workforce Analytics">
            <x-filament::icon icon="heroicon-m-chart-bar" class="h-4 w-4" />
            <span class="ppc-dashboard-header-action-text">Analytics</span>
        </a>
    @endisset
</header>
