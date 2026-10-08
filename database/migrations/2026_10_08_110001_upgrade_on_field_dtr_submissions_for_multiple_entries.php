<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dtr_submissions')) {
            Schema::table('dtr_submissions', function (Blueprint $table): void {
                if (! Schema::hasColumn('dtr_submissions', 'schedule_type')) {
                    $table->string('schedule_type', 64)->nullable()->after('time_out');
                }

                if (! Schema::hasColumn('dtr_submissions', 'schedule_start')) {
                    $table->time('schedule_start')->nullable()->after('schedule_type');
                }

                if (! Schema::hasColumn('dtr_submissions', 'schedule_end')) {
                    $table->time('schedule_end')->nullable()->after('schedule_start');
                }

                if (! Schema::hasColumn('dtr_submissions', 'entries')) {
                    $table->json('entries')->nullable()->after('schedule_end');
                }
            });
        }

        if (Schema::hasTable('dtrs') && Schema::hasColumn('dtrs', 'on_field_dtr_submission_id')) {
            Schema::table('dtrs', function (Blueprint $table): void {
                if (! Schema::hasIndex('dtrs', 'dtrs_on_field_submission_idx')) {
                    $table->index('on_field_dtr_submission_id', 'dtrs_on_field_submission_idx');
                }

                if (Schema::hasIndex('dtrs', 'dtrs_on_field_dtr_submission_id_unique')) {
                    $table->dropUnique('dtrs_on_field_dtr_submission_id_unique');
                }
            });
        }

        if (Schema::hasTable('employee_visible_dtrs') && Schema::hasColumn('employee_visible_dtrs', 'on_field_dtr_submission_id')) {
            Schema::table('employee_visible_dtrs', function (Blueprint $table): void {
                if (! Schema::hasIndex('employee_visible_dtrs', 'emp_vis_dtrs_on_field_submission_idx')) {
                    $table->index('on_field_dtr_submission_id', 'emp_vis_dtrs_on_field_submission_idx');
                }

                if (Schema::hasIndex('employee_visible_dtrs', 'employee_visible_dtrs_on_field_dtr_submission_id_unique')) {
                    $table->dropUnique('employee_visible_dtrs_on_field_dtr_submission_id_unique');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dtr_submissions')) {
            Schema::table('dtr_submissions', function (Blueprint $table): void {
                $cols = collect(['schedule_type', 'schedule_start', 'schedule_end', 'entries'])
                    ->filter(fn (string $col): bool => Schema::hasColumn('dtr_submissions', $col))
                    ->all();

                if ($cols !== []) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
