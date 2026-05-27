<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('holiday_employee_exclusions')) {
            return;
        }

        Schema::create('holiday_employee_exclusions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('holiday_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->boolean('applies_every_year')->default(false);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['holiday_id', 'employee_id', 'occurrence_date'], 'holiday_employee_exclusions_unique');
            $table->index(['holiday_id', 'occurrence_date']);
            $table->index(['employee_id', 'occurrence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_employee_exclusions');
    }
};
