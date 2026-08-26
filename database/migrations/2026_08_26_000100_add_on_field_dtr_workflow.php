<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sic_rc_accounts', function (Blueprint $table): void {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('employees')
                ->nullOnDelete();
        });

        Schema::table('dtr_submissions', function (Blueprint $table): void {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('sic_rc_account_id')
                ->constrained('employees')
                ->nullOnDelete();
            $table->string('employee_name_snapshot')->nullable()->after('employee_id');
            $table->string('employee_company_id_snapshot')->nullable()->after('employee_name_snapshot');
            $table->string('branch_name_snapshot')->nullable()->after('branch_id');
            $table->date('date_in')->nullable()->after('branch_name_snapshot');
            $table->time('time_in')->nullable()->after('date_in');
            $table->date('date_out')->nullable()->after('time_in');
            $table->time('time_out')->nullable()->after('date_out');
            $table->text('description')->nullable()->after('comments');
            $table->string('status', 32)->default('Pending')->after('submission_type')->index();
            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('reviewer_remarks')->nullable()->after('reviewed_by_user_id');
            $table->timestamp('reviewed_at')->nullable()->after('reviewer_remarks');
            $table->unsignedBigInteger('generated_dtr_id')->nullable()->after('reviewed_at')->index();
            $table->unsignedBigInteger('generated_visible_dtr_id')->nullable()->after('generated_dtr_id')->index();
            $table->timestamp('generated_dtr_deleted_at')->nullable()->after('generated_visible_dtr_id');
            $table->foreignId('generated_dtr_deleted_by_user_id')
                ->nullable()
                ->after('generated_dtr_deleted_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(
                ['submission_type', 'status', 'payroll_period_id', 'branch_id'],
                'dtr_submissions_on_field_scope_index',
            );
        });

        DB::table('dtr_submissions')
            ->where('submission_type', 'proof')
            ->whereNull('description')
            ->update(['description' => DB::raw('comments')]);

        Schema::table('dtrs', function (Blueprint $table): void {
            $table->foreignId('on_field_dtr_submission_id')
                ->nullable()
                ->after('leave_id')
                ->unique()
                ->constrained('dtr_submissions')
                ->restrictOnDelete();
        });

        Schema::table('employee_visible_dtrs', function (Blueprint $table): void {
            $table->foreignId('on_field_dtr_submission_id')
                ->nullable()
                ->after('leave_id')
                ->unique()
                ->constrained('dtr_submissions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_visible_dtrs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('on_field_dtr_submission_id');
        });

        Schema::table('dtrs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('on_field_dtr_submission_id');
        });

        Schema::table('dtr_submissions', function (Blueprint $table): void {
            $table->dropIndex('dtr_submissions_on_field_scope_index');
            $table->dropConstrainedForeignId('generated_dtr_deleted_by_user_id');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn([
                'employee_name_snapshot',
                'employee_company_id_snapshot',
                'branch_name_snapshot',
                'date_in',
                'time_in',
                'date_out',
                'time_out',
                'description',
                'status',
                'reviewer_remarks',
                'reviewed_at',
                'generated_dtr_id',
                'generated_visible_dtr_id',
                'generated_dtr_deleted_at',
            ]);
        });

        Schema::table('sic_rc_accounts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
