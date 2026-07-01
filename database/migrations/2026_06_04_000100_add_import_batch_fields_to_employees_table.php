<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'employee_import_batch_id')) {
                $table->string('employee_import_batch_id')->nullable()->after('user_id')->index();
            }

            if (! Schema::hasColumn('employees', 'employee_import_name')) {
                $table->string('employee_import_name')->nullable()->after('employee_import_batch_id');
            }

            if (! Schema::hasColumn('employees', 'employee_imported_at')) {
                $table->timestamp('employee_imported_at')->nullable()->after('employee_import_name')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'employee_imported_at')) {
                $table->dropColumn('employee_imported_at');
            }

            if (Schema::hasColumn('employees', 'employee_import_name')) {
                $table->dropColumn('employee_import_name');
            }

            if (Schema::hasColumn('employees', 'employee_import_batch_id')) {
                $table->dropColumn('employee_import_batch_id');
            }
        });
    }
};
