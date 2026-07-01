<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'employee_attachment_path')) {
                $table->string('employee_attachment_path')->nullable()->after('description');
            }

            if (! Schema::hasColumn('tickets', 'employee_attachment_original_name')) {
                $table->string('employee_attachment_original_name')->nullable()->after('employee_attachment_path');
            }

            if (! Schema::hasColumn('tickets', 'hr_attachment_path')) {
                $table->string('hr_attachment_path')->nullable()->after('hr_comment');
            }

            if (! Schema::hasColumn('tickets', 'hr_attachment_original_name')) {
                $table->string('hr_attachment_original_name')->nullable()->after('hr_attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $columns = [
                'hr_attachment_original_name',
                'hr_attachment_path',
                'employee_attachment_original_name',
                'employee_attachment_path',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
