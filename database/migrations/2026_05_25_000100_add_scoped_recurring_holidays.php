<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('holidays')) {
            return;
        }

        if ($this->hasIndex('holidays', 'holidays_date_unique')) {
            Schema::table('holidays', function (Blueprint $table): void {
                $table->dropUnique('holidays_date_unique');
            });
        }

        Schema::table('holidays', function (Blueprint $table): void {
            if (! Schema::hasColumn('holidays', 'branch_id')) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('holidays', 'month_day')) {
                $table->string('month_day', 5)
                    ->nullable()
                    ->after('date')
                    ->index();
            }

            if (! Schema::hasColumn('holidays', 'is_recurring')) {
                $table->boolean('is_recurring')
                    ->default(true)
                    ->after('title')
                    ->index();
            }

            if (! Schema::hasColumn('holidays', 'source')) {
                $table->string('source')
                    ->nullable()
                    ->after('is_recurring');
            }
        });

        DB::table('holidays')
            ->whereNull('month_day')
            ->whereNotNull('date')
            ->update([
                'month_day' => DB::raw("DATE_FORMAT(`date`, '%m-%d')"),
            ]);

        DB::table('holidays')
            ->whereNull('source')
            ->update(['source' => 'manual']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('holidays')) {
            return;
        }

        Schema::table('holidays', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('holidays', 'source') ? 'source' : null,
                Schema::hasColumn('holidays', 'is_recurring') ? 'is_recurring' : null,
                Schema::hasColumn('holidays', 'month_day') ? 'month_day' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }

            if (Schema::hasColumn('holidays', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
