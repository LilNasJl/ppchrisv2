<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\EmployeePanelProvider;
use App\Providers\Filament\HrPanelProvider;
use App\Providers\Filament\KpiPanelProvider;

return [
    AppServiceProvider::class,
    HrPanelProvider::class,
    EmployeePanelProvider::class,
    KpiPanelProvider::class,
];
