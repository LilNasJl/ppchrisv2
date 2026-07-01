<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HrAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('super_admin', 'web');
        $username = $this->defaultUsername();

        $existingDefaultSuperAdmin = User::query()
            ->where('username', $username)
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->exists();

        if (! $existingDefaultSuperAdmin) {
            $this->removeExistingSystemAccounts($username);

            $user = User::withTrashed()
                ->where('username', $username)
                ->first();

            if ($user && method_exists($user, 'restore') && $user->trashed()) {
                $user->restore();
            }

            $user ??= new User(['username' => $username]);

            $user->forceFill([
                'name' => env('HRIS_DEFAULT_SUPER_ADMIN_NAME', 'Super Admin'),
                'email' => env('HRIS_DEFAULT_SUPER_ADMIN_EMAIL', 'super_admin@ppchris.local'),
                'password' => Hash::make(env('HRIS_DEFAULT_SUPER_ADMIN_PASSWORD', 'password123')),
                'role' => 'admin',
                'is_disabled' => false,
                'deleted_at' => null,
            ])->save();

            $user->syncRoles([$role]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function removeExistingSystemAccounts(string $defaultUsername): void
    {
        User::withTrashed()
            ->where('username', '!=', $defaultUsername)
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
}
