<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    protected array $tables = [
        'users',
        'employees',
        'branches',
        'departments',
        'designations',
        'deductions',
        'employee_deductions',
        'leaves',
        'dtrs',
        'payrolls',
        'payroll_periods',
        'payroll_snapshots',
        'payroll_calculation_settings',
        'payroll_period_employee_exclusions',
        'holiday_types',
        'holidays',
        'activities',
        'announcements',
        'memo_types',
        'memos',
        'tickets',
        'action_histories',
        'payroll_signatories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'uuid')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->uuid('uuid')->nullable()->after('id')->unique();
            });

            DB::table($tableName)
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($tableName): void {
                    foreach ($rows as $row) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update(['uuid' => (string) Str::uuid()]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'uuid')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('uuid');
            });
        }
    }
};
