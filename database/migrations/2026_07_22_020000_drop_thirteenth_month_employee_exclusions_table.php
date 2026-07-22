<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('thirteenth_month_employee_exclusions');
    }

    public function down(): void
    {
        // The employee roster feature was intentionally removed.
    }
};
