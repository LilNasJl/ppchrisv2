<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request): string => match (true) {
            $request->is('sicrc', 'sicrc/*', 'sicrc-tools/*') => route('filament.sicrc.auth.login'),
            $request->is('kpi', 'kpi/*') => route('filament.kpi.auth.login'),
            $request->is('employee', 'employee/*') => route('filament.employee.auth.login'),
            default => route('filament.hr.auth.login'),
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('leave:reset-yearly')->yearlyOn(1, 1, '00:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
