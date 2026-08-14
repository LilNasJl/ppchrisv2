<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('dtrs', 'credited_early_clock_in')) {
            Schema::table('dtrs', function (Blueprint $table): void {
                $table->unsignedInteger('credited_early_clock_in')
                    ->default(0)
                    ->after('early_clock_in');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('dtrs', 'credited_early_clock_in')) {
            Schema::table('dtrs', function (Blueprint $table): void {
                $table->dropColumn('credited_early_clock_in');
            });
        }
    }
};
