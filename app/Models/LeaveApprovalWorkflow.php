<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApprovalWorkflow extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function levels()
    {
        return $this->hasMany(LeaveApprovalLevel::class, 'workflow_id')->orderBy('sequence');
    }

    public function scopeLabel(): string
    {
        return collect([
            $this->employee_id ? Employee::find($this->employee_id)?->full_name : null,
            $this->designation_id ? Designation::find($this->designation_id)?->title : null,
            $this->branch_id ? Branch::find($this->branch_id)?->branch_name : null,
            $this->department_id ? Department::find($this->department_id)?->name : null,
        ])->filter()->join(' / ') ?: 'Company default';
    }

    public function specificity(): int
    {
        return ($this->employee_id ? 1000 : 0) + ($this->designation_id ? 100 : 0)
            + ($this->branch_id ? 10 : 0) + ($this->department_id ? 1 : 0);
    }
}
