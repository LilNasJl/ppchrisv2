<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class EmployeeDeduction extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'employee_id',
        'deduction_id',
        'amount',
        'term_type',
        'term_periods',
        'remaining_terms',
        'active',
        'completed_at',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'deduction_id' => 'integer',
        'amount' => 'decimal:2',
        'term_periods' => 'integer',
        'remaining_terms' => 'integer',
        'active' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function deduction()
    {
        return $this->belongsTo(Deduction::class, 'deduction_id');
    }

    public function isActiveForPayroll(): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->term_type === Deduction::TERM_FIXED) {
            return (int) ($this->remaining_terms ?? 0) > 0;
        }

        return true;
    }
}
