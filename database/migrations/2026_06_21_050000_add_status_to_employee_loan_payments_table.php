<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_loan_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_loan_payments', 'status')) {
                $table->string('status')->default('Posted')->after('processed_at');
            }

            if (! Schema::hasColumn('employee_loan_payments', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('employee_loan_payments', 'void_reason')) {
                $table->string('void_reason')->nullable()->after('voided_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_loan_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_loan_payments', 'void_reason')) {
                $table->dropColumn('void_reason');
            }

            if (Schema::hasColumn('employee_loan_payments', 'voided_at')) {
                $table->dropColumn('voided_at');
            }

            if (Schema::hasColumn('employee_loan_payments', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
