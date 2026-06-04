<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deductions') || ! Schema::hasColumn('deductions', 'description')) {
            return;
        }

        DB::statement('ALTER TABLE deductions MODIFY description VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('deductions') || ! Schema::hasColumn('deductions', 'description')) {
            return;
        }

        DB::table('deductions')
            ->whereNull('description')
            ->update(['description' => '']);

        DB::statement('ALTER TABLE deductions MODIFY description VARCHAR(255) NOT NULL');
    }
};
