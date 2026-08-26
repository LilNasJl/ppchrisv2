<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_periods', 'unlocked_at')) {
                $table->timestamp('unlocked_at')->nullable()->after('locked_at');
            }

            if (! Schema::hasColumn('payroll_periods', 'auto_lock_blocked_at')) {
                $table->timestamp('auto_lock_blocked_at')->nullable()->after('unlocked_at');
            }

            if (! Schema::hasColumn('payroll_periods', 'auto_lock_blocked_reason')) {
                $table->string('auto_lock_blocked_reason')->nullable()->after('auto_lock_blocked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table): void {
            if (Schema::hasColumn('payroll_periods', 'auto_lock_blocked_reason')) {
                $table->dropColumn('auto_lock_blocked_reason');
            }

            if (Schema::hasColumn('payroll_periods', 'auto_lock_blocked_at')) {
                $table->dropColumn('auto_lock_blocked_at');
            }

            if (Schema::hasColumn('payroll_periods', 'unlocked_at')) {
                $table->dropColumn('unlocked_at');
            }
        });
    }
};
