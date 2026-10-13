<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing approved leave retains its historic paid treatment.
        if (! Schema::hasColumn('leaves', 'pay_treatment')) {
            Schema::table('leaves', function (Blueprint $table): void {
                $table->string('pay_treatment', 16)->default('paid');
            });
        }

        foreach ($this->indexes() as [$table, $name, $columns]) {
            if (Schema::hasTable($table) && ! Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        // Retain the pay-treatment data on rollback; remove only added indexes.
        foreach ($this->indexes() as [$table, $name]) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }

    private function indexes(): array
    {
        return [
            ['leaves', 'leaves_employee_status_dates_idx', ['employee_id', 'status', 'leave_from', 'leave_to']],
            ['dtrs', 'dtrs_scope_date_reconcile_idx', ['payroll_period_id', 'branch_id', 'fingerprint_id', 'date_in']],
            ['dtrs', 'dtrs_branch_fp_date_idx', ['branch_id', 'fingerprint_id', 'date_in']],
            ['employee_visible_dtrs', 'evdtrs_branch_fp_date_idx', ['branch_id', 'fingerprint_id', 'date_in']],
        ];
    }
};
