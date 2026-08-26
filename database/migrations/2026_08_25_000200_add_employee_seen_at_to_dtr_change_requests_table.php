<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtr_change_requests', function (Blueprint $table): void {
            $table->timestamp('employee_seen_at')->nullable()->after('reviewed_at');
            $table->index(['employee_id', 'reviewed_at', 'employee_seen_at'], 'dtr_change_requests_employee_unseen_index');
        });
    }

    public function down(): void
    {
        Schema::table('dtr_change_requests', function (Blueprint $table): void {
            $table->dropIndex('dtr_change_requests_employee_unseen_index');
            $table->dropColumn('employee_seen_at');
        });
    }
};
