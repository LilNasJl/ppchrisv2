<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dtrs') && ! Schema::hasIndex('dtrs', 'dtrs_period_branch_fingerprint_idx')) {
            Schema::table('dtrs', function (Blueprint $table): void {
                $table->index(['payroll_period_id', 'branch_id', 'fingerprint_id'], 'dtrs_period_branch_fingerprint_idx');
            });
        }

        if (Schema::hasTable('employee_deductions')) {
            if (! Schema::hasIndex('employee_deductions', 'emp_deductions_emp_ded_idx')) {
                Schema::table('employee_deductions', function (Blueprint $table): void {
                    $table->index(['employee_id', 'deduction_id'], 'emp_deductions_emp_ded_idx');
                });
            }

            if (! Schema::hasIndex('employee_deductions', 'emp_deductions_emp_active_idx')) {
                Schema::table('employee_deductions', function (Blueprint $table): void {
                    $table->index(['employee_id', 'active'], 'emp_deductions_emp_active_idx');
                });
            }
        }

        if (Schema::hasTable('employees')) {
            if (! Schema::hasIndex('employees', 'employees_branch_id_idx')) {
                Schema::table('employees', function (Blueprint $table): void {
                    $table->index('branch_id', 'employees_branch_id_idx');
                });
            }

            if (! Schema::hasIndex('employees', 'employees_user_id_idx')) {
                Schema::table('employees', function (Blueprint $table): void {
                    $table->index('user_id', 'employees_user_id_idx');
                });
            }

            if (! Schema::hasIndex('employees', 'employees_lastname_firstname_idx')) {
                Schema::table('employees', function (Blueprint $table): void {
                    $table->index(['lastname', 'firstname'], 'employees_lastname_firstname_idx');
                });
            }
        }

        if (Schema::hasTable('holidays')) {
            if (! Schema::hasIndex('holidays', 'holidays_date_idx')) {
                Schema::table('holidays', function (Blueprint $table): void {
                    $table->index('date', 'holidays_date_idx');
                });
            }

            if (! Schema::hasIndex('holidays', 'holidays_recurring_month_day_idx')) {
                Schema::table('holidays', function (Blueprint $table): void {
                    $table->index(['is_recurring', 'month_day'], 'holidays_recurring_month_day_idx');
                });
            }
        }

        if (Schema::hasTable('payroll_periods')) {
            if (! Schema::hasIndex('payroll_periods', 'payroll_periods_date_start_idx')) {
                Schema::table('payroll_periods', function (Blueprint $table): void {
                    $table->index('date_start', 'payroll_periods_date_start_idx');
                });
            }

            if (! Schema::hasIndex('payroll_periods', 'payroll_periods_locked_date_start_idx')) {
                Schema::table('payroll_periods', function (Blueprint $table): void {
                    $table->index(['is_locked', 'date_start'], 'payroll_periods_locked_date_start_idx');
                });
            }
        }

        if (Schema::hasTable('employee_loan_payments') && ! Schema::hasIndex('employee_loan_payments', 'emp_loan_payments_loan_status_idx')) {
            Schema::table('employee_loan_payments', function (Blueprint $table): void {
                $table->index(['employee_loan_id', 'status'], 'emp_loan_payments_loan_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dtrs') && Schema::hasIndex('dtrs', 'dtrs_period_branch_fingerprint_idx')) {
            Schema::table('dtrs', function (Blueprint $table): void {
                $table->dropIndex('dtrs_period_branch_fingerprint_idx');
            });
        }

        if (Schema::hasTable('employee_deductions')) {
            if (Schema::hasIndex('employee_deductions', 'emp_deductions_emp_ded_idx')) {
                Schema::table('employee_deductions', function (Blueprint $table): void {
                    $table->dropIndex('emp_deductions_emp_ded_idx');
                });
            }

            if (Schema::hasIndex('employee_deductions', 'emp_deductions_emp_active_idx')) {
                Schema::table('employee_deductions', function (Blueprint $table): void {
                    $table->dropIndex('emp_deductions_emp_active_idx');
                });
            }
        }

        if (Schema::hasTable('employees')) {
            if (Schema::hasIndex('employees', 'employees_branch_id_idx')) {
                Schema::table('employees', function (Blueprint $table): void {
                    $table->dropIndex('employees_branch_id_idx');
                });
            }

            if (Schema::hasIndex('employees', 'employees_user_id_idx')) {
                Schema::table('employees', function (Blueprint $table): void {
                    $table->dropIndex('employees_user_id_idx');
                });
            }

            if (Schema::hasIndex('employees', 'employees_lastname_firstname_idx')) {
                Schema::table('employees', function (Blueprint $table): void {
                    $table->dropIndex('employees_lastname_firstname_idx');
                });
            }
        }

        if (Schema::hasTable('holidays')) {
            if (Schema::hasIndex('holidays', 'holidays_date_idx')) {
                Schema::table('holidays', function (Blueprint $table): void {
                    $table->dropIndex('holidays_date_idx');
                });
            }

            if (Schema::hasIndex('holidays', 'holidays_recurring_month_day_idx')) {
                Schema::table('holidays', function (Blueprint $table): void {
                    $table->dropIndex('holidays_recurring_month_day_idx');
                });
            }
        }

        if (Schema::hasTable('payroll_periods')) {
            if (Schema::hasIndex('payroll_periods', 'payroll_periods_date_start_idx')) {
                Schema::table('payroll_periods', function (Blueprint $table): void {
                    $table->dropIndex('payroll_periods_date_start_idx');
                });
            }

            if (Schema::hasIndex('payroll_periods', 'payroll_periods_locked_date_start_idx')) {
                Schema::table('payroll_periods', function (Blueprint $table): void {
                    $table->dropIndex('payroll_periods_locked_date_start_idx');
                });
            }
        }

        if (Schema::hasTable('employee_loan_payments') && Schema::hasIndex('employee_loan_payments', 'emp_loan_payments_loan_status_idx')) {
            Schema::table('employee_loan_payments', function (Blueprint $table): void {
                $table->dropIndex('emp_loan_payments_loan_status_idx');
            });
        }
    }
};
