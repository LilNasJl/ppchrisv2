<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PAGES = [
        'AccountLogs',
        'CompanyHandbook',
        'DtrProofSubmissions',
        'IncidentReport',
        'JobDescriptions',
        'KpiCategories',
        'KpiDepartmentConfiguration',
        'KpiIndicators',
        'LeaveApprovalWorkflows',
        'OffboardingAndClearanceFlow',
        'OrganizationalStructure',
        'OvertimeManagement',
        'StationManagers',
        'ViewOnFieldDtrSubmission',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleId = DB::table('roles')->where('name', 'super_admin')->where('guard_name', 'web')->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', array_map(fn (string $page): string => 'View:'.$page, self::PAGES))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        // Keep role assignments that administrators may now rely on.
    }
};
