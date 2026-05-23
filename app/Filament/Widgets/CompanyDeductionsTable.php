<?php

namespace App\Filament\Widgets;

use App\Models\Deduction;

class CompanyDeductionsTable extends DeductionTypesTable
{
    protected static ?string $heading = 'Company Deductions';

    protected function category(): string
    {
        return Deduction::CATEGORY_COMPANY;
    }
}
