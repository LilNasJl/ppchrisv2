<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometrics_sync_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('device_id', 64)->index();
            $table->string('device_name');
            $table->string('active_device_key', 64)->nullable()->unique();
            $table->foreignId('payroll_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('command_id')->nullable();
            $table->text('message')->nullable();
            $table->json('result')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('remote_acknowledged_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('deadline_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometrics_sync_requests');
    }
};
