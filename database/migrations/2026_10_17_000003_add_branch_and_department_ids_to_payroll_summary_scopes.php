<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_summary_scopes', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_summary_scopes', 'branch_ids')) {
                $table->json('branch_ids')->nullable()->after('included_ids');
            }

            if (! Schema::hasColumn('payroll_summary_scopes', 'department_ids')) {
                $table->json('department_ids')->nullable()->after('branch_ids');
            }
        });

        // Backfill existing scopes safely without modifying or deleting any existing records
        $scopes = DB::table('payroll_summary_scopes')->get();

        foreach ($scopes as $scope) {
            $included = is_string($scope->included_ids)
                ? json_decode($scope->included_ids, true)
                : (is_array($scope->included_ids) ? $scope->included_ids : []);

            $included = is_array($included) ? array_values(array_map('intval', $included)) : [];

            $branchIds = $scope->group_by === 'branch' ? $included : [];
            $departmentIds = $scope->group_by === 'department' ? $included : [];

            DB::table('payroll_summary_scopes')
                ->where('id', $scope->id)
                ->update([
                    'branch_ids' => json_encode($branchIds),
                    'department_ids' => json_encode($departmentIds),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('payroll_summary_scopes', function (Blueprint $table) {
            $columns = array_filter(
                ['branch_ids', 'department_ids'],
                fn (string $column) => Schema::hasColumn('payroll_summary_scopes', $column)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
