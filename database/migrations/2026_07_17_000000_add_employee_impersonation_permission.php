<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            return;
        }

        Permission::findOrCreate('Impersonate:Employee', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            return;
        }

        Permission::query()
            ->where('name', 'Impersonate:Employee')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
