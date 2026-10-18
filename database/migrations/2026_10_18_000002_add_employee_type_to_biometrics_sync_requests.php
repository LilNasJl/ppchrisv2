<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biometrics_sync_requests', function (Blueprint $table): void {
            $table->string('sync_type', 20)->default('payroll')->after('device_name');
            $table->foreignId('payroll_period_id')->nullable()->change();
            $table->date('date_from')->nullable()->change();
            $table->date('date_to')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('biometrics_sync_requests')->where('sync_type', 'employee')->exists()) {
            throw new RuntimeException('Employee sync history exists; rollback would discard its request type.');
        }
        Schema::table('biometrics_sync_requests', function (Blueprint $table): void {
            $table->dropColumn('sync_type');
            $table->foreignId('payroll_period_id')->nullable(false)->change();
            $table->date('date_from')->nullable(false)->change();
            $table->date('date_to')->nullable(false)->change();
        });
    }
};
