<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payroll_periods MODIFY description TEXT NULL');
        DB::statement('ALTER TABLE payroll_periods MODIFY is_locked TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payroll_periods MODIFY description DATE NULL');
        DB::statement('ALTER TABLE payroll_periods MODIFY is_locked DATE NULL');
    }
};
