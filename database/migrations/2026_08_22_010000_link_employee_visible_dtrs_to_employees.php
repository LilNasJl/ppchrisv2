<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_visible_dtrs', function (Blueprint $table): void {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('employees')
                ->nullOnDelete();

            $table->index(
                ['payroll_period_id', 'branch_id', 'employee_id', 'date_in'],
                'employee_visible_dtrs_employee_scope_index',
            );
        });

        $employeesByBranch = DB::table('employees')
            ->whereNull('deleted_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('employment_type')
                    ->orWhereNotIn('employment_type', [
                        'Resigned',
                        'Terminated',
                        'Force Resigned',
                        'Death of Employee',
                        'Death Employee',
                    ]);
            })
            ->get(['id', 'branch_id', 'fingerprint_id'])
            ->filter(fn (object $employee): bool => filled($employee->branch_id) && filled($employee->fingerprint_id))
            ->groupBy('branch_id');

        DB::table('employee_visible_dtrs')
            ->whereNull('employee_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($employeesByBranch): void {
                foreach ($rows as $row) {
                    $identity = $this->canonicalIdentity($row->fingerprint_id);
                    $matches = collect($employeesByBranch->get($row->branch_id, []))
                        ->filter(fn (object $employee): bool => $this->canonicalIdentity($employee->fingerprint_id) === $identity)
                        ->values();

                    if ($identity === '' || $matches->count() !== 1) {
                        continue;
                    }

                    DB::table('employee_visible_dtrs')
                        ->where('id', $row->id)
                        ->update(['employee_id' => $matches->first()->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('employee_visible_dtrs', function (Blueprint $table): void {
            $table->dropIndex('employee_visible_dtrs_employee_scope_index');
            $table->dropConstrainedForeignId('employee_id');
        });
    }

    private function canonicalIdentity(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return ctype_digit($value)
            ? (ltrim($value, '0') ?: '0')
            : mb_strtolower($value);
    }
};
