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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
            $table->string('branch_address');
            $table->string('mobile_no');

            // sic id or employee id which designation is SIC
            $table->integer('employee_id');

            $table->integer('no_of_shifts')
                ->nullable();
            $table->time('reg_sched_start')
                ->nullable();
            $table->time('reg_sched_end')
                ->nullable();

            $table->boolean('is_24hrs')
                ->nullable();
            $table->time('opening_hrs')
                ->nullable();
            $table->time('closed_hrs')
                ->nullable();
            

            $table->time('shift1_start')
                ->nullable();
            $table->time('shift1_end')
                ->nullable();
            $table->time('shift2_start')
                ->nullable();
            $table->time('shift2_end')
                ->nullable();
            $table->time('shift3_start')
                ->nullable();
            $table->time('shift3_end')
                ->nullable();

            $table->boolean('has_broken_time')
                ->nullable();

            $table->time('broken_shift1_start')
                ->nullable();
            $table->time('broken_shift1_end')
                ->nullable();
            $table->time('broken_shift2_start')
                ->nullable();
            $table->time('broken_shift2_end')
                ->nullable();
            $table->time('broken_shift3_start')
                ->nullable();
            $table->time('broken_shift3_end')
                ->nullable();

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
