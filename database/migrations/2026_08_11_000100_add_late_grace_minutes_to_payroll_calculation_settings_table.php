<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_calculation_settings')
            || Schema::hasColumn('payroll_calculation_settings', 'late_grace_minutes')) {
            return;
        }

        Schema::table('payroll_calculation_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('late_grace_minutes')
                ->default(2)
                ->after('work_hours_per_day');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_calculation_settings')
            || ! Schema::hasColumn('payroll_calculation_settings', 'late_grace_minutes')) {
            return;
        }

        Schema::table('payroll_calculation_settings', function (Blueprint $table): void {
            $table->dropColumn('late_grace_minutes');
        });
    }
};
