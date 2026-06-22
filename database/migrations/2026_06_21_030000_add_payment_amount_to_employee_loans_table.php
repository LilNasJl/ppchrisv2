<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_loans', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_loans', 'payment_amount')) {
                $table->decimal('payment_amount', 12, 2)
                    ->default(0)
                    ->after('loan_terms_months');
            }
        });

        DB::table('employee_loans')
            ->where('schedule', 'Every payroll period')
            ->update(['schedule' => 'Every Payroll']);

        DB::table('employee_loans')
            ->where('payment_amount', '<=', 0)
            ->update([
                'payment_amount' => DB::raw('ROUND((loan_amount + loan_interest) / GREATEST(loan_terms_months, 1), 2)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('employee_loans', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_loans', 'payment_amount')) {
                $table->dropColumn('payment_amount');
            }
        });
    }
};
