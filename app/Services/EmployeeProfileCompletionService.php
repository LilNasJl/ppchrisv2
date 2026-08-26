<?php

namespace App\Services;

use App\Models\Employee;

class EmployeeProfileCompletionService
{
    /**
     * @return array{basic:int, designation:int, education:int, salary:int}
     */
    public function sectionCounts(?Employee $employee): array
    {
        if (! $employee) {
            return [
                'basic' => 0,
                'designation' => 0,
                'education' => 0,
                'salary' => 0,
            ];
        }

        $salaryFields = ['rate_type', 'payment_type'];
        $salaryFields[] = str($employee->rate_type ?? '')->lower()->contains('month')
            ? 'monthly_rate'
            : 'daily_rate';

        return [
            'basic' => $this->countMissing($employee, [
                'firstname',
                'lastname',
                'birthdate',
                'status',
                'gender',
                'kids',
                'address',
            ]),
            'designation' => $this->countMissing($employee, [
                'designation_id',
                'department_id',
                'branch_id',
                'hired_date',
                'employment_type',
            ]),
            'education' => $this->countMissing($employee, [
                'school_name',
                'school_level',
                'year_grad',
            ]),
            'salary' => $this->countMissing($employee, $salaryFields, positiveNumericFields: [
                'daily_rate',
                'monthly_rate',
            ]),
        ];
    }

    public function total(?Employee $employee): int
    {
        return array_sum($this->sectionCounts($employee));
    }

    public function section(?Employee $employee, string $section): int
    {
        return $this->sectionCounts($employee)[$section] ?? 0;
    }

    /**
     * @param  array<int, string>  $fields
     * @param  array<int, string>  $positiveNumericFields
     */
    protected function countMissing(
        Employee $employee,
        array $fields,
        array $positiveNumericFields = [],
    ): int {
        return collect($fields)
            ->filter(function (string $field) use ($employee, $positiveNumericFields): bool {
                $value = $employee->getAttribute($field);

                if (in_array($field, $positiveNumericFields, true)) {
                    return blank($value) || (float) $value <= 0;
                }

                return blank($value) && $value !== 0 && $value !== '0';
            })
            ->count();
    }
}
