<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('super_admin', 'web');

        $existingDefaultSuperAdmin = User::query()
            ->where('username', $this->defaultUsername())
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->exists();

        if ($existingDefaultSuperAdmin) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        $this->removeExistingSystemAccounts();

        $user = $this->defaultSuperAdmin();

        $user->forceFill([
            'role' => 'admin',
            'is_disabled' => false,
        ])->save();

        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep the account intact on rollback so the system is not accidentally locked.
    }

    private function defaultSuperAdmin(): User
    {
        $username = $this->defaultUsername();

        $user = User::withTrashed()
            ->where('username', $username)
            ->first();

        if ($user) {
            if (method_exists($user, 'restore') && $user->trashed()) {
                $user->restore();
            }

            $user->forceFill([
                'name' => env('HRIS_DEFAULT_SUPER_ADMIN_NAME', 'Super Admin'),
                'email' => env('HRIS_DEFAULT_SUPER_ADMIN_EMAIL', 'super_admin@ppchris.local'),
                'password' => Hash::make(env('HRIS_DEFAULT_SUPER_ADMIN_PASSWORD', 'password123')),
            ])->save();

            return $user;
        }

        return User::create([
            'name' => env('HRIS_DEFAULT_SUPER_ADMIN_NAME', 'Super Admin'),
            'username' => $username,
            'email' => env('HRIS_DEFAULT_SUPER_ADMIN_EMAIL', 'super_admin@ppchris.local'),
            'password' => Hash::make(env('HRIS_DEFAULT_SUPER_ADMIN_PASSWORD', 'password123')),
            'role' => 'admin',
            'is_disabled' => false,
        ]);
    }

    private function removeExistingSystemAccounts(): void
    {
        User::withTrashed()
            ->where('username', '!=', $this->defaultUsername())
            ->where(function ($query): void {
                $query
                    ->whereIn('role', ['admin', 'hr'])
                    ->orWhereIn('username', ['jlladroma', 'superadmin']);
            })
            ->get()
            ->each(function (User $user): void {
                $user->syncRoles([]);
                $user->forceFill([
                    'is_disabled' => true,
                    'deleted_at' => now(),
                ])->save();
            });
    }

    private function defaultUsername(): string
    {
        $username = Str::of(env('HRIS_DEFAULT_SUPER_ADMIN_USERNAME', 'super_admin'))
            ->trim()
            ->toString();

        $username = preg_replace('/\s+/', '', $username);

        return filled($username) ? $username : 'super_admin';
    }
};
