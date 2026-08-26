<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dtr_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->foreignId('assigned_sic_rc_account_id')->nullable()->constrained('sic_rc_accounts')->nullOnDelete();
            $table->foreignId('reviewed_by_sic_rc_account_id')->nullable()->constrained('sic_rc_accounts')->nullOnDelete();
            $table->string('employee_name_snapshot');
            $table->string('employee_company_id_snapshot')->nullable();
            $table->string('branch_name_snapshot');
            $table->string('payroll_period_title_snapshot');
            $table->date('date_from');
            $table->date('date_to');
            $table->string('request_type', 100);
            $table->text('description');
            $table->string('status', 20)->default('Pending');
            $table->text('reviewer_remarks')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status', 'created_at']);
            $table->index(['branch_id', 'status', 'created_at']);
            $table->index(['assigned_sic_rc_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dtr_change_requests');
    }
};
