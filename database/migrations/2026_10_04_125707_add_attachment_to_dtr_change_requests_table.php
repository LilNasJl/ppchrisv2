<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtr_change_requests', function (Blueprint $table): void {
            $table->string('attachment_path', 2048)->nullable()->after('description');
            $table->string('attachment_original_name', 255)->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('dtr_change_requests', function (Blueprint $table): void {
            $table->dropColumn(['attachment_path', 'attachment_original_name']);
        });
    }
};
