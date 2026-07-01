<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayEmployeeExclusion extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'holiday_id',
        'employee_id',
        'occurrence_date',
        'applies_every_year',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
        'applies_every_year' => 'boolean',
    ];

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
