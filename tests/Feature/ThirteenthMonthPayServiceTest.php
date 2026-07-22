<?php

use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollSnapshot;
use App\Models\ThirteenthMonthRelease;
use App\Services\ThirteenthMonthPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates each half year from locked payroll snapshots using a divisor of six', function (): void {
    $branch = Branch::query()->create(['branch_name' => 'Admin']);
    $employee = Employee::query()->create([
        'uid' => 'PF-0001',
        'firstname' => 'Juan',
        'lastname' => 'Dela Cruz',
        'branch_id' => $branch->id,
        'rate_type' => 'monthly',
    ]);

    $january = payrollPeriod('2026-01-15', true);
    $june = payrollPeriod('2026-06-30', true);
    $july = payrollPeriod('2026-07-15', true);
    $unlockedApril = payrollPeriod('2026-04-30', false);

    payrollSnapshot($january, $employee, $branch, [
        'rate' => 'Monthly', 'half_month_pay' => 13000, 'late' => 100,
        'undertime_amount' => 50, 'halfday' => 0, 'absent' => 0,
    ]);
    payrollSnapshot($june, $employee, $branch, [
        'rate' => 'Monthly', 'half_month_pay' => 13000, 'late' => 0,
        'undertime_amount' => 0, 'halfday' => 500, 'absent' => 0,
    ]);
    payrollSnapshot($july, $employee, $branch, [
        'rate' => 'Monthly', 'half_month_pay' => 13000, 'late' => 0,
        'undertime_amount' => 0, 'halfday' => 0, 'absent' => 0,
    ]);
    payrollSnapshot($unlockedApril, $employee, $branch, [
        'rate' => 'Monthly', 'half_month_pay' => 99000, 'late' => 0,
        'undertime_amount' => 0, 'halfday' => 0, 'absent' => 0,
    ]);

    $service = app(ThirteenthMonthPayService::class);
    $midyear = $service->rows(2026, ThirteenthMonthPayService::MIDYEAR)->sole();
    $yearEnd = $service->rows(2026, ThirteenthMonthPayService::YEAR_END)->sole();
    $wholeYear = $service->rows(2026, ThirteenthMonthPayService::WHOLE_YEAR)->sole();
    $midyearColumns = $service->periodColumns(2026, ThirteenthMonthPayService::MIDYEAR);

    expect($midyear['basis_total'])->toBe(25350.0)
        ->and($midyear['calculated_amount'])->toBe(4225.0)
        ->and($midyear['months'][4])->toBe(0.0)
        ->and($midyear['period_amounts']['4_second'])->toBe(0.0)
        ->and($midyearColumns['4_second']['status'])->toBe('Open')
        ->and($midyearColumns['6_second']['status'])->toBe('Locked')
        ->and($yearEnd['basis_total'])->toBe(13000.0)
        ->and($yearEnd['calculated_amount'])->toBe(2166.67)
        ->and($wholeYear['basis_total'])->toBe(38350.0)
        ->and($wholeYear['calculated_amount'])->toBe(3195.83);
});

it('freezes released amounts in a release ledger', function (): void {
    $branch = Branch::query()->create(['branch_name' => 'Admin']);
    $employee = Employee::query()->create([
        'uid' => 'PF-0002',
        'firstname' => 'Maria',
        'lastname' => 'Santos',
        'branch_id' => $branch->id,
        'rate_type' => 'daily',
    ]);
    $period = payrollPeriod('2026-03-31', true);

    payrollSnapshot($period, $employee, $branch, [
        'rate' => 'Daily', 'total_pay' => 6000, 'late' => 60, 'undertime_amount' => 40,
    ]);

    $service = app(ThirteenthMonthPayService::class);
    $row = $service->rows(2026, ThirteenthMonthPayService::MIDYEAR)->sole();
    $release = $service->release($row, 2026, ThirteenthMonthPayService::MIDYEAR, null);
    $releasedRow = $service->rows(2026, ThirteenthMonthPayService::MIDYEAR)->sole();

    expect((float) $release->basis_amount)->toBe(5900.0)
        ->and((float) $release->released_amount)->toBe(983.33)
        ->and($releasedRow['released'])->toBeTrue()
        ->and(ThirteenthMonthRelease::query()->count())->toBe(1);
});

it('keeps Midyear and Year End release statuses independent', function (): void {
    $branch = Branch::query()->create(['branch_name' => 'Admin']);
    $employee = Employee::query()->create([
        'uid' => 'PF-0003',
        'firstname' => 'Christopher',
        'lastname' => 'Aldepolla',
        'branch_id' => $branch->id,
        'rate_type' => 'monthly',
    ]);
    $midyearPeriod = payrollPeriod('2026-06-30', true);
    $yearEndPeriod = payrollPeriod('2026-07-15', true);

    payrollSnapshot($midyearPeriod, $employee, $branch, [
        'rate' => 'Monthly', 'half_month_pay' => 12500, 'late' => 0,
        'undertime_amount' => 0, 'halfday' => 0, 'absent' => 0,
    ]);
    payrollSnapshot($yearEndPeriod, $employee, $branch, [
        'rate' => 'Monthly', 'half_month_pay' => 12500, 'late' => 0,
        'undertime_amount' => 0, 'halfday' => 0, 'absent' => 0,
    ]);

    $service = app(ThirteenthMonthPayService::class);
    $midyear = $service->rows(2026, ThirteenthMonthPayService::MIDYEAR)->sole();
    $service->release($midyear, 2026, ThirteenthMonthPayService::MIDYEAR, null);

    expect($service->rows(2026, ThirteenthMonthPayService::MIDYEAR)->sole()['release_status'])->toBe('Released')
        ->and($service->rows(2026, ThirteenthMonthPayService::YEAR_END)->sole()['release_status'])->toBe('Pending');

    $service->setReleaseStatus(
        $service->rows(2026, ThirteenthMonthPayService::MIDYEAR),
        2026,
        ThirteenthMonthPayService::MIDYEAR,
        null,
        'pending',
    );

    expect($service->rows(2026, ThirteenthMonthPayService::MIDYEAR)->sole()['release_status'])->toBe('Pending')
        ->and(ThirteenthMonthRelease::query()->count())->toBe(0);

    $wholeYear = $service->rows(2026, ThirteenthMonthPayService::WHOLE_YEAR)->sole();
    $service->release($wholeYear, 2026, ThirteenthMonthPayService::WHOLE_YEAR, null);

    expect($service->rows(2026, ThirteenthMonthPayService::WHOLE_YEAR)->sole()['release_status'])->toBe('Released')
        ->and($service->rows(2026, ThirteenthMonthPayService::MIDYEAR)->sole()['release_status'])->toBe('Released via Whole Year')
        ->and($service->releaseConflict(2026, ThirteenthMonthPayService::MIDYEAR))->not->toBeNull();

    expect(fn () => $service->release($midyear, 2026, ThirteenthMonthPayService::MIDYEAR, null))
        ->toThrow(InvalidArgumentException::class);
});

function payrollPeriod(string $payoutDate, bool $locked): PayrollPeriod
{
    return PayrollPeriod::query()->create([
        'title' => $payoutDate,
        'date_start' => $payoutDate,
        'date_end' => $payoutDate,
        'date_payout' => $payoutDate,
        'is_locked' => $locked,
        'locked_at' => $locked ? $payoutDate.' 12:00:00' : null,
    ]);
}

function payrollSnapshot(PayrollPeriod $period, Employee $employee, Branch $branch, array $data): PayrollSnapshot
{
    return PayrollSnapshot::query()->create([
        'payroll_period_id' => $period->id,
        'employee_id' => $employee->id,
        'branch_id' => $branch->id,
        'row_number' => 1,
        'data' => array_merge([
            'name' => $employee->full_name,
            'branch' => $branch->branch_name,
            'designation' => 'Staff',
        ], $data),
    ]);
}
