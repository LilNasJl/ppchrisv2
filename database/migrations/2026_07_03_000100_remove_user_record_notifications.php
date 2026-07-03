<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->where('type', 'App\\Notifications\\RecordUpdatedNotification')
            ->delete();
    }

    public function down(): void
    {
        // Removed notification records cannot be reconstructed safely.
    }
};
