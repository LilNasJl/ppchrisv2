<?php

use App\Models\Deduction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deductions')) {
            Schema::table('deductions', function (Blueprint $table): void {
                if (! Schema::hasColumn('deductions', 'category')) {
                    $table->string('category')->default(Deduction::CATEGORY_OTHER)->after('description');
                }

                if (! Schema::hasColumn('deductions', 'term_type')) {
                    $table->string('term_type')->default(Deduction::TERM_PERMANENT)->after('category');
                }

                if (! Schema::hasColumn('deductions', 'term_periods')) {
                    $table->unsignedInteger('term_periods')->nullable()->after('term_type');
                }
            });

            foreach (Deduction::COMPANY_TITLES as $title) {
                DB::table('deductions')
                    ->where('title', $title)
                    ->update(['category' => Deduction::CATEGORY_COMPANY]);
            }

            foreach (Deduction::REMITTANCE_TITLES as $title) {
                DB::table('deductions')
                    ->where('title', $title)
                    ->update(['category' => Deduction::CATEGORY_REMITTANCE]);
            }
        }

        if (Schema::hasTable('employee_deductions')) {
            Schema::table('employee_deductions', function (Blueprint $table): void {
                if (! Schema::hasColumn('employee_deductions', 'term_type')) {
                    $table->string('term_type')->default(Deduction::TERM_PERMANENT)->after('amount');
                }

                if (! Schema::hasColumn('employee_deductions', 'term_periods')) {
                    $table->unsignedInteger('term_periods')->nullable()->after('term_type');
                }

                if (! Schema::hasColumn('employee_deductions', 'remaining_terms')) {
                    $table->unsignedInteger('remaining_terms')->nullable()->after('term_periods');
                }

                if (! Schema::hasColumn('employee_deductions', 'active')) {
                    $table->boolean('active')->default(true)->after('remaining_terms');
                }

                if (! Schema::hasColumn('employee_deductions', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('active');
                }
            });
        }

        if (Schema::hasTable('payroll_periods')) {
            Schema::table('payroll_periods', function (Blueprint $table): void {
                if (! Schema::hasColumn('payroll_periods', 'locked_at')) {
                    $table->timestamp('locked_at')->nullable()->after('is_locked');
                }

                if (! Schema::hasColumn('payroll_periods', 'deductions_processed_at')) {
                    $table->timestamp('deductions_processed_at')->nullable()->after('locked_at');
                }
            });
        }

        if (! Schema::hasTable('payroll_period_employee_exclusions')) {
            Schema::create('payroll_period_employee_exclusions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['payroll_period_id', 'employee_id'], 'payroll_period_employee_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_period_employee_exclusions');

        if (Schema::hasTable('payroll_periods')) {
            Schema::table('payroll_periods', function (Blueprint $table): void {
                $columns = array_values(array_filter([
                    Schema::hasColumn('payroll_periods', 'deductions_processed_at') ? 'deductions_processed_at' : null,
                    Schema::hasColumn('payroll_periods', 'locked_at') ? 'locked_at' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('employee_deductions')) {
            Schema::table('employee_deductions', function (Blueprint $table): void {
                $columns = array_values(array_filter([
                    Schema::hasColumn('employee_deductions', 'completed_at') ? 'completed_at' : null,
                    Schema::hasColumn('employee_deductions', 'active') ? 'active' : null,
                    Schema::hasColumn('employee_deductions', 'remaining_terms') ? 'remaining_terms' : null,
                    Schema::hasColumn('employee_deductions', 'term_periods') ? 'term_periods' : null,
                    Schema::hasColumn('employee_deductions', 'term_type') ? 'term_type' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('deductions')) {
            Schema::table('deductions', function (Blueprint $table): void {
                $columns = array_values(array_filter([
                    Schema::hasColumn('deductions', 'term_periods') ? 'term_periods' : null,
                    Schema::hasColumn('deductions', 'term_type') ? 'term_type' : null,
                    Schema::hasColumn('deductions', 'category') ? 'category' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
