<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        collect([
            'super_admin',
            'hr_admin',
            'payroll_officer',
            'dtr_officer',
            'leave_officer',
            'memo_officer',
            'reports_viewer',
        ])->each(fn (string $role): Role => Role::findOrCreate($role, 'web'));

        User::query()
            ->whereIn('role', ['hr', 'admin'])
            ->get()
            ->each(fn (User $user): User => $user->assignRole('super_admin'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()
            ->whereIn('name', [
                'super_admin',
                'hr_admin',
                'payroll_officer',
                'dtr_officer',
                'leave_officer',
                'memo_officer',
                'reports_viewer',
            ])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
