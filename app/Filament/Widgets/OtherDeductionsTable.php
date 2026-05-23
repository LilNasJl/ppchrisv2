<?php

namespace App\Filament\Widgets;

use App\Models\Deduction;

class OtherDeductionsTable extends DeductionTypesTable
{
    protected static ?string $heading = 'Other Deductions';

    protected function category(): string
    {
        return Deduction::CATEGORY_OTHER;
    }
}
