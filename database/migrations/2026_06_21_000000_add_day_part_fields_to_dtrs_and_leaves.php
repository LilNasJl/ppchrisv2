<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            if (! Schema::hasColumn('dtrs', 'day_part')) {
                $table->string('day_part')
                    ->default('whole_day')
                    ->after('schedule_type')
                    ->index();
            }

            if (! Schema::hasColumn('dtrs', 'entry_source')) {
                $table->string('entry_source')
                    ->default('manual')
                    ->after('day_part')
                    ->index();
            }

            if (! Schema::hasColumn('dtrs', 'absence_minutes')) {
                $table->unsignedInteger('absence_minutes')
                    ->default(0)
                    ->after('is_absent');
            }
        });

        Schema::table('leaves', function (Blueprint $table): void {
            if (! Schema::hasColumn('leaves', 'half_day_period')) {
                $table->string('half_day_period')
                    ->nullable()
                    ->after('is_half_day');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (Schema::hasColumn('leaves', 'half_day_period')) {
                $table->dropColumn('half_day_period');
            }
        });

        Schema::table('dtrs', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('dtrs', 'day_part') ? 'day_part' : null,
                Schema::hasColumn('dtrs', 'entry_source') ? 'entry_source' : null,
                Schema::hasColumn('dtrs', 'absence_minutes') ? 'absence_minutes' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
