<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dtrs') || Schema::hasColumn('dtrs', 'leave_id')) {
            return;
        }

        Schema::table('dtrs', function (Blueprint $table): void {
            $table->foreignId('leave_id')
                ->nullable()
                ->after('id')
                ->constrained('leaves')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dtrs') || ! Schema::hasColumn('dtrs', 'leave_id')) {
            return;
        }

        Schema::table('dtrs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('leave_id');
        });
    }
};
