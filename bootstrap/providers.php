<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\EmployeePanelProvider;
use App\Providers\Filament\HrPanelProvider;
use App\Providers\Filament\KpiPanelProvider;
use App\Providers\Filament\SicRcPanelProvider;

return [
    AppServiceProvider::class,
    HrPanelProvider::class,
    EmployeePanelProvider::class,
    KpiPanelProvider::class,
    SicRcPanelProvider::class,
];
