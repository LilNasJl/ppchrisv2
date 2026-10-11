<x-filament-panels::page>
    @include('filament.pages.partials.memo-page-styles')

    <div class="memo-workspace">
        @include('filament.pages.partials.memo-page-header', [
            'title' => $this->employeeName(),
            'subtitle' => 'Employee notice records and supporting files.',
            'returnUrl' => \App\Filament\Pages\Memo::getUrl(),
        ])
        <div class="memo-content">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
