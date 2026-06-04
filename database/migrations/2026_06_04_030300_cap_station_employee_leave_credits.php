<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasTable('designations')) {
            return;
        }

        DB::table('employees')
            ->join('designations', 'employees.designation_id', '=', 'designations.id')
            ->whereIn(DB::raw('UPPER(TRIM(designations.title))'), Employee::STATION_SIX_LEAVE_DESIGNATIONS)
            ->where(function ($query): void {
                $query
                    ->whereNull('employees.leave_credits_year')
                    ->orWhere('employees.leave_credits_year', now()->year);
            })
            ->where('employees.leave_credits', '>', 6)
            ->update([
                'employees.leave_credits' => 6,
                'employees.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
