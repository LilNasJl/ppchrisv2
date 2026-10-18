<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricsSyncRequest extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date', 'result' => 'array', 'received_at' => 'datetime', 'executed_at' => 'datetime',
            'completed_at' => 'datetime', 'remote_acknowledged_at' => 'datetime', 'next_attempt_at' => 'datetime', 'deadline_at' => 'datetime'];
    }

    public function terminal(): bool
    {
        return in_array($this->status, ['success', 'failed'], true);
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }
}
