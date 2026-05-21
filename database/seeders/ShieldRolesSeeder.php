<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldRolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = collect([
            'hr_admin',
            'payroll_officer',
            'dtr_officer',
            'leave_officer',
            'memo_officer',
            'reports_viewer',
        ])->mapWithKeys(fn (string $role): array => [$role => Role::findOrCreate($role, 'web')]);

        $roles['hr_admin']->syncPermissions(Permission::query()->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
