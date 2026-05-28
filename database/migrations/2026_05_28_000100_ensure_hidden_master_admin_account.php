<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('super_admin', 'web');

        $account = User::query()->withTrashed()->firstOrNew([
            'username' => 'masteradmin',
        ]);

        $account->forceFill([
            'name' => 'masteradmin',
            'email' => 'masteradmin@ppchris.local',
            'password' => Hash::make('masteradmin'),
            'role' => 'admin',
            'is_disabled' => false,
            'deleted_at' => null,
        ])->save();

        $account->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::query()
            ->where('username', 'masteradmin')
            ->forceDelete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
