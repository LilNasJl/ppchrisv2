<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_calculation_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_calculation_settings', 'holiday_overtime_premium_rate')) {
                $table->decimal('holiday_overtime_premium_rate', 8, 2)->default(30)->after('special_holiday_rate');
            }

            if (! Schema::hasColumn('payroll_calculation_settings', 'unworked_regular_holiday_pay_enabled')) {
                $table->boolean('unworked_regular_holiday_pay_enabled')->default(true)->after('holiday_overtime_premium_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_calculation_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('payroll_calculation_settings', 'unworked_regular_holiday_pay_enabled')) {
                $table->dropColumn('unworked_regular_holiday_pay_enabled');
            }

            if (Schema::hasColumn('payroll_calculation_settings', 'holiday_overtime_premium_rate')) {
                $table->dropColumn('holiday_overtime_premium_rate');
            }
        });
    }
};
