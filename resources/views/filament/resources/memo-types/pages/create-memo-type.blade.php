<x-filament-panels::page>
    @include('filament.pages.partials.memo-page-styles')

    <div class="memo-workspace memo-form-page">
        @include('filament.pages.partials.memo-page-header', [
            'title' => 'New Notice Type',
            'subtitle' => 'Add a category for employee notices.',
            'returnUrl' => \App\Filament\Resources\MemoTypes\MemoTypeResource::getUrl(),
        ])
        <div class="memo-content">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
