<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class ThirteenthMonthRelease extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'year',
        'segment',
        'basis_amount',
        'released_amount',
        'calculation_data',
        'released_at',
        'released_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'basis_amount' => 'decimal:2',
        'released_amount' => 'decimal:2',
        'calculation_data' => 'array',
        'released_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by')->withTrashed();
    }
}
