<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table) {
            if (! Schema::hasColumn('dtrs', 'holiday_id')) {
                $table->unsignedBigInteger('holiday_id')
                    ->nullable()
                    ->after('is_holiday')
                    ->index();
            }

            if (! Schema::hasColumn('dtrs', 'is_absent')) {
                $table->boolean('is_absent')
                    ->default(false)
                    ->after('daily_rate')
                    ->index();
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'schedule_type')) {
                $table->string('schedule_type')
                    ->default('regular')
                    ->after('employment_type');
            }

            if (! Schema::hasColumn('employees', 'leave_credits')) {
                $table->decimal('leave_credits', 5, 2)
                    ->default(10)
                    ->after('allowance');
            }

            if (! Schema::hasColumn('employees', 'birthday_leave_credits')) {
                $table->decimal('birthday_leave_credits', 5, 2)
                    ->default(1)
                    ->after('leave_credits');
            }

            if (! Schema::hasColumn('employees', 'leave_credits_year')) {
                $table->unsignedSmallInteger('leave_credits_year')
                    ->nullable()
                    ->after('birthday_leave_credits');
            }
        });

        Schema::table('leaves', function (Blueprint $table) {
            if (! Schema::hasColumn('leaves', 'is_half_day')) {
                $table->boolean('is_half_day')
                    ->default(false)
                    ->after('leave_to');
            }

            if (! Schema::hasColumn('leaves', 'deducted_leave_credits')) {
                $table->decimal('deducted_leave_credits', 5, 2)
                    ->default(0)
                    ->after('status');
            }

            if (! Schema::hasColumn('leaves', 'deducted_birthday_leave_credits')) {
                $table->decimal('deducted_birthday_leave_credits', 5, 2)
                    ->default(0)
                    ->after('deducted_leave_credits');
            }

            if (! Schema::hasColumn('leaves', 'status_updated_at')) {
                $table->timestamp('status_updated_at')
                    ->nullable()
                    ->after('deducted_birthday_leave_credits');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('leaves', 'is_half_day') ? 'is_half_day' : null,
                Schema::hasColumn('leaves', 'deducted_leave_credits') ? 'deducted_leave_credits' : null,
                Schema::hasColumn('leaves', 'deducted_birthday_leave_credits') ? 'deducted_birthday_leave_credits' : null,
                Schema::hasColumn('leaves', 'status_updated_at') ? 'status_updated_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('employees', 'schedule_type') ? 'schedule_type' : null,
                Schema::hasColumn('employees', 'leave_credits') ? 'leave_credits' : null,
                Schema::hasColumn('employees', 'birthday_leave_credits') ? 'birthday_leave_credits' : null,
                Schema::hasColumn('employees', 'leave_credits_year') ? 'leave_credits_year' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('dtrs', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('dtrs', 'holiday_id') ? 'holiday_id' : null,
                Schema::hasColumn('dtrs', 'is_absent') ? 'is_absent' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
