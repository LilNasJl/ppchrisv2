<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->foreignId('leave_id')->nullable()->constrained('leaves')->nullOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('fingerprint_id')->index();
            $table->string('batch_id')->nullable()->index();
            $table->string('import_name')->nullable();
            $table->string('source_session_id')->nullable()->index();
            $table->string('source_filename')->nullable();
            $table->string('source_file_hash', 64)->nullable()->index();
            $table->string('source_row_hash', 64)->nullable()->index();
            $table->string('latest_source_row_hash', 64)->nullable()->index();
            $table->json('latest_source_payload')->nullable();
            $table->date('date_in')->nullable()->index();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->nullable()->index();
            $table->string('day_part')->nullable()->index();
            $table->string('entry_source')->nullable()->index();
            $table->time('schedule_start')->nullable();
            $table->time('schedule_end')->nullable();
            $table->integer('late')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('overtime')->default(0);
            $table->integer('early_clock_in')->default(0);
            $table->integer('credited_early_clock_in')->default(0);
            $table->integer('credited_overtime')->default(0);
            $table->integer('work_hrs')->default(0);
            $table->integer('credited_work_hrs')->default(0);
            $table->string('overtime_status')->nullable();
            $table->boolean('early_clock_in_approved')->default(false);
            $table->boolean('overtime_approved')->default(false);
            $table->boolean('is_holiday')->default(false);
            $table->foreignId('holiday_id')->nullable()->constrained('holidays')->nullOnDelete();
            $table->string('holiday_type')->nullable();
            $table->decimal('holiday_rate', 8, 2)->nullable();
            $table->boolean('holiday_excluded')->default(false);
            $table->decimal('daily_rate', 10, 2)->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->integer('absence_minutes')->default(0);
            $table->boolean('is_imported')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_manually_edited')->default(false)->index();
            $table->timestamp('manual_edited_at')->nullable();
            $table->foreignId('manual_edited_by_sicrc_account_id')->nullable()->constrained('sic_rc_accounts')->nullOnDelete();
            $table->boolean('needs_review')->default(false)->index();
            $table->string('review_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payroll_period_id', 'branch_id', 'fingerprint_id', 'date_in'], 'employee_visible_dtrs_scope_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_visible_dtrs');
    }
};
