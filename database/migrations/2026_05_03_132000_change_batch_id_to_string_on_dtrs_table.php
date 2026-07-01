<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('dtrs', 'batch_id')) {
            return;
        }

        DB::statement('ALTER TABLE dtrs MODIFY batch_id VARCHAR(191) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('dtrs', 'batch_id')) {
            return;
        }

        DB::statement('ALTER TABLE dtrs MODIFY batch_id BIGINT UNSIGNED NULL');
    }
};
