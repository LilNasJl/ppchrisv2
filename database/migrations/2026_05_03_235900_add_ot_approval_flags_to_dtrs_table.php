<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->boolean('early_clock_in_approved')
                ->default(false)
                ->after('overtime_status');

            $table->boolean('overtime_approved')
                ->default(false)
                ->after('early_clock_in_approved');
        });
    }

    public function down(): void
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->dropColumn([
                'early_clock_in_approved',
                'overtime_approved',
            ]);
        });
    }
};
