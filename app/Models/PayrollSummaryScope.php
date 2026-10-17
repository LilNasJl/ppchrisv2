<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSummaryScope extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'group_by',
        'included_ids',
        'branch_ids',
        'department_ids',
    ];

    protected $casts = [
        'included_ids' => 'array',
        'branch_ids' => 'array',
        'department_ids' => 'array',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    /**
     * Resolve selected branch IDs, falling back to legacy included_ids if branch_ids is not set.
     *
     * @return array<int>
     */
    public function resolvedBranchIds(): array
    {
        if (is_array($this->branch_ids) && $this->branch_ids !== []) {
            return array_values(array_unique(array_filter(array_map('intval', $this->branch_ids))));
        }

        if (($this->group_by ?? 'branch') === 'branch' && is_array($this->included_ids) && $this->included_ids !== []) {
            return array_values(array_unique(array_filter(array_map('intval', $this->included_ids))));
        }

        return [];
    }

    /**
     * Resolve selected department IDs, falling back to legacy included_ids if department_ids is not set.
     *
     * @return array<int>
     */
    public function resolvedDepartmentIds(): array
    {
        if (is_array($this->department_ids) && $this->department_ids !== []) {
            return array_values(array_unique(array_filter(array_map('intval', $this->department_ids))));
        }

        if (($this->group_by ?? 'branch') === 'department' && is_array($this->included_ids) && $this->included_ids !== []) {
            return array_values(array_unique(array_filter(array_map('intval', $this->included_ids))));
        }

        return [];
    }
}
