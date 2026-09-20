<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add station manager fields to employees table
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'is_station_manager')) {
                $table->boolean('is_station_manager')->default(false);
            }
            if (! Schema::hasColumn('employees', 'managed_branches')) {
                $table->json('managed_branches')->nullable();
            }
        });

        // 2. Add employee foreign keys to dtr_change_requests
        Schema::table('dtr_change_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('dtr_change_requests', 'assigned_employee_id')) {
                $table->foreignId('assigned_employee_id')
                    ->nullable()
                    ->after('payroll_period_id')
                    ->constrained('employees')
                    ->nullOnDelete();
                $table->index(['assigned_employee_id', 'status'], 'dtr_change_requests_assigned_emp_status_index');
            }
            if (! Schema::hasColumn('dtr_change_requests', 'reviewed_by_employee_id')) {
                $table->foreignId('reviewed_by_employee_id')
                    ->nullable()
                    ->after('assigned_employee_id')
                    ->constrained('employees')
                    ->nullOnDelete();
            }
        });

        // 3. Add employee foreign key to dtr_submissions
        Schema::table('dtr_submissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('dtr_submissions', 'submitted_by_employee_id')) {
                $table->foreignId('submitted_by_employee_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('employees')
                    ->nullOnDelete();
                $table->index(['submitted_by_employee_id', 'created_at'], 'dtr_submissions_emp_created_index');
            }
        });

        // 4. Add employee foreign key to employee_visible_dtrs
        Schema::table('employee_visible_dtrs', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_visible_dtrs', 'manual_edited_by_employee_id')) {
                $table->foreignId('manual_edited_by_employee_id')
                    ->nullable()
                    ->after('manual_edited_at')
                    ->constrained('employees')
                    ->nullOnDelete();
            }
        });

        // 5. Add employee foreign key to sic_rc_dtr_imports
        if (Schema::hasTable('sic_rc_dtr_imports')) {
            Schema::table('sic_rc_dtr_imports', function (Blueprint $table): void {
                if (! Schema::hasColumn('sic_rc_dtr_imports', 'imported_by_employee_id')) {
                    $table->foreignId('imported_by_employee_id')
                        ->nullable()
                        ->after('payroll_period_id')
                        ->constrained('employees')
                        ->nullOnDelete();
                }
            });
        }

        // 6. Data Migration: Map existing sic_rc_accounts to employees
        if (Schema::hasTable('sic_rc_accounts')) {
            $accounts = DB::table('sic_rc_accounts')->get();

            foreach ($accounts as $account) {
                if (! $account->employee_id) {
                    continue;
                }

                $managedBranches = null;
                if (! empty($account->biometric_devices)) {
                    try {
                        $decrypted = \Illuminate\Support\Facades\Crypt::decrypt($account->biometric_devices);
                        $managedBranches = is_array($decrypted) ? $decrypted : json_decode((string) $decrypted, true);
                    } catch (\Throwable) {
                        $managedBranches = json_decode((string) $account->biometric_devices, true);
                    }
                }

                // Transfer station manager status and branch devices to the employee
                DB::table('employees')
                    ->where('id', $account->employee_id)
                    ->update([
                        'is_station_manager' => (bool) $account->is_active,
                        'managed_branches' => $managedBranches ? json_encode($managedBranches) : null,
                    ]);

                // Update dtr_change_requests
                DB::table('dtr_change_requests')
                    ->where('assigned_sic_rc_account_id', $account->id)
                    ->update(['assigned_employee_id' => $account->employee_id]);

                DB::table('dtr_change_requests')
                    ->where('reviewed_by_sic_rc_account_id', $account->id)
                    ->update(['reviewed_by_employee_id' => $account->employee_id]);

                // Update dtr_submissions
                DB::table('dtr_submissions')
                    ->where('sic_rc_account_id', $account->id)
                    ->update(['submitted_by_employee_id' => $account->employee_id]);

                // Update employee_visible_dtrs
                DB::table('employee_visible_dtrs')
                    ->where('manual_edited_by_sicrc_account_id', $account->id)
                    ->update(['manual_edited_by_employee_id' => $account->employee_id]);

                // Update sic_rc_dtr_imports
                if (Schema::hasTable('sic_rc_dtr_imports')) {
                    DB::table('sic_rc_dtr_imports')
                        ->where('sic_rc_account_id', $account->id)
                        ->update(['imported_by_employee_id' => $account->employee_id]);
                }
            }

            // 7. Drop foreign keys and legacy columns referencing sic_rc_accounts
            Schema::table('dtr_change_requests', function (Blueprint $table): void {
                $table->dropForeign(['assigned_sic_rc_account_id']);
                $table->dropForeign(['reviewed_by_sic_rc_account_id']);
                $table->dropColumn(['assigned_sic_rc_account_id', 'reviewed_by_sic_rc_account_id']);
            });

            Schema::table('dtr_submissions', function (Blueprint $table): void {
                $table->dropForeign(['sic_rc_account_id']);
                $table->dropColumn('sic_rc_account_id');
            });

            Schema::table('employee_visible_dtrs', function (Blueprint $table): void {
                $table->dropForeign(['manual_edited_by_sicrc_account_id']);
                $table->dropColumn('manual_edited_by_sicrc_account_id');
            });

            if (Schema::hasTable('sic_rc_dtr_imports')) {
                Schema::table('sic_rc_dtr_imports', function (Blueprint $table): void {
                    $table->dropForeign(['sic_rc_account_id']);
                    $table->dropColumn('sic_rc_account_id');
                });
            }

            // 8. Drop legacy sic_rc_accounts table
            Schema::dropIfExists('sic_rc_accounts');
        }
    }

    public function down(): void
    {
        // Reverse schema changes if rolled back
        if (! Schema::hasTable('sic_rc_accounts')) {
            Schema::create('sic_rc_accounts', function (Blueprint $table): void {
                $table->id();
                $table->string('uuid', 36)->nullable()->unique();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('username', 191)->unique();
                $table->string('password', 255);
                $table->text('station_biometrics')->nullable();
                $table->text('biometric_devices')->nullable();
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'is_station_manager')) {
                $table->dropColumn('is_station_manager');
            }
            if (Schema::hasColumn('employees', 'managed_branches')) {
                $table->dropColumn('managed_branches');
            }
        });

        Schema::table('dtr_change_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('dtr_change_requests', 'assigned_employee_id')) {
                $table->dropForeign(['assigned_employee_id']);
                $table->dropColumn('assigned_employee_id');
            }
            if (Schema::hasColumn('dtr_change_requests', 'reviewed_by_employee_id')) {
                $table->dropForeign(['reviewed_by_employee_id']);
                $table->dropColumn('reviewed_by_employee_id');
            }
        });

        Schema::table('dtr_submissions', function (Blueprint $table): void {
            if (Schema::hasColumn('dtr_submissions', 'submitted_by_employee_id')) {
                $table->dropForeign(['submitted_by_employee_id']);
                $table->dropColumn('submitted_by_employee_id');
            }
        });

        Schema::table('employee_visible_dtrs', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_visible_dtrs', 'manual_edited_by_employee_id')) {
                $table->dropForeign(['manual_edited_by_employee_id']);
                $table->dropColumn('manual_edited_by_employee_id');
            }
        });

        if (Schema::hasTable('sic_rc_dtr_imports')) {
            Schema::table('sic_rc_dtr_imports', function (Blueprint $table): void {
                if (Schema::hasColumn('sic_rc_dtr_imports', 'imported_by_employee_id')) {
                    $table->dropForeign(['imported_by_employee_id']);
                    $table->dropColumn('imported_by_employee_id');
                }
            });
        }
    }
};
