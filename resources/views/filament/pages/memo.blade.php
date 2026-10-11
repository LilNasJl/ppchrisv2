<x-filament-panels::page>
    @include('filament.pages.partials.memo-page-styles')

    <div class="memo-workspace">
        @include('filament.pages.partials.memo-page-header', [
            'title' => 'Employee Notice',
            'subtitle' => 'Find employees and review their notice records.',
        ])
        <div class="memo-content">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
