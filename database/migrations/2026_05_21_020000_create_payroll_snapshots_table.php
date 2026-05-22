<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('data');
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index(['payroll_period_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_snapshots');
    }
};
