<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_loans', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_loans', 'terms_basis')) {
                // Preserve the contract of loans created before monthly terms.
                $table->string('terms_basis')
                    ->default('payroll_period')
                    ->after('loan_terms_months');
            }

            if (! Schema::hasColumn('employee_loans', 'interest_rate')) {
                $table->decimal('interest_rate', 8, 4)
                    ->nullable()
                    ->after('loan_interest');
            }
        });

        Schema::table('employee_loan_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_loan_requests', 'terms_basis')) {
                $table->string('terms_basis')
                    ->default('payroll_period')
                    ->after('loan_terms_months');
            }

            if (! Schema::hasColumn('employee_loan_requests', 'interest_rate')) {
                $table->decimal('interest_rate', 8, 4)
                    ->nullable()
                    ->after('loan_interest');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_loan_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_loan_requests', 'interest_rate')) {
                $table->dropColumn('interest_rate');
            }

            if (Schema::hasColumn('employee_loan_requests', 'terms_basis')) {
                $table->dropColumn('terms_basis');
            }
        });

        Schema::table('employee_loans', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_loans', 'interest_rate')) {
                $table->dropColumn('interest_rate');
            }

            if (Schema::hasColumn('employee_loans', 'terms_basis')) {
                $table->dropColumn('terms_basis');
            }
        });
    }
};
