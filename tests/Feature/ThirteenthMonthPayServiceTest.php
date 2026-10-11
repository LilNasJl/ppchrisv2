<?php

use App\Filament\Pages\ThirteenthMonthPay;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollSnapshot;
use App\Models\ThirteenthMonthRelease;
use App\Models\User;
use App\Services\ThirteenthMonthPayService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('calculates accrual using twelve and reconciles the full year from locked snapshots', function (): void {
    $branch = thirteenthBranch('Admin');
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
        ->and($midyear['calculated_amount'])->toBe(2112.5)
        ->and($midyear['months'][4])->toBe(0.0)
        ->and($midyear['period_amounts']['4_second'])->toBe(0.0)
        ->and($midyearColumns['4_second']['status'])->toBe('Open')
        ->and($midyearColumns['6_second']['status'])->toBe('Locked')
        ->and($yearEnd['basis_total'])->toBe(38350.0)
        ->and($yearEnd['calculated_amount'])->toBe(3195.83)
        ->and($wholeYear['basis_total'])->toBe(38350.0)
        ->and($wholeYear['calculated_amount'])->toBe(3195.83);
});

it('freezes released amounts in a release ledger', function (): void {
    $branch = thirteenthBranch('Admin');
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
        ->and((float) $release->released_amount)->toBe(491.67)
        ->and($releasedRow['released'])->toBeTrue()
        ->and(ThirteenthMonthRelease::query()->count())->toBe(1);
});

it('deducts the midyear payout from year end and preserves payment history', function (): void {
    $branch = thirteenthBranch('Admin');
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
        ->and($service->rows(2026, ThirteenthMonthPayService::YEAR_END)->sole()['pending_amount'])->toBe(1041.66);

    expect(fn () => $service->setReleaseStatus(
        $service->rows(2026, ThirteenthMonthPayService::MIDYEAR),
        2026,
        ThirteenthMonthPayService::MIDYEAR,
        null,
        'pending',
    ))->toThrow(InvalidArgumentException::class);
    $yearEnd = $service->rows(2026, ThirteenthMonthPayService::YEAR_END)->sole();
    $payment = $service->release($yearEnd, 2026, ThirteenthMonthPayService::YEAR_END, null);
    $repeat = $service->release($yearEnd, 2026, ThirteenthMonthPayService::YEAR_END, null);
    expect($repeat->id)->toBe($payment->id)
        ->and(ThirteenthMonthRelease::query()->count())->toBe(2)
        ->and($service->rows(2026, ThirteenthMonthPayService::WHOLE_YEAR)->sole()['pending_amount'])->toBe(0.0);
});

it('pays the same annual amount once or in two payouts without including extras', function (bool $split): void {
    $branch = thirteenthBranch('Admin');
    $employee = Employee::query()->create(['uid' => 'TEST-1', 'firstname' => 'Test', 'branch_id' => $branch->id]);
    foreach (range(1, 12) as $month) {
        payrollSnapshot(payrollPeriod(sprintf('2026-%02d-15', $month), true), $employee, $branch, [
            'half_month_pay' => 26000, 'overtime' => 4000, 'allowance' => 2000,
            'holiday_pay' => 1000, 'salary_adjustment' => 500,
        ]);
    }
    $service = app(ThirteenthMonthPayService::class);
    if ($split) {
        $payment = $service->release($service->rows(2026, 'midyear')->sole(), 2026, 'midyear', null);
        expect((float) $payment->released_amount)->toBe(13000.0);
    }
    $payment = $service->release($service->rows(2026, 'year_end')->sole(), 2026, 'year_end', null);
    expect((float) $payment->released_amount)->toBe($split ? 13000.0 : 26000.0)
        ->and((float) ThirteenthMonthRelease::query()->sum('released_amount'))->toBe(26000.0);
})->with([true, false]);

it('aggregates transferred employees across branches and filters by latest payroll branch', function (): void {
    $a = thirteenthBranch('A');
    $b = thirteenthBranch('B');
    $employee = Employee::query()->create(['uid' => 'TRANSFER', 'firstname' => 'Test', 'branch_id' => $b->id]);
    payrollSnapshot(payrollPeriod('2026-01-15', true), $employee, $a, ['half_month_pay' => 12000]);
    payrollSnapshot(payrollPeriod('2026-07-15', true), $employee, $b, ['half_month_pay' => 24000]);
    $service = app(ThirteenthMonthPayService::class);
    expect($service->rows(2026, 'year_end', $b->id)->sole()['calculated_amount'])->toBe(3000.0)
        ->and($service->rows(2026, 'year_end', $a->id))->toHaveCount(0);
});

it('blocks recording while payroll is open and never trusts a supplied amount', function (): void {
    $branch = thirteenthBranch('A');
    $employee = Employee::query()->create(['uid' => 'SAFE', 'firstname' => 'Test', 'branch_id' => $branch->id]);
    payrollSnapshot(payrollPeriod('2026-01-15', true), $employee, $branch, ['half_month_pay' => 12000]);
    $open = payrollPeriod('2026-02-15', false);
    $service = app(ThirteenthMonthPayService::class);
    $row = $service->rows(2026, 'midyear')->sole();
    expect(fn () => $service->release($row, 2026, 'midyear', null))->toThrow(InvalidArgumentException::class);
    $open->update(['is_locked' => true, 'locked_at' => now()]);
    $row['calculated_amount'] = 999999;
    $row['pending_amount'] = 999999;
    $payment = $service->release($row, 2026, 'midyear', null);
    expect((float) $payment->released_amount)->toBe(1000.0);
});

it('flags historical overpayments without rewriting the original payment', function (): void {
    $branch = thirteenthBranch('A');
    $employee = Employee::query()->create(['uid' => 'LEGACY', 'firstname' => 'Test', 'branch_id' => $branch->id]);
    payrollSnapshot(payrollPeriod('2026-01-15', true), $employee, $branch, ['half_month_pay' => 12000]);
    $payment = ThirteenthMonthRelease::query()->create(['employee_id' => $employee->id, 'year' => 2026, 'segment' => 'midyear', 'released_amount' => 2000, 'basis_amount' => 12000, 'released_at' => now()]);
    $row = app(ThirteenthMonthPayService::class)->rows(2026, 'whole_year')->sole();
    expect($row['overpaid_amount'])->toBe(1000.0)->and($row['pending_amount'])->toBe(0.0)
        ->and((float) $payment->fresh()->released_amount)->toBe(2000.0);
});

function thirteenthBranch(string $name): Branch
{
    return Branch::query()->create(['branch_name' => $name, 'branch_address' => 'Test', 'mobile_no' => '0', 'employee_id' => 0]);
}

it('renders the revised payout screen and rejects unauthorized print access', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('hr'));
    $this->get('/hr-tools/13th-month-pay/print?year=2026&type=year_end&view=summary')->assertForbidden();
    $user = User::withoutEvents(fn () => User::query()->firstOrCreate(['username' => 'masteradmin'], [
        'name' => 'Master Admin', 'username' => 'masteradmin', 'email' => 'master@test.invalid',
        'password' => 'password', 'role' => 'admin',
    ]));
    $this->actingAs($user);
    Livewire::test(ThirteenthMonthPay::class)
        ->assertSee('Record Payout')->assertSee('Remaining Balance')->assertSee('Accrued Entitlement')
        ->set('calculation_type', '')
        ->assertSee('Select a valid payout before recording payment.')
        ->set('calculation_type', null)
        ->assertSee('Select a valid payout before recording payment.')
        ->set('calculation_type', 'unsupported')
        ->assertSee('Select a valid payout before recording payment.')
        ->set('calculation_type', 'midyear')->assertSee('January to June')
        ->set('calculation_type', 'year_end')->assertSee('January to December')
        ->set('calculation_type', 'whole_year')->assertSee('January to December');
    $this->get('/hr-tools/13th-month-pay/print?year=2026&type=year_end&view=summary')
        ->assertOk()->assertSee('divided by 12');
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
