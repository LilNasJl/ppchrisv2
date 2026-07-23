<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class EmployeeEmploymentTypeChange extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'employee_id',
        'previous_type',
        'employment_type',
        'effective_date',
        'explanation',
        'changed_by_user_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
