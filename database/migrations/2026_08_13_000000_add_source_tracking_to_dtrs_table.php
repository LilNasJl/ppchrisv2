<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            $table->string('source_session_id')->nullable()->after('import_name')->index();
            $table->string('source_filename')->nullable()->after('source_session_id');
            $table->string('source_file_hash', 64)->nullable()->after('source_filename')->index();
            $table->string('source_row_hash', 64)->nullable()->after('source_file_hash')->index();
        });
    }

    public function down(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            $table->dropIndex(['source_row_hash']);
            $table->dropIndex(['source_session_id']);
            $table->dropIndex(['source_file_hash']);
            $table->dropColumn([
                'source_session_id',
                'source_filename',
                'source_file_hash',
                'source_row_hash',
            ]);
        });
    }
};
