<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (! Schema::hasColumn('leaves', 'hr_comment')) {
                $table->text('hr_comment')->nullable()->after('reason');
            }

            if (! Schema::hasColumn('leaves', 'reviewed_by')) {
                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('status_updated_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('leaves', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }

            if (! Schema::hasColumn('leaves', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('reviewed_at');
            }

            if (! Schema::hasColumn('leaves', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable()->after('attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (Schema::hasColumn('leaves', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('leaves', 'hr_comment') ? 'hr_comment' : null,
                Schema::hasColumn('leaves', 'reviewed_at') ? 'reviewed_at' : null,
                Schema::hasColumn('leaves', 'attachment_path') ? 'attachment_path' : null,
                Schema::hasColumn('leaves', 'attachment_original_name') ? 'attachment_original_name' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
