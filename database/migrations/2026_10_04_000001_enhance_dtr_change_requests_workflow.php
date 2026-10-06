<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtr_change_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('dtr_id')->nullable()->after('payroll_period_id')->index();
            $table->unsignedBigInteger('employee_visible_dtr_id')->nullable()->after('dtr_id')->index();
            $table->date('dtr_date')->nullable()->after('employee_visible_dtr_id')->index();
            $table->unsignedInteger('current_level')->default(1)->after('status');
            $table->unsignedInteger('total_levels')->default(1)->after('current_level');
            $table->string('current_step_label', 100)->nullable()->after('total_levels');
            $table->json('workflow_snapshot')->nullable()->after('current_step_label');
            $table->json('approvals_history')->nullable()->after('workflow_snapshot');
            $table->json('original_values')->nullable()->after('approvals_history');
            $table->json('proposed_values')->nullable()->after('original_values');
            $table->text('rejection_reason')->nullable()->after('proposed_values');
            $table->unsignedBigInteger('first_level_editor_id')->nullable()->after('rejection_reason');
            $table->timestamp('first_level_edited_at')->nullable()->after('first_level_editor_id');
            $table->timestamp('cancelled_at')->nullable()->after('employee_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('dtr_change_requests', function (Blueprint $table): void {
            $table->dropIndex(['dtr_id']);
            $table->dropIndex(['employee_visible_dtr_id']);
            $table->dropIndex(['dtr_date']);
            $table->dropColumn([
                'dtr_id',
                'employee_visible_dtr_id',
                'dtr_date',
                'current_level',
                'total_levels',
                'current_step_label',
                'workflow_snapshot',
                'approvals_history',
                'original_values',
                'proposed_values',
                'rejection_reason',
                'first_level_editor_id',
                'first_level_edited_at',
                'cancelled_at',
            ]);
        });
    }
};
