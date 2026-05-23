<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        foreach (['philhealth', 'gsis', 'sss', 'pagibig', 'tin'] as $column) {
            if (Schema::hasColumn('employees', $column)) {
                DB::statement("ALTER TABLE employees MODIFY {$column} VARCHAR(20) NULL");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        foreach (['philhealth', 'gsis', 'sss', 'pagibig', 'tin'] as $column) {
            if (Schema::hasColumn('employees', $column)) {
                DB::statement("ALTER TABLE employees MODIFY {$column} VARCHAR(20) NOT NULL");
            }
        }
    }
};
