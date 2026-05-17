

<x-filament-panels::page>

    <div class="mb-4" style="max-width: 560px;">
        {{ $this->form }}
    </div>

    <iframe 
        src="{{ asset('page/hr_atttendance_viewer.html') }}?period_id={{ $this->period_id }}&branch_id={{ $this->branch_id }}"
        width="100%" 
        height="800px"
        style="border: none;">
    </iframe>

</x-filament-panels::page>
