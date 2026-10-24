<?php

use App\Models\EmployeeLoan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191)->unique();
            $table->timestamps();
        });

        $names = collect(array_keys(EmployeeLoan::loanTypeOptions()))
            ->merge(DB::table('employee_loans')->whereNotNull('loan_type')->distinct()->pluck('loan_type'))
            ->merge(DB::table('employee_loan_requests')->whereNotNull('loan_type')->distinct()->pluck('loan_type'))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique(fn (string $name): string => mb_strtolower($name));

        foreach ($names as $name) {
            DB::table('loan_types')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_types');
    }
};
