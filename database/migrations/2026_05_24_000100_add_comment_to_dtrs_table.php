<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dtrs') || Schema::hasColumn('dtrs', 'comment')) {
            return;
        }

        Schema::table('dtrs', function (Blueprint $table): void {
            $table->text('comment')->nullable()->after('daily_rate');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dtrs') || ! Schema::hasColumn('dtrs', 'comment')) {
            return;
        }

        Schema::table('dtrs', function (Blueprint $table): void {
            $table->dropColumn('comment');
        });
    }
};
