<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            if (Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->string('email', 255)->nullable()->change();
                });
            }

            if (Schema::hasTable('employees')) {
                Schema::table('employees', function (Blueprint $table): void {
                    if (Schema::hasColumn('employees', 'user_id')) {
                        $table->integer('user_id')->nullable()->change();
                    }

                    foreach (array_keys($this->employeeNullableColumns()) as $column) {
                        if (Schema::hasColumn('employees', $column)) {
                            $table->string($column)->nullable()->change();
                        }
                    }
                });
            }

            return;
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        }

        if (! Schema::hasTable('employees')) {
            return;
        }

        foreach ($this->employeeNullableColumns() as $column => $definition) {
            if (Schema::hasColumn('employees', $column)) {
                DB::statement("ALTER TABLE employees MODIFY {$column} {$definition} NULL");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
            DB::table('users')
                ->whereNull('email')
                ->update([
                    'email' => DB::raw("CONCAT('user-', id, '@local.test')"),
                ]);

            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        }
    }

    private function employeeNullableColumns(): array
    {
        return [
            'firstname' => 'VARCHAR(255)',
            'middlename' => 'VARCHAR(255)',
            'lastname' => 'VARCHAR(255)',
            'gender' => 'VARCHAR(255)',
            'birthdate' => 'DATE',
            'status' => 'VARCHAR(255)',
            'address' => 'VARCHAR(255)',
            'mobile' => 'VARCHAR(255)',
            'kids' => 'SMALLINT',
            'email' => 'VARCHAR(255)',
            'designation_id' => 'INT',
            'department_id' => 'INT',
            'branch_id' => 'INT',
            'fingerprint_id' => 'VARCHAR(191)',
            'hired_date' => 'DATE',
            'employment_type' => 'VARCHAR(255)',
            'school_name' => 'VARCHAR(255)',
            'school_level' => 'VARCHAR(255)',
            'year_grad' => 'DATE',
            'rate_type' => 'VARCHAR(255)',
            'payment_type' => 'VARCHAR(255)',
            'daily_rate' => 'DOUBLE',
            'monthly_rate' => 'DOUBLE',
            'allowance' => 'DOUBLE',
            'gsis' => 'VARCHAR(20)',
            'philhealth' => 'VARCHAR(20)',
            'pagibig' => 'VARCHAR(20)',
            'tin' => 'VARCHAR(20)',
            'sss' => 'VARCHAR(20)',
            'bank_id_no' => 'VARCHAR(191)',
        ];
    }
};
