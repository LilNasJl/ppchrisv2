@php
    $employee = auth()->user()?->employee;
    $rateType = ucfirst($employee?->rate_type ?? 'Daily');
    $dailyRate = (float) ($employee?->daily_rate ?? 0);
    $monthlyRate = (float) ($employee?->monthly_rate ?? 0);
    $allowance = (float) ($employee?->allowance ?? 0);
    $paymentType = strtoupper($employee?->payment_type ?? 'ATM');
    $isMonthly = strtolower($employee?->rate_type ?? '') === 'monthly';
@endphp

<div class="emp-salary-grid">
    <div class="emp-stat-card primary">
        <div class="emp-stat-icon">
            <x-filament::icon icon="heroicon-m-banknotes" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
        </div>
        <div class="emp-stat-content">
            <span class="emp-stat-label">RATE STRUCTURE</span>
            <div class="emp-stat-value text-blue-700 dark:text-blue-300">
                {{ $rateType }} Rate
            </div>
            <span class="emp-stat-badge blue">{{ $isMonthly ? 'Fixed Monthly Package' : 'Daily Wage Earner' }}</span>
        </div>
    </div>

    <div class="emp-stat-card">
        <div class="emp-stat-icon">
            <x-filament::icon icon="heroicon-m-calendar" class="w-5 h-5 text-slate-600 dark:text-slate-400" />
        </div>
        <div class="emp-stat-content">
            <span class="emp-stat-label">DAILY RATE</span>
            <div class="emp-stat-value">
                ₱{{ number_format($dailyRate, 2) }}
            </div>
            <span class="emp-stat-sub">Per day standard rate</span>
        </div>
    </div>

    <div class="emp-stat-card">
        <div class="emp-stat-icon">
            <x-filament::icon icon="heroicon-m-calendar-days" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
        </div>
        <div class="emp-stat-content">
            <span class="emp-stat-label">MONTHLY BASIC RATE</span>
            <div class="emp-stat-value text-emerald-700 dark:text-emerald-300">
                ₱{{ number_format($monthlyRate, 2) }}
            </div>
            <span class="emp-stat-sub">Base monthly amount</span>
        </div>
    </div>

    <div class="emp-stat-card">
        <div class="emp-stat-icon">
            <x-filament::icon icon="heroicon-m-gift" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
        </div>
        <div class="emp-stat-content">
            <span class="emp-stat-label">MONTHLY ALLOWANCE</span>
            <div class="emp-stat-value {{ $allowance > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-400' }}">
                ₱{{ number_format($allowance, 2) }}
            </div>
            <span class="emp-stat-sub">{{ $allowance > 0 ? 'Active monthly allowance' : 'No allowance assigned' }}</span>
        </div>
    </div>

    <div class="emp-stat-card">
        <div class="emp-stat-icon">
            <x-filament::icon icon="heroicon-m-credit-card" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
        </div>
        <div class="emp-stat-content">
            <span class="emp-stat-label">PAYMENT CHANNEL</span>
            <div class="emp-stat-value text-indigo-700 dark:text-indigo-300">
                {{ $paymentType ?: 'PAYROLL' }}
            </div>
            <span class="emp-stat-sub">Salary disbursement</span>
        </div>
    </div>
</div>
