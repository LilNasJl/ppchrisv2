<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProbationaryEmploymentPromotionService
{
    public const AUTOMATIC_EXPLANATION = 'Automatically changed to Permanent after completing six months of service.';

    public function promoteEligible(?CarbonInterface $asOf = null): int
    {
        $asOfDate = $asOf
            ? CarbonImmutable::instance($asOf)->startOfDay()
            : CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $promoted = 0;

        Employee::query()
            ->where('employment_type', 'Probationary')
            ->whereNotNull('hired_date')
            ->whereDate('hired_date', '<=', $asOfDate->toDateString())
            ->orderBy('id')
            ->chunkById(200, function ($employees) use ($asOfDate, &$promoted): void {
                foreach ($employees as $employee) {
                    $anniversary = CarbonImmutable::instance($employee->hired_date)
                        ->startOfDay()
                        ->addMonthsNoOverflow(6);

                    if ($anniversary->isAfter($asOfDate)) {
                        continue;
                    }

                    try {
                        app(EmploymentTypeChangeService::class)->save(
                            employee: $employee,
                            employmentType: 'Permanent',
                            effectiveDate: $anniversary->toDateString(),
                            explanation: self::AUTOMATIC_EXPLANATION,
                        );

                        $promoted++;
                    } catch (InvalidArgumentException $exception) {
                        Log::warning('Automatic employment type promotion was skipped.', [
                            'employee_id' => $employee->id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return $promoted;
    }
}
