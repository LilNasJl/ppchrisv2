<div class="dtr-period-select-wrap">
    <div class="dtr-select-container">
        <x-filament::icon icon="heroicon-m-calendar" class="dtr-select-icon" />
        <select wire:model.live="periodId" class="dtr-native-select" aria-label="Payroll Period">
            <option value="">All Payroll Periods</option>
            @foreach ($this->payrollPeriods() as $periodOption)
                <option value="{{ $periodOption->id }}" @selected((string) $periodOption->id === (string) $this->periodId)>
                    {{ $periodOption->title }} ({{ $periodOption->is_locked ? 'Locked' : 'Open' }})
                </option>
            @endforeach
        </select>
        <x-filament::icon icon="heroicon-m-chevron-down" class="dtr-select-chevron" />
    </div>
</div>
