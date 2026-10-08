<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('payroll_periods', function (Blueprint $table): void {
                $table->text('description')->nullable()->change();
                $table->boolean('is_locked')->default(false)->change();
            });

            return;
        }

        DB::statement('ALTER TABLE payroll_periods MODIFY description TEXT NULL');
        DB::statement('ALTER TABLE payroll_periods MODIFY is_locked TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE payroll_periods MODIFY description DATE NULL');
        DB::statement('ALTER TABLE payroll_periods MODIFY is_locked DATE NULL');
    }
};
