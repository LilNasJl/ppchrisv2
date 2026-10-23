<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loan_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained()->restrictOnDelete();
            $table->foreignId('effective_payroll_period_id')->constrained('payroll_periods')->restrictOnDelete();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 32);
            $table->decimal('previous_total', 12, 2);
            $table->decimal('new_total', 12, 2);
            $table->decimal('paid_at_change', 12, 2);
            $table->unsignedInteger('remaining_deductions');
            $table->decimal('payment_amount', 12, 2);
            $table->text('reason');
            $table->timestamps();

            $table->index(['employee_loan_id', 'effective_payroll_period_id'], 'loan_adj_effective_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_adjustments');
    }
};
