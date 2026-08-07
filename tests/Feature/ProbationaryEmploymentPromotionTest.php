<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmploymentTypeChangeService;
use App\Services\ProbationaryEmploymentPromotionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ProbationaryEmploymentPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_eligible_probationary_employees_are_promoted(): void
    {
        $eligible = $this->employee('0001', 'Probationary', '2026-01-31');
        $notYetEligible = $this->employee('0002', 'Probationary', '2026-02-01');
        $contractual = $this->employee('0003', 'Contractual', '2025-01-01');
        $resigned = $this->employee('0004', 'Resigned', '2025-01-01');

        $promoted = app(ProbationaryEmploymentPromotionService::class)
            ->promoteEligible(CarbonImmutable::parse('2026-07-31', 'Asia/Manila'));

        $this->assertSame(1, $promoted);
        $this->assertSame('Permanent', $eligible->refresh()->employment_type);
        $this->assertSame('Probationary', $notYetEligible->refresh()->employment_type);
        $this->assertSame('Contractual', $contractual->refresh()->employment_type);
        $this->assertSame('Resigned', $resigned->refresh()->employment_type);

        $this->assertDatabaseHas('employee_employment_type_changes', [
            'employee_id' => $eligible->id,
            'previous_type' => 'Probationary',
            'employment_type' => 'Permanent',
            'effective_date' => '2026-07-31',
            'explanation' => ProbationaryEmploymentPromotionService::AUTOMATIC_EXPLANATION,
            'changed_by_user_id' => null,
        ]);
    }

    public function test_promotion_is_idempotent(): void
    {
        $employee = $this->employee('0001', 'Probationary', '2026-01-15');
        $service = app(ProbationaryEmploymentPromotionService::class);
        $asOf = CarbonImmutable::parse('2026-07-31', 'Asia/Manila');

        $this->assertSame(1, $service->promoteEligible($asOf));
        $this->assertSame(0, $service->promoteEligible($asOf));
        $this->assertSame('Permanent', $employee->refresh()->employment_type);
        $this->assertDatabaseCount('employee_employment_type_changes', 1);
    }

    public function test_manual_employment_change_accepts_no_explanation(): void
    {
        $employee = $this->employee('0001', 'Temporary', '2026-01-01');

        $change = app(EmploymentTypeChangeService::class)->save(
            employee: $employee,
            employmentType: 'Probationary',
            effectiveDate: '2026-07-01',
            explanation: null,
        );

        $this->assertNull($change->explanation);
        $this->assertSame('Probationary', $employee->refresh()->employment_type);
    }

    public function test_manual_employment_change_accepts_a_date_earlier_than_the_latest_change(): void
    {
        $employee = $this->employee('0001', 'Temporary', '2026-01-01');
        $service = app(EmploymentTypeChangeService::class);

        $service->save(
            employee: $employee,
            employmentType: 'Probationary',
            effectiveDate: '2026-07-01',
            explanation: null,
        );

        $backdatedChange = $service->save(
            employee: $employee->refresh(),
            employmentType: 'Contractual',
            effectiveDate: '2026-06-01',
            explanation: 'Backdated correction.',
        );

        $this->assertSame('2026-06-01', $backdatedChange->effective_date->toDateString());
        $this->assertSame('Contractual', $employee->refresh()->employment_type);
        $this->assertDatabaseCount('employee_employment_type_changes', 2);
    }

    protected function employee(string $uid, string $employmentType, string $hiredDate): Employee
    {
        $user = User::factory()->create([
            'username' => 'PF'.$uid,
            'role' => 'employee',
            'is_disabled' => false,
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,
            'uid' => $uid,
            'firstname' => 'Employee',
            'lastname' => $uid,
            'hired_date' => $hiredDate,
            'employment_type' => $employmentType,
            'rate_type' => 'monthly',
            'monthly_rate' => 26000,
        ]);
    }
}
