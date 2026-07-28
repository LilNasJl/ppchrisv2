<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['username', 'password', 'scope_type', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class KpiAccount extends Authenticatable implements FilamentUser, HasName
{
    use HasPublicUuid;
    use SoftDeletes;

    public const SCOPE_BRANCH = 'branch';

    public const SCOPE_DEPARTMENT = 'department';

    public const SCOPE_EMPLOYEE = 'employee';

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'kpi'
            && $this->is_active
            && ! $this->trashed();
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public static function scopeOptions(): array
    {
        return [
            self::SCOPE_BRANCH => 'Branch',
            self::SCOPE_DEPARTMENT => 'Department',
            self::SCOPE_EMPLOYEE => 'Employee',
        ];
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'kpi_account_branch');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'kpi_account_department');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'kpi_account_employee');
    }

    public function ratingCycles(): HasMany
    {
        return $this->hasMany(KpiRatingCycle::class);
    }

    public function getScopeLabelAttribute(): string
    {
        return static::scopeOptions()[$this->scope_type] ?? ucfirst((string) $this->scope_type);
    }

    public function getScopeSummaryAttribute(): string
    {
        $names = match ($this->scope_type) {
            self::SCOPE_BRANCH => $this->branches->pluck('branch_name'),
            self::SCOPE_DEPARTMENT => $this->departments->map(
                fn (Department $department): string => $department->acronym ?: $department->name,
            ),
            self::SCOPE_EMPLOYEE => $this->employees->pluck('full_name'),
            default => collect(),
        };

        return $names->filter()->implode(', ') ?: 'No assignment';
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
