<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('scope_type', 24);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scope_type', 'is_active']);
        });

        Schema::create('kpi_account_branch', function (Blueprint $table): void {
            $table->foreignId('kpi_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->primary(['kpi_account_id', 'branch_id']);
        });

        Schema::create('kpi_account_department', function (Blueprint $table): void {
            $table->foreignId('kpi_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            $table->primary(['kpi_account_id', 'department_id']);
        });

        Schema::create('kpi_account_employee', function (Blueprint $table): void {
            $table->foreignId('kpi_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->primary(['kpi_account_id', 'employee_id']);
        });

        Schema::create('kpi_rating_cycles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kpi_account_id')->constrained()->cascadeOnDelete();
            $table->date('rating_date');
            $table->string('title');
            $table->string('scope_type', 24);
            $table->string('status', 24)->default('draft');
            $table->timestamps();

            $table->unique(['kpi_account_id', 'rating_date']);
            $table->index(['kpi_account_id', 'rating_date', 'status']);
        });

        Schema::create('kpi_rating_targets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kpi_rating_cycle_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 24);
            $table->string('target_key');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_name');
            $table->string('branch_name')->nullable();
            $table->string('department_name')->nullable();
            $table->string('designation_name')->nullable();
            $table->string('status', 24)->default('pending');
            $table->json('rating_payload')->nullable();
            $table->timestamps();

            $table->unique(['kpi_rating_cycle_id', 'target_key']);
            $table->index(['kpi_rating_cycle_id', 'target_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_rating_targets');
        Schema::dropIfExists('kpi_rating_cycles');
        Schema::dropIfExists('kpi_account_employee');
        Schema::dropIfExists('kpi_account_department');
        Schema::dropIfExists('kpi_account_branch');
        Schema::dropIfExists('kpi_accounts');
    }
};
