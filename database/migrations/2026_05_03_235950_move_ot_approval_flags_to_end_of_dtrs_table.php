<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE dtrs
                MODIFY early_clock_in_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER updated_at,
                MODIFY overtime_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER early_clock_in_approved
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE dtrs
                MODIFY early_clock_in_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER overtime_status,
                MODIFY overtime_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER early_clock_in_approved
        ");
    }
};
