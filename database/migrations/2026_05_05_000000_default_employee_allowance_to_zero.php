<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')
            ->whereNull('allowance')
            ->update(['allowance' => 0]);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('employees', function (Blueprint $table): void {
                $table->double('allowance')->nullable()->default(0)->change();
            });

            return;
        }

        DB::statement('ALTER TABLE employees MODIFY allowance DOUBLE NULL DEFAULT 0');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE employees MODIFY allowance DOUBLE NULL');
    }
};
