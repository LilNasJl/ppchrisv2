@php
    $isDtrMain = $this instanceof \App\Filament\Employee\Pages\Dtr;
    $selectedVal = $isDtrMain ? ($this->period_id ?: $this->periodId) : $this->periodId;
    $modelProp = $isDtrMain ? 'period_id' : 'periodId';
@endphp

<div class="dtr-period-select-wrap">
    <div class="dtr-select-container">
        <x-filament::icon icon="heroicon-m-calendar" class="dtr-select-icon" />
        <select wire:model.live="{{ $modelProp }}" class="dtr-native-select" aria-label="Payroll Period">
            @if ($isDtrMain)
                <option value="">Select Payroll Period...</option>
            @else
                <option value="">All Payroll Periods</option>
            @endif
            @foreach ($this->payrollPeriods() as $periodOption)
                <option value="{{ $periodOption->id }}" @selected((string) $periodOption->id === (string) $selectedVal)>
                    {{ $periodOption->title }} ({{ $periodOption->is_locked ? 'Locked' : 'Open' }})
                </option>
            @endforeach
        </select>
        <x-filament::icon icon="heroicon-m-chevron-down" class="dtr-select-chevron" />
    </div>
</div>
