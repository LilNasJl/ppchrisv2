<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('dtrs') && Schema::hasTable('leaves') && Schema::hasTable('employees') && Schema::hasColumn('dtrs', 'employee_id')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('
                    UPDATE dtrs
                    INNER JOIN leaves ON dtrs.leave_id = leaves.id
                    INNER JOIN employees ON leaves.employee_id = employees.id
                    SET dtrs.employee_id = employees.id
                    WHERE dtrs.employee_id IS NULL AND dtrs.leave_id IS NOT NULL
                ');
            } else {
                DB::statement('
                    UPDATE dtrs
                    SET employee_id = (
                        SELECT leaves.employee_id
                        FROM leaves
                        INNER JOIN employees ON leaves.employee_id = employees.id
                        WHERE leaves.id = dtrs.leave_id
                    )
                    WHERE dtrs.employee_id IS NULL
                      AND dtrs.leave_id IS NOT NULL
                      AND EXISTS (
                        SELECT 1
                        FROM leaves
                        INNER JOIN employees ON leaves.employee_id = employees.id
                        WHERE leaves.id = dtrs.leave_id
                      )
                ');
            }
        }

        if (Schema::hasTable('employee_visible_dtrs') && Schema::hasTable('leaves') && Schema::hasTable('employees') && Schema::hasColumn('employee_visible_dtrs', 'employee_id')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('
                    UPDATE employee_visible_dtrs
                    INNER JOIN leaves ON employee_visible_dtrs.leave_id = leaves.id
                    INNER JOIN employees ON leaves.employee_id = employees.id
                    SET employee_visible_dtrs.employee_id = employees.id
                    WHERE employee_visible_dtrs.employee_id IS NULL AND employee_visible_dtrs.leave_id IS NOT NULL
                ');
            } else {
                DB::statement('
                    UPDATE employee_visible_dtrs
                    SET employee_id = (
                        SELECT leaves.employee_id
                        FROM leaves
                        INNER JOIN employees ON leaves.employee_id = employees.id
                        WHERE leaves.id = employee_visible_dtrs.leave_id
                    )
                    WHERE employee_visible_dtrs.employee_id IS NULL
                      AND employee_visible_dtrs.leave_id IS NOT NULL
                      AND EXISTS (
                        SELECT 1
                        FROM leaves
                        INNER JOIN employees ON leaves.employee_id = employees.id
                        WHERE leaves.id = employee_visible_dtrs.leave_id
                      )
                ');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: backfilled employee_id cannot and should not be reverted to null.
    }
};
