<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_indicators', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['department_id', 'name']);
        });

        Schema::create('kpi_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kpi_indicator_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 5, 2);
            $table->timestamps();
            $table->unique(['kpi_indicator_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_categories');
        Schema::dropIfExists('kpi_indicators');
    }
};
