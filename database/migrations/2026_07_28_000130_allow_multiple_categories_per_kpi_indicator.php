<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_categories', function (Blueprint $table): void {
            $table->index('kpi_indicator_id', 'kpi_categories_kpi_indicator_id_index');
            $table->dropUnique('kpi_categories_kpi_indicator_id_unique');
            $table->unique(
                ['kpi_indicator_id', 'name'],
                'kpi_categories_indicator_name_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('kpi_categories', function (Blueprint $table): void {
            $table->dropUnique('kpi_categories_indicator_name_unique');
            $table->unique('kpi_indicator_id', 'kpi_categories_kpi_indicator_id_unique');
            $table->dropIndex('kpi_categories_kpi_indicator_id_index');
        });
    }
};
