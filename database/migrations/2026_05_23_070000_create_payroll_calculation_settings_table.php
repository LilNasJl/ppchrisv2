<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_calculation_settings')) {
            return;
        }

        Schema::create('payroll_calculation_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('regular_work_days_per_month', 8, 2)->default(26);
            $table->decimal('regular_half_month_days', 8, 2)->default(13);
            $table->decimal('work_hours_per_day', 8, 2)->default(8);
            $table->decimal('half_day_work_day_value', 8, 2)->default(0.5);
            $table->decimal('overtime_rate_multiplier', 8, 2)->default(1);
            $table->decimal('regular_holiday_rate', 8, 2)->default(200);
            $table->decimal('special_holiday_rate', 8, 2)->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_calculation_settings');
    }
};
