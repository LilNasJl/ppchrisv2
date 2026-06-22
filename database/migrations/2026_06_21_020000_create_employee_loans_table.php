<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amortization_start_payroll_period_id')
                ->nullable()
                ->constrained('payroll_periods')
                ->nullOnDelete();
            $table->string('loan_type')->default('Company Loan');
            $table->date('loan_date');
            $table->decimal('loan_amount', 12, 2)->default(0);
            $table->decimal('loan_interest', 12, 2)->default(0);
            $table->unsignedInteger('loan_terms_months')->default(1);
            $table->decimal('payment_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('schedule')->nullable();
            $table->string('status')->default('Active')->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('loan_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
