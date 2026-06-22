<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (! Schema::hasColumn('leaves', 'half_day_schedule')) {
                $table->string('half_day_schedule')
                    ->nullable()
                    ->after('half_day_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (Schema::hasColumn('leaves', 'half_day_schedule')) {
                $table->dropColumn('half_day_schedule');
            }
        });
    }
};
