<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_employment_type_changes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('previous_type')->nullable();
            $table->string('employment_type');
            $table->date('effective_date');
            $table->text('explanation');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date'], 'employment_type_changes_employee_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employment_type_changes');
    }
};
