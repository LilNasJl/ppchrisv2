<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            if (! Schema::hasColumn('dtrs', 'holiday_excluded')) {
                $table->boolean('holiday_excluded')->default(false)->after('holiday_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            if (Schema::hasColumn('dtrs', 'holiday_excluded')) {
                $table->dropColumn('holiday_excluded');
            }
        });
    }
};
