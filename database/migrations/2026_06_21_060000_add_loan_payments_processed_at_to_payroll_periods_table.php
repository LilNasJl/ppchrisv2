<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_periods', 'loan_payments_processed_at')) {
                $table->timestamp('loan_payments_processed_at')->nullable()->after('deductions_processed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table): void {
            if (Schema::hasColumn('payroll_periods', 'loan_payments_processed_at')) {
                $table->dropColumn('loan_payments_processed_at');
            }
        });
    }
};
