<section class="ppc-birthday-highlights" aria-labelledby="ppc-birthday-highlights-title" data-dashboard-birthday="true">
    <header class="ppc-birthday-highlights-header">
        <div class="ppc-birthday-highlights-title-wrap">
            <x-filament::icon icon="heroicon-s-cake" />
            <h3 id="ppc-birthday-highlights-title">
                @if ($birthdayEmployees->isNotEmpty())
                    Today's Birthday Celebrant{{ $birthdayEmployees->count() > 1 ? 's' : '' }}
                @else
                    Birthday Highlights
                @endif
            </h3>
            <span class="ppc-birthday-highlights-tag">
                {{ $today->format('D, M j, Y') }}
            </span>
        </div>

        @if ($calendarUrl)
            <a href="{{ $calendarUrl }}" class="ppc-birthday-highlights-link" title="Open Birthday Calendar">
                <x-filament::icon icon="heroicon-m-calendar" />
                <span>Birthday Calendar</span>
                <x-filament::icon icon="heroicon-m-arrow-right" class="h-3 w-3" />
            </a>
        @endif
    </header>

    @if ($birthdayEmployees->isNotEmpty())
        <div class="ppc-birthday-cards-grid">
            @foreach ($birthdayEmployees as $celebrant)
                <div class="ppc-birthday-card">
                    <div class="ppc-birthday-person">
                        <div class="ppc-birthday-avatar" aria-hidden="true">
                            @if (!empty($celebrant['avatar']) && !str_contains($celebrant['avatar'], 'default-profile'))
                                <img src="{{ $celebrant['avatar'] }}" alt="{{ $celebrant['name'] }}" />
                            @else
                                <span>{{ $celebrant['initials'] }}</span>
                            @endif
                        </div>
                        <div class="ppc-birthday-meta">
                            <div class="ppc-birthday-name-wrap">
                                <strong>{{ $celebrant['name'] }}</strong>
                                @if ($celebrant['age'])
                                    <span class="ppc-birthday-age-pill">Turning {{ $celebrant['age'] }} today</span>
                                @endif
                            </div>
                            <p>
                                {{ $celebrant['designation'] }}
                                <span aria-hidden="true">&middot;</span>
                                {{ $celebrant['branch'] }}
                            </p>
                        </div>
                    </div>
                    <div class="ppc-birthday-badge-today">
                        <x-filament::icon icon="heroicon-s-sparkles" />
                        <span>Happy Birthday!</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="ppc-birthday-empty-row">
            <div class="ppc-birthday-empty-info">
                <x-filament::icon icon="heroicon-o-cake" class="h-4 w-4 text-slate-400" />
                <p class="ppc-birthday-empty">No employee birthdays today.</p>
            </div>
        </div>
    @endif

    @if ($upcomingBirthdays->isNotEmpty())
        <div class="ppc-birthday-upcoming-strip">
            <span class="ppc-birthday-upcoming-title">
                <x-filament::icon icon="heroicon-o-clock" class="h-3 w-3 inline" />
                Upcoming:
            </span>
            @foreach ($upcomingBirthdays as $upcoming)
                <span class="ppc-birthday-upcoming-pill" title="{{ $upcoming['designation'] }} ({{ $upcoming['branch'] }})">
                    <strong>{{ $upcoming['name'] }}</strong>
                    <span>{{ $upcoming['date'] }} ({{ $upcoming['relative'] }})</span>
                </span>
            @endforeach
        </div>
    @endif
</section>
