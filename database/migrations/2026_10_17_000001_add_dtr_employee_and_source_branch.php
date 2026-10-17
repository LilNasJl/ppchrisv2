<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            if (! Schema::hasColumn('dtrs', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            }
            if (! Schema::hasColumn('dtrs', 'source_branch_id')) {
                $table->foreignId('source_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
        });

        if (Schema::hasTable('dtrs') && ! Schema::hasIndex('dtrs', 'dtrs_period_employee_idx')) {
            Schema::table('dtrs', function (Blueprint $table): void {
                $table->index(['payroll_period_id', 'employee_id'], 'dtrs_period_employee_idx');
            });
        }

        // Backfill existing dtr records safely without modifying any existing punches or attendance records
        if (Schema::hasTable('dtrs') && Schema::hasColumn('dtrs', 'employee_id') && Schema::hasTable('employees')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('UPDATE dtrs d JOIN employees e ON (e.fingerprint_id = d.fingerprint_id OR e.uid = d.fingerprint_id) SET d.employee_id = e.id WHERE d.employee_id IS NULL AND (d.fingerprint_id IS NOT NULL AND d.fingerprint_id != "")');
                if (Schema::hasColumn('dtrs', 'source_branch_id')) {
                    DB::statement('UPDATE dtrs SET source_branch_id = branch_id WHERE source_branch_id IS NULL AND branch_id IS NOT NULL');
                }
            } else {
                DB::table('employees')
                    ->select(['id', 'uid', 'fingerprint_id'])
                    ->where(fn ($q) => $q->whereNotNull('fingerprint_id')->orWhereNotNull('uid'))
                    ->chunkById(100, function ($employees) {
                        foreach ($employees as $emp) {
                            $idents = array_filter([(string) $emp->fingerprint_id, (string) $emp->uid]);
                            if ($idents !== []) {
                                DB::table('dtrs')->whereNull('employee_id')->whereIn('fingerprint_id', $idents)->update(['employee_id' => $emp->id]);
                            }
                        }
                    });
                if (Schema::hasColumn('dtrs', 'source_branch_id')) {
                    DB::table('dtrs')->whereNull('source_branch_id')->whereNotNull('branch_id')->update([
                        'source_branch_id' => DB::raw('branch_id'),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            if (Schema::hasTable('dtrs') && Schema::hasIndex('dtrs', 'dtrs_period_employee_idx')) {
                $table->dropIndex('dtrs_period_employee_idx');
            }
            if (Schema::hasColumn('dtrs', 'source_branch_id')) {
                $table->dropConstrainedForeignId('source_branch_id');
            }
            if (Schema::hasColumn('dtrs', 'employee_id')) {
                $table->dropConstrainedForeignId('employee_id');
            }
        });
    }
};
