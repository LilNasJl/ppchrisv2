<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SicRcDtrImport extends Model
{
    use HasPublicUuid;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_NO_CHANGES = 'no_changes';

    protected $fillable = [
        'imported_by_employee_id',
        'branch_id',
        'payroll_period_id',
        'batch_id',
        'import_name',
        'source_filename',
        'source_file_hash',
        'total_rows',
        'imported_rows',
        'skipped_rows',
        'failed_rows',
        'status',
        'message',
        'errors',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows' => 'integer',
            'failed_rows' => 'integer',
            'errors' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function importedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'imported_by_employee_id')->withTrashed();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
