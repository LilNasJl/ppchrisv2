<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_summary_scopes')) {
            Schema::create('payroll_summary_scopes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payroll_period_id')->unique()->constrained('payroll_periods')->cascadeOnDelete();
                $table->string('group_by', 16)->default('branch');
                $table->json('included_ids')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_summary_scopes');
    }
};
