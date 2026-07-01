<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loan_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('employee_loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_loan_id', 'payroll_period_id'], 'employee_loan_period_unique');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_payments');
    }
};
