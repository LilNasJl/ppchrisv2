<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'fingerprint_id')) {
                $table->string('fingerprint_id')
                    ->nullable()
                    ->after('designation_id');
            }

            if (! Schema::hasColumn('employees', 'bank_id_no')) {
                $table->string('bank_id_no')
                    ->nullable()
                    ->after(Schema::hasColumn('employees', 'sss') ? 'sss' : 'email');
            }

            if (! Schema::hasColumn('employees', 'salary_adjustment')) {
                $table->decimal('salary_adjustment', 12, 2)
                    ->default(0)
                    ->after('allowance');
            }
        });

        if (Schema::hasColumn('employees', 'fingerprint_id')) {
            DB::statement('ALTER TABLE employees MODIFY fingerprint_id VARCHAR(191) NULL');
        }

        if (Schema::hasColumn('employees', 'salary_adjustment')) {
            DB::table('employees')
                ->whereNull('salary_adjustment')
                ->update(['salary_adjustment' => 0]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('employees', 'bank_id_no') ? 'bank_id_no' : null,
                Schema::hasColumn('employees', 'salary_adjustment') ? 'salary_adjustment' : null,
                Schema::hasColumn('employees', 'fingerprint_id') ? 'fingerprint_id' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
