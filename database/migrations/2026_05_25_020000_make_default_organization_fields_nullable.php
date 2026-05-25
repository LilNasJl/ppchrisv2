<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('designations')) {
            if (Schema::hasColumn('designations', 'description')) {
                DB::statement('ALTER TABLE designations MODIFY description TEXT NULL');
            }

            if (Schema::hasColumn('designations', 'specification')) {
                DB::statement('ALTER TABLE designations MODIFY specification VARCHAR(255) NULL');
            }
        }

        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'description')) {
            DB::statement('ALTER TABLE departments MODIFY description TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('designations')) {
            DB::table('designations')
                ->whereNull('description')
                ->update(['description' => '']);

            if (Schema::hasColumn('designations', 'description')) {
                DB::statement('ALTER TABLE designations MODIFY description TEXT NOT NULL');
            }

            if (Schema::hasColumn('designations', 'specification')) {
                DB::table('designations')
                    ->whereNull('specification')
                    ->update(['specification' => '']);

                DB::statement('ALTER TABLE designations MODIFY specification VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'description')) {
            DB::table('departments')
                ->whereNull('description')
                ->update(['description' => '']);

            DB::statement('ALTER TABLE departments MODIFY description TEXT NOT NULL');
        }
    }
};
