<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // 1. Handle employee_loans referencing 'sample'
            if (Schema::hasTable('employee_loans')) {
                $sampleLoanIds = DB::table('employee_loans')
                    ->whereRaw('LOWER(TRIM(loan_type)) = ?', ['sample'])
                    ->pluck('id')
                    ->all();

                if (! empty($sampleLoanIds)) {
                    if (Schema::hasTable('employee_loan_requests')) {
                        DB::table('employee_loan_requests')
                            ->whereIn('approved_employee_loan_id', $sampleLoanIds)
                            ->update(['approved_employee_loan_id' => null]);
                    }

                    if (Schema::hasTable('employee_loan_payments')) {
                        DB::table('employee_loan_payments')
                            ->whereIn('employee_loan_id', $sampleLoanIds)
                            ->delete();
                    }

                    if (Schema::hasTable('employee_loan_adjustments')) {
                        DB::table('employee_loan_adjustments')
                            ->whereIn('employee_loan_id', $sampleLoanIds)
                            ->delete();
                    }

                    DB::table('employee_loans')
                        ->whereIn('id', $sampleLoanIds)
                        ->delete();
                }
            }

            // 2. Handle employee_loan_requests referencing 'sample'
            if (Schema::hasTable('employee_loan_requests')) {
                DB::table('employee_loan_requests')
                    ->whereRaw('LOWER(TRIM(loan_type)) = ?', ['sample'])
                    ->delete();
            }

            // 3. Remove 'sample' from loan_types table
            if (Schema::hasTable('loan_types')) {
                DB::table('loan_types')
                    ->whereRaw('LOWER(TRIM(name)) = ?', ['sample'])
                    ->delete();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('loan_types')) {
            $exists = DB::table('loan_types')
                ->whereRaw('LOWER(TRIM(name)) = ?', ['sample'])
                ->exists();

            if (! $exists) {
                DB::table('loan_types')->insert([
                    'name' => 'sample',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
