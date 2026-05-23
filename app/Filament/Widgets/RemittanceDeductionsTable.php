<?php

namespace App\Filament\Widgets;

use App\Models\Deduction;

class RemittanceDeductionsTable extends DeductionTypesTable
{
    protected static ?string $heading = 'Remittances';

    protected function category(): string
    {
        return Deduction::CATEGORY_REMITTANCE;
    }
}
