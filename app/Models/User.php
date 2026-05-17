<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'profile_photo_path', 'is_disabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function canAccessPanel(Panel $panel): bool
    {
        if ((bool) $this->is_disabled) {
            return false;
        }

        return match ($panel->getId()) {
            'hr' => $this->role === 'hr',
            'employee' => $this->role === 'employee' && ! (bool) $this->employee?->hasEndedEmployment(),
            default => false,
        };
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if ($user->isForceDeleting()) {
                $user->employee()->withTrashed()->forceDelete();

                return;
            }

            $user->employee()->delete();
        });

        static::restoring(function (User $user): void {
            $user->employee()->withTrashed()->restore();
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_disabled' => 'boolean',
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (blank($this->profile_photo_path)) {
            return null;
        }

        return '/storage/'.ltrim($this->profile_photo_path, '/');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id')->withTrashed();
    }

    public function deduction()
    {
        return $this->hasMany(Deduction::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
