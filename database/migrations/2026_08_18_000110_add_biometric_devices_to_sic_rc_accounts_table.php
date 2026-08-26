<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sic_rc_accounts', function (Blueprint $table): void {
            $table->longText('biometric_devices')->nullable()->after('station_biometrics');
        });
    }

    public function down(): void
    {
        Schema::table('sic_rc_accounts', function (Blueprint $table): void {
            $table->dropColumn('biometric_devices');
        });
    }
};