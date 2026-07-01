<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_period_employee_adjustments')) {
            return;
        }

        Schema::create('payroll_period_employee_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('salary_adjustment', 12, 2)->default(0);
            $table->decimal('shortages', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id'], 'payroll_period_employee_adjustment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_period_employee_adjustments');
    }
};
