<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_categories')) {
            return;
        }

        $indexes = $this->getIndexes();

        if (! $this->hasIndex($indexes, ['kpi_indicator_id', 'name'], unique: true)) {
            Schema::table('kpi_categories', function (Blueprint $table): void {
                $table->unique(
                    ['kpi_indicator_id', 'name'],
                    'kpi_categories_indicator_name_unique',
                );
            });
        }

        $indexes = $this->getIndexes();
        $legacyUnique = $this->findIndex(
            $indexes,
            ['kpi_indicator_id'],
            unique: true,
        );

        if ($legacyUnique !== null) {
            Schema::table('kpi_categories', function (Blueprint $table) use ($legacyUnique): void {
                $table->dropUnique($legacyUnique['name']);
            });
        }

        $indexes = $this->getIndexes();

        if (! $this->hasIndex($indexes, ['kpi_indicator_id'], unique: false)) {
            Schema::table('kpi_categories', function (Blueprint $table): void {
                $table->index(
                    'kpi_indicator_id',
                    'kpi_categories_kpi_indicator_id_index',
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_categories')) {
            return;
        }

        $indexes = $this->getIndexes();

        if (! $this->hasIndex($indexes, ['kpi_indicator_id'], unique: true)) {
            Schema::table('kpi_categories', function (Blueprint $table): void {
                $table->unique(
                    'kpi_indicator_id',
                    'kpi_categories_kpi_indicator_id_unique',
                );
            });
        }

        $indexes = $this->getIndexes();
        $compositeUnique = $this->findIndex(
            $indexes,
            ['kpi_indicator_id', 'name'],
            unique: true,
        );

        if ($compositeUnique !== null) {
            Schema::table('kpi_categories', function (Blueprint $table) use ($compositeUnique): void {
                $table->dropUnique($compositeUnique['name']);
            });
        }

        $indexes = $this->getIndexes();
        $supportingIndex = $indexes->first(
            fn (array $index): bool => ($index['name'] ?? null)
                === 'kpi_categories_kpi_indicator_id_index',
        );

        if ($supportingIndex !== null) {
            Schema::table('kpi_categories', function (Blueprint $table) use ($supportingIndex): void {
                $table->dropIndex($supportingIndex['name']);
            });
        }
    }

    /**
     * @return Collection<int, array{name: string, columns: array<int, string>, unique: bool}>
     */
    private function getIndexes(): Collection
    {
        return collect(Schema::getIndexes('kpi_categories'));
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function hasIndex(Collection $indexes, array $columns, bool $unique): bool
    {
        return $this->findIndex($indexes, $columns, $unique) !== null;
    }

    /**
     * @param  array<int, string>  $columns
     * @return array{name: string, columns: array<int, string>, unique: bool}|null
     */
    private function findIndex(Collection $indexes, array $columns, bool $unique): ?array
    {
        return $indexes->first(
            fn (array $index): bool => (bool) ($index['unique'] ?? false) === $unique
                && array_values($index['columns'] ?? []) === $columns,
        );
    }
};
