<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table): void {
            if (! Schema::hasColumn('branches', 'scheduling')) {
                $table->string('scheduling')->nullable()->after('has_broken_time');
            }
        });

        DB::statement('ALTER TABLE branches MODIFY mobile_no VARCHAR(255) NULL');
        DB::statement('ALTER TABLE branches MODIFY employee_id INT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table): void {
            if (Schema::hasColumn('branches', 'scheduling')) {
                $table->dropColumn('scheduling');
            }
        });
    }
};
