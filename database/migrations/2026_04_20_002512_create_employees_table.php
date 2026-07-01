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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');

            // personal information
            $table->string('firstname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('lastname')->nullable();
            $table->string('gender')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('status')->nullable();
            $table->string('address')->nullable();
            $table->string('mobile')->nullable();
            $table->smallInteger('kids')->nullable();
            $table->string('email')->nullable();

            // designation
            $table->integer('designation_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('branch_id')->nullable();
            $table->date('hired_date')->nullable();
            $table->string('employment_type')->nullable();

            // education
            $table->string('school_name')->nullable();
            $table->string('school_level')->nullable();
            $table->date('year_grad')->nullable();

            // salary
            $table->string('rate_type')->nullable();
            $table->string('payment_type')->nullable();
            $table->double('daily_rate')->nullable();
            $table->double('monthly_rate')->nullable();
            $table->double('allowance')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
