<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')
            ->whereNull('allowance')
            ->update(['allowance' => 0]);

        DB::statement('ALTER TABLE employees MODIFY allowance DOUBLE NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE employees MODIFY allowance DOUBLE NULL');
    }
};
