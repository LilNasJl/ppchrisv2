<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_employment_type_changes', function (Blueprint $table): void {
            $table->text('explanation')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('employee_employment_type_changes')
            ->whereNull('explanation')
            ->update(['explanation' => '']);

        Schema::table('employee_employment_type_changes', function (Blueprint $table): void {
            $table->text('explanation')->nullable(false)->change();
        });
    }
};
