<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            if (! Schema::hasColumn('dtrs', 'import_name')) {
                $table->string('import_name')->nullable()->after('batch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dtrs', function (Blueprint $table): void {
            if (Schema::hasColumn('dtrs', 'import_name')) {
                $table->dropColumn('import_name');
            }
        });
    }
};
