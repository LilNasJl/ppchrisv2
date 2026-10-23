<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_loan_adjustments', function (Blueprint $table): void {
            $table->decimal('previous_loan_amount', 12, 2)->nullable();
            $table->decimal('new_loan_amount', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_loan_adjustments', function (Blueprint $table): void {
            $table->dropColumn(['previous_loan_amount', 'new_loan_amount']);
        });
    }
};
