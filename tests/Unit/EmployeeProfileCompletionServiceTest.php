<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Services\EmployeeProfileCompletionService;
use Tests\TestCase;

class EmployeeProfileCompletionServiceTest extends TestCase
{
    public function test_it_counts_missing_fields_by_profile_section(): void
    {
        $employee = (new Employee)->forceFill([
            'firstname' => 'Juan',
            'lastname' => 'Cruz',
            'birthdate' => '2000-01-01',
            'status' => 'single',
            'gender' => 'male',
            'kids' => 0,
            'address' => 'Tagum City',
            'designation_id' => 1,
            'department_id' => 1,
            'branch_id' => 1,
            'hired_date' => '2026-01-01',
            'employment_type' => 'Permanent',
            'school_name' => null,
            'school_level' => null,
            'year_grad' => null,
            'rate_type' => 'monthly',
            'payment_type' => 'atm',
            'monthly_rate' => 25000,
        ]);

        $counts = (new EmployeeProfileCompletionService)->sectionCounts($employee);

        $this->assertSame(0, $counts['basic']);
        $this->assertSame(0, $counts['designation']);
        $this->assertSame(3, $counts['education']);
        $this->assertSame(0, $counts['salary']);
        $this->assertSame(3, array_sum($counts));
    }
}
