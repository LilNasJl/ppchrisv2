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
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach (self::PAGES as $page) {
            DB::table('permissions')->insertOrIgnore([
                'name' => 'View:'.$page,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Preserve permissions that may have been granted after this migration.
    }
};
