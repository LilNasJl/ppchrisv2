<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dtrs', function (Blueprint $table) {
            $table->id();
            $table->integer('payroll_period_id')->nullable()->index();
            $table->integer('branch_id')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->string('batch_id', 191)->nullable()->index();

            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();

            $table->string('schedule_type')->nullable();
            $table->string('schedule_start')->nullable();
            $table->string('schedule_end')->nullable();


            $table->double('late')->nullable();
            $table->double('undertime')->nullable();
            $table->double('overtime')->nullable();
            $table->double('early_clock_in')->nullable();
            $table->double('credited_overtime')->nullable();
            $table->double('work_hrs')->nullable();
            $table->double('credited_work_hrs')->nullable();
            $table->string('overtime_status')->nullable();
            $table->boolean('is_holiday')->nullable();
            $table->string('holiday_type')->nullable();
            $table->double('holiday_rate')->nullable();
            $table->boolean('is_imported')->nullable();
            $table->boolean('is_locked')->nullable()->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtrs');
    }
};
