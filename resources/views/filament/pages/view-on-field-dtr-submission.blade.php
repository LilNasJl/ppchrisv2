<x-filament-panels::page>
    @include('filament.pages.partials.on-field-dtr-details', [
        'submission' => $this->submission,
        'showReviewer' => true,
    ])
</x-filament-panels::page>
