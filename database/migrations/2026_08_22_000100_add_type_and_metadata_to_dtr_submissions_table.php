<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtr_submissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('dtr_submissions', 'submission_type')) {
                $table->string('submission_type', 20)->default('dtr')->after('is_new');
                $table->index(['submission_type', 'is_new']);
            }

            if (! Schema::hasColumn('dtr_submissions', 'mime_type')) {
                $table->string('mime_type', 191)->nullable()->after('file_size');
            }

            if (! Schema::hasColumn('dtr_submissions', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->after('mime_type');
            }

            if (! Schema::hasColumn('dtr_submissions', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('is_new');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dtr_submissions', function (Blueprint $table): void {
            if (Schema::hasColumn('dtr_submissions', 'submission_type')) {
                $table->dropIndex(['submission_type', 'is_new']);
            }

            $columns = collect(['submission_type', 'mime_type', 'file_hash', 'viewed_at'])
                ->filter(fn (string $column): bool => Schema::hasColumn('dtr_submissions', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
