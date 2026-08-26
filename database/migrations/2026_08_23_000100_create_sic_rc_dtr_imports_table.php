<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sic_rc_dtr_imports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sic_rc_account_id')->nullable()->constrained('sic_rc_accounts')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_id')->index();
            $table->string('import_name');
            $table->string('source_filename')->nullable();
            $table->string('source_file_hash', 64)->nullable()->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status', 32)->index();
            $table->text('message')->nullable();
            $table->json('errors')->nullable();
            $table->timestamp('imported_at')->nullable()->index();
            $table->timestamps();

            $table->index(['branch_id', 'payroll_period_id', 'imported_at'], 'sicrc_dtr_import_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sic_rc_dtr_imports');
    }
};
