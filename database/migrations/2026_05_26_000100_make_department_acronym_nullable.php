<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments') || ! Schema::hasColumn('departments', 'acronym')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('departments', function (Blueprint $table): void {
                $table->string('acronym', 20)->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE departments MODIFY acronym VARCHAR(20) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('departments') || ! Schema::hasColumn('departments', 'acronym') || DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('departments')
            ->whereNull('acronym')
            ->update(['acronym' => '']);

        DB::statement('ALTER TABLE departments MODIFY acronym VARCHAR(20) NOT NULL');
    }
};
