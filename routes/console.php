<?php

use App\Models\Employee;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('leave:reset-yearly', function (): void {
    Employee::query()->update([
        'leave_credits' => 10,
        'birthday_leave_credits' => 1,
        'leave_credits_year' => now()->year,
    ]);

    $this->info('Employee leave credits have been reset for ' . now()->year . '.');
})->purpose('Reset employee leave and birthday leave credits for the current year');
