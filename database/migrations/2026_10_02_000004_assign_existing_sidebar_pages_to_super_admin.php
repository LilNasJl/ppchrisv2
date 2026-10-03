<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'View:BirthdayCalendar',
        'View:DatabaseManagement',
        'View:Kpi',
        'View:LoanManagement',
        'View:ManageBiometrics',
        'View:ThirteenthMonthPay',
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

        foreach (DB::table('permissions')->where('guard_name', 'web')->whereIn('name', self::PERMISSIONS)->pluck('id') as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        // Preserve any role grants made after this migration.
    }
};
