<header class="employee-record-header">
    <div class="employee-record-header-copy">
        <p class="employee-record-eyebrow">{{ $eyebrow ?? 'WORKFORCE RECORDS' }}</p>
        <h1>{{ $employee?->full_name ?? 'Employee Details' }}</h1>
    </div>

    <div class="employee-record-header-actions">
        <x-filament::actions :actions="$headerActions" />
    </div>
</header>
