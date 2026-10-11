<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table): void {
            $table->string('loan_quincena', 10)->nullable();
        });
        Schema::create('employee_loan_payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_loan_payment_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->timestamp('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
        });
        DB::table('employee_loan_payments')->orderBy('id')->chunkById(200, function ($payments): void {
            foreach ($payments as $payment) {
                DB::table('employee_loan_payment_events')->insert([
                    'employee_loan_payment_id' => $payment->id,
                    'status' => $payment->status, 'amount' => $payment->amount,
                    'balance_after' => $payment->balance_after,
                    'occurred_at' => $payment->voided_at ?: $payment->processed_at ?: $payment->created_at ?: now(),
                    'reason' => 'Existing payment imported into audit history'.($payment->void_reason ? ': '.$payment->void_reason : ''),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_payment_events');
        Schema::table('payroll_periods', fn (Blueprint $table) => $table->dropColumn('loan_quincena'));
    }
};
