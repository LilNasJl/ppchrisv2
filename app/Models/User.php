<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'profile_photo_path', 'is_disabled', 'can_view_payroll'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicUuid, HasRoles, Notifiable, SoftDeletes;

    public function canAccessPanel(Panel $panel): bool
    {
        if ((bool) $this->is_disabled) {
            return false;
        }

        return match ($panel->getId()) {
            'hr' => in_array($this->role, ['hr', 'admin'], true),
            'employee' => $this->role === 'employee' && ! (bool) $this->employee?->hasEndedEmployment(),
            default => false,
        };
    }

    public function canImpersonate(): bool
    {
        return in_array($this->role, ['hr', 'admin'], true)
            && ! (bool) $this->is_disabled;
    }

    public function canBeImpersonated(): bool
    {
        return in_array($this->role, ['hr', 'admin'], true)
            && ! (bool) $this->is_disabled;
    }

    public static function normalizeUsername(?string $username): ?string
    {
        if (blank($username)) {
            return null;
        }

        return Str::of((string) $username)
            ->trim()
            ->toString();
    }

    public static function companyUsernameFromUid(?string $uid): ?string
    {
        if (blank($uid)) {
            return null;
        }

        $uid = Str::of((string) $uid)
            ->replace('PF', '')
            ->replace('-', '')
            ->trim()
            ->toString();

        return 'PF'.str_pad((string) ((int) $uid), 4, '0', STR_PAD_LEFT);
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
            'can_view_payroll' => 'boolean',
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (blank($this->profile_photo_path)) {
            return null;
        }

        $path = Str::of((string) $this->profile_photo_path)
            ->replace('\\', '/')
            ->trim()
            ->ltrim('/')
            ->toString();

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        if (Str::startsWith($path, 'public/')) {
            $path = Str::after($path, 'public/');
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        if (Str::startsWith($path, 'profile-photos/')) {
            $profilePhotoPath = '/profile-photos/'.rawurlencode(basename($path));
            $rootUrl = app()->runningInConsole()
                ? rtrim((string) config('app.url'), '/')
                : request()->getSchemeAndHttpHost();

            return $rootUrl.$profilePhotoPath;
        }

        return Storage::disk('public')->url($path);
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

    public function accountStatusHistories()
    {
        return $this->hasMany(AccountStatusHistory::class)->latest();
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
