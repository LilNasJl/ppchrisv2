<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leave_approval_workflows', 'device_ids')) {
            Schema::table('leave_approval_workflows', function (Blueprint $table): void {
                $table->json('device_ids')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Preserve workflow device assignments if this migration is rolled back.
    }
};
