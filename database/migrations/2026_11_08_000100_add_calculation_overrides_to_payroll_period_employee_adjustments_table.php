<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_period_employee_adjustments')) {
            return;
        }

        Schema::table('payroll_period_employee_adjustments', function (Blueprint $table): void {
            $columns = [
                'days_worked_override' => ['type' => 'decimal', 'total' => 8, 'places' => 2],
                'allowance_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'overtime_amount_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'shift3_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'regular_holiday_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'special_holiday_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'undertime_amount_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'late_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'absent_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'halfday_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'uniform_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'other_deductions_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'sss_loan_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'sss_ee_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'hdmf_loan_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'hdmf_ee_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
                'phic_ee_override' => ['type' => 'decimal', 'total' => 12, 'places' => 2],
            ];

            foreach ($columns as $name => $meta) {
                if (! Schema::hasColumn('payroll_period_employee_adjustments', $name)) {
                    $table->decimal($name, $meta['total'], $meta['places'])->nullable();
                }
            }

            if (! Schema::hasColumn('payroll_period_employee_adjustments', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_period_employee_adjustments')) {
            return;
        }

        $dropColumns = array_filter([
            'days_worked_override',
            'allowance_override',
            'overtime_amount_override',
            'shift3_override',
            'regular_holiday_override',
            'special_holiday_override',
            'undertime_amount_override',
            'late_override',
            'absent_override',
            'halfday_override',
            'uniform_override',
            'other_deductions_override',
            'sss_loan_override',
            'sss_ee_override',
            'hdmf_loan_override',
            'hdmf_ee_override',
            'phic_ee_override',
            'notes',
        ], fn (string $col): bool => Schema::hasColumn('payroll_period_employee_adjustments', $col));

        if ($dropColumns !== []) {
            Schema::table('payroll_period_employee_adjustments', function (Blueprint $table) use ($dropColumns): void {
                $table->dropColumn($dropColumns);
            });
        }
    }
};
