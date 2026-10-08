<section class="ppc-holiday-highlights" aria-labelledby="ppc-holiday-highlights-title">
    <header class="ppc-holiday-highlights-header">
        <h3 id="ppc-holiday-highlights-title">Holiday Calendar Highlights</h3>
        <time datetime="{{ $today->toDateString() }}">{{ $today->format('D, M j, Y') }}</time>
    </header>

    @forelse ($holidays as $holiday)
        <div class="ppc-holiday-highlight-row">
            <div>
                <strong>{{ $holiday->title }}</strong>
                <p>{{ $holiday->type?->type ?? 'Holiday' }} <span aria-hidden="true">&middot;</span> {{ $holiday->branch?->branch_name ?? 'All branches' }}</p>
            </div>
            <span class="ppc-holiday-today">Today</span>
        </div>
    @empty
        <p class="ppc-holiday-empty">No holiday is scheduled for today.</p>
    @endforelse
</section>
