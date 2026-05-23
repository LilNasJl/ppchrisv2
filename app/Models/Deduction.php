<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deduction extends Model
{
    use SoftDeletes;

    public const CATEGORY_COMPANY = 'company';

    public const CATEGORY_REMITTANCE = 'remittance';

    public const CATEGORY_OTHER = 'other';

    public const TERM_PERMANENT = 'permanent';

    public const TERM_FIXED = 'fixed';

    public const COMPANY_TITLES = [
        'SHORTAGES',
        'COMPANY UNIFORM',
    ];

    public const REMITTANCE_TITLES = [
        'SSS LOAN',
        'SSS EE',
        'HDMF LOAN',
        'HDMF EE',
        'PHIC EE',
    ];

    protected $fillable = [
        'title',
        'description',
        'amount',
        'category',
        'term_type',
        'term_periods',
    ];

    protected $casts = [
        'term_periods' => 'integer',
    ];

    public static function defaultTitles(): array
    {
        return [
            ...self::COMPANY_TITLES,
            ...self::REMITTANCE_TITLES,
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_COMPANY => 'Company Deductions',
            self::CATEGORY_REMITTANCE => 'Remittances',
            self::CATEGORY_OTHER => 'Other Deductions',
        ];
    }

    public static function termTypeOptions(): array
    {
        return [
            self::TERM_PERMANENT => 'Permanent',
            self::TERM_FIXED => 'Fixed number of payroll periods',
        ];
    }

    public static function categoryForTitle(string $title): string
    {
        $title = strtoupper(trim($title));

        if (in_array($title, self::COMPANY_TITLES, true)) {
            return self::CATEGORY_COMPANY;
        }

        if (in_array($title, self::REMITTANCE_TITLES, true)) {
            return self::CATEGORY_REMITTANCE;
        }

        return self::CATEGORY_OTHER;
    }

    public function employeeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class, 'deduction_id');
    }
}
