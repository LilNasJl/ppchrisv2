<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class PayrollSnapshot extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'branch_id',
        'row_number',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
