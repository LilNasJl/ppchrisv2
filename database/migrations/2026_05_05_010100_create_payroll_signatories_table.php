<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_signatories', function (Blueprint $table) {
            $table->id();
            $table->string('context')->unique()->default('default');
            $table->string('prepared_by')->default('Prepared By');
            $table->string('checked_by')->default('Checked By');
            $table->string('approved_by')->default('Approved By');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_signatories');
    }
};
