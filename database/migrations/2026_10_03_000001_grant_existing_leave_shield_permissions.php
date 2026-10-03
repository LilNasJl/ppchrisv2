<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleId = DB::table('roles')->where('name', 'super_admin')->where('guard_name', 'web')->value('id');

        if (! $roleId) {
            return;
        }

        $existingPermissions = DB::table('permissions as permissions')
            ->join('role_has_permissions as grants', 'permissions.id', '=', 'grants.permission_id')
            ->where('grants.role_id', $roleId)
            ->where('permissions.guard_name', 'web')
            ->pluck('permissions.name');

        $permissionsToGrant = [];

        if ($existingPermissions->contains('View:LeaveApprovalWorkflows')) {
            $permissionsToGrant[] = 'Manage:LeaveWorkflow';
        }

        if ($existingPermissions->contains('Update:Leave')) {
            $permissionsToGrant[] = 'Review:Leave';
            $permissionsToGrant[] = 'Override:Leave';
        }

        foreach ($permissionsToGrant as $permission) {
            $permissionId = DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->value('id');

            if ($permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve permissions changed by administrators after this migration.
    }
};
