<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['employee_id', 'username', 'password', 'station_biometrics', 'biometric_devices', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class SicRcAccount extends Authenticatable implements FilamentUser, HasName
{
    use HasPublicUuid;
    use SoftDeletes;

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'sicrc'
            && $this->is_active
            && ! $this->trashed();
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public function canBeImpersonated(): bool
    {
        return $this->is_active && ! $this->trashed();
    }

    public function branchAssignments(): array
    {
        return collect($this->biometric_devices ?: [])
            ->filter(fn (mixed $assignment): bool => is_array($assignment) && filled($assignment['branch_id'] ?? null))
            ->map(fn (array $assignment): array => [
                'branch_id' => (int) $assignment['branch_id'],
                'branch_name' => (string) ($assignment['branch_name'] ?? ''),
            ])
            ->values()
            ->all();
    }

    public function branchDeviceAssignments(): array
    {
        return $this->branchAssignments();
    }

    public function assignedBranchIds(): array
    {
        return collect($this->branchAssignments())
            ->pluck('branch_id')
            ->filter()
            ->map(fn (mixed $branchId): int => (int) $branchId)
            ->unique()
            ->values()
            ->all();
    }

    public function assignedBranchCount(): int
    {
        return count($this->assignedBranchIds());
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function dtrSubmissions()
    {
        return $this->hasMany(DtrSubmission::class);
    }

    public function assignedDtrChangeRequests()
    {
        return $this->hasMany(DtrChangeRequest::class, 'assigned_sic_rc_account_id');
    }

    public function reviewedDtrChangeRequests()
    {
        return $this->hasMany(DtrChangeRequest::class, 'reviewed_by_sic_rc_account_id');
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'biometric_devices' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }
}
