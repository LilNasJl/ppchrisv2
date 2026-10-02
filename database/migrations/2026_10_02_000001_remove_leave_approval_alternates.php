<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_approval_levels', function (Blueprint $table): void {
            $table->dropColumn('alternate_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('leave_approval_levels', function (Blueprint $table): void {
            $table->unsignedBigInteger('alternate_employee_id')->nullable();
        });
    }
};
