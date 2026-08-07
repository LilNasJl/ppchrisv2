<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeEmploymentTypeChange;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmploymentTypeChangeService
{
    public function save(
        Employee $employee,
        string $employmentType,
        string $effectiveDate,
        ?string $explanation,
        ?User $changedBy = null,
    ): EmployeeEmploymentTypeChange {
        if (! in_array($employmentType, Employee::EMPLOYMENT_TYPES, true)) {
            throw new InvalidArgumentException('The selected employment type is invalid.');
        }

        $explanation = trim((string) $explanation);
        $explanation = $explanation !== '' ? $explanation : null;

        if ($explanation !== null && mb_strlen($explanation) > 2000) {
            throw new InvalidArgumentException('The employment type change explanation may not exceed 2,000 characters.');
        }

        $date = Carbon::parse($effectiveDate)->startOfDay();

        if ($date->isFuture()) {
            throw new InvalidArgumentException('The employment type change date cannot be in the future.');
        }

        return DB::transaction(function () use ($employee, $employmentType, $date, $explanation, $changedBy): EmployeeEmploymentTypeChange {
            $lockedEmployee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $latestChange = $lockedEmployee->employmentTypeChanges()->first();
            $isLatestChangeCorrection = $latestChange
                && $latestChange->employment_type === $employmentType
                && $lockedEmployee->employment_type === $employmentType;

            if ($isLatestChangeCorrection) {
                $latestChange->update([
                    'effective_date' => $date->toDateString(),
                    'explanation' => $explanation,
                    'changed_by_user_id' => $changedBy?->id,
                ]);

                return $latestChange->refresh();
            }

            $change = $lockedEmployee->employmentTypeChanges()->create([
                'previous_type' => $lockedEmployee->employment_type,
                'employment_type' => $employmentType,
                'effective_date' => $date->toDateString(),
                'explanation' => $explanation,
                'changed_by_user_id' => $changedBy?->id,
            ]);

            $lockedEmployee->forceFill([
                'employment_type' => $employmentType,
            ])->save();

            return $change->refresh();
        });
    }
}
