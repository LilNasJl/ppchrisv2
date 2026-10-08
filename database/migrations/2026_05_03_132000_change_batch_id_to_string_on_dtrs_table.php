<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('dtrs', function (Blueprint $table): void {
                $table->string('batch_id', 191)->nullable()->change();
            });

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

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('dtrs', function (Blueprint $table): void {
                $table->unsignedBigInteger('batch_id')->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE dtrs MODIFY batch_id BIGINT UNSIGNED NULL');
    }
};
