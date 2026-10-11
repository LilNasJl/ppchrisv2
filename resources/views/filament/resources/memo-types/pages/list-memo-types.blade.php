<x-filament-panels::page>
    @include('filament.pages.partials.memo-page-styles')

    <div class="memo-workspace">
        @include('filament.pages.partials.memo-page-header', [
            'title' => 'Notice Types',
            'subtitle' => 'Manage the categories used for employee notices.',
            'returnUrl' => \App\Filament\Pages\Memo::getUrl(),
        ])
        <div class="memo-content">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
