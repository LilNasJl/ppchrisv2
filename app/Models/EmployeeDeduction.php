<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDeduction extends Model
{
    protected $fillable = [
        'employee_id',
        'deduction_id',
        'amount',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'deduction_id' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function deduction()
    {
        return $this->belongsTo(Deduction::class, 'deduction_id');
    }
}
