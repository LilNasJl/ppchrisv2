<x-filament-panels::page>
    <iframe
        src="{{ asset('page/dtr_importer.html') }}?csrf={{ urlencode(csrf_token()) }}&endpoint={{ urlencode(route('hr_tools.import.dtr')) }}"
        width="100%"
        height="860"
        style="border: none;"
    ></iframe>
</x-filament-panels::page>
