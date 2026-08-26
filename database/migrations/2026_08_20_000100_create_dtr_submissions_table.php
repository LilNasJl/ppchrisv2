<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dtr_submissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->foreignId('sic_rc_account_id')->nullable()->constrained('sic_rc_accounts')->nullOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('comments')->nullable();
            $table->boolean('is_new')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payroll_period_id', 'branch_id']);
            $table->index(['sic_rc_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dtr_submissions');
    }
};
