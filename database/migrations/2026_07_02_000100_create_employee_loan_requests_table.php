<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loan_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('preferred_start_payroll_period_id')
                ->nullable()
                ->constrained('payroll_periods')
                ->nullOnDelete();
            $table->foreignId('approved_employee_loan_id')
                ->nullable()
                ->unique()
                ->constrained('employee_loans')
                ->nullOnDelete();
            $table->string('loan_type');
            $table->date('request_date');
            $table->decimal('loan_amount', 12, 2);
            $table->decimal('loan_interest', 12, 2)->default(0);
            $table->unsignedInteger('loan_terms_months');
            $table->decimal('payment_amount', 12, 2);
            $table->string('schedule');
            $table->text('reason');
            $table->string('status')->default('Pending')->index();
            $table->text('hr_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'status'], 'employee_loan_requests_employee_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_requests');
    }
};
