<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deductions') || ! Schema::hasColumn('deductions', 'description')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('deductions', function (Blueprint $table): void {
                $table->string('description')->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE deductions MODIFY description VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('deductions') || ! Schema::hasColumn('deductions', 'description') || DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('deductions')
            ->whereNull('description')
            ->update(['description' => '']);

        DB::statement('ALTER TABLE deductions MODIFY description VARCHAR(255) NOT NULL');
    }
};
