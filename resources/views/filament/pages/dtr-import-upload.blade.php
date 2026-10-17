<x-filament-panels::page>
    @php
        $payrollPeriodOptions = base64_encode((string) json_encode($this->getPayrollPeriodOptions(), JSON_UNESCAPED_UNICODE));
    @endphp

    <iframe
        src="{{ asset('page/dtr_importer.html') }}?csrf={{ urlencode(csrf_token()) }}&endpoint={{ urlencode(route('hr_tools.import.dtr')) }}"
        data-period-options="{{ $payrollPeriodOptions }}"
        width="100%"
        height="860"
        style="border: none;"
    ></iframe>
</x-filament-panels::page>
