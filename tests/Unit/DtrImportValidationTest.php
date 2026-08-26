<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Employee;
use App\Services\Imports\DtrImportService;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use Tests\TestCase;

class DtrImportValidationTest extends TestCase
{
    public function test_incomplete_timeout_does_not_require_out_or_schedule_fields(): void
    {
        $service = new TestableDtrImportService;

        $errors = $service->requiredColumnErrors([
            'Batch ID' => 'BATCH-1',
            'Period ID' => 1,
            'Branch ID' => 1,
            'Fingerprint ID' => 1234,
            'Date In' => '2026-08-22',
            'Time In' => '08:00:00',
            'Date Out' => '',
            'Time Out' => '',
            'Schedule Type' => 'Regular',
            'Schedule Start' => '',
            'Schedule End' => '',
        ]);

        $this->assertSame([], $errors);
    }

    public function test_monthly_regular_employee_uses_saturday_schedule(): void
    {
        $service = new TestableDtrImportService;
        $service->employee = (new Employee)->forceFill(['rate_type' => 'monthly']);

        $this->assertSame('Saturday', $service->scheduleType([
            'date_in' => '2026-08-22',
            'schedule_type' => 'Regular',
        ]));
    }

    public function test_monthly_employee_uses_saturday_schedule_when_biometric_label_is_a_shift(): void
    {
        $service = new TestableDtrImportService;
        $service->employee = (new Employee)->forceFill(['rate_type' => 'Monthly']);

        $this->assertSame('Saturday', $service->scheduleType([
            'date_in' => '2026-08-22',
            'schedule_type' => 'Shift2',
        ]));
    }

    public function test_monthly_saturday_overtime_and_forgot_to_punch_keep_their_special_types(): void
    {
        $service = new TestableDtrImportService;
        $service->employee = (new Employee)->forceFill(['rate_type' => 'monthly']);

        $this->assertSame('Overtime', $service->scheduleType([
            'date_in' => '2026-08-22',
            'schedule_type' => 'Overtime',
        ]));
        $this->assertSame('Forgot to Punch', $service->scheduleType([
            'date_in' => '2026-08-22',
            'schedule_type' => 'Forgot to Punch',
        ]));
    }

    public function test_hr_and_sicrc_incomplete_punches_are_for_approval_not_absent(): void
    {
        $data = [
            'date_in' => '2026-08-24',
            'time_in' => '08:05:00',
            'date_out' => null,
            'time_out' => null,
            'schedule_type' => 'Forgot to Punch',
            'schedule_start' => null,
            'schedule_end' => null,
        ];

        foreach ([new TestableDtrImportService, new TestableEmployeeVisibleDtrImportService] as $service) {
            $calculation = $service->calculationData($data);

            $this->assertSame('Forgot to Punch', $calculation['schedule_type']);
            $this->assertFalse($calculation['is_absent']);
            $this->assertSame(0, $calculation['absence_minutes']);
            $this->assertSame(0, $calculation['late']);
            $this->assertSame(0, $calculation['undertime']);
            $this->assertSame(0, $calculation['credited_overtime']);
            $this->assertSame(0, $calculation['credited_work_hrs']);
        }
    }

    public function test_monthly_shift_labeled_saturday_import_is_stored_as_a_three_hour_saturday_entry(): void
    {
        $service = new TestableDtrImportService;
        $service->employee = (new Employee)->forceFill(['rate_type' => 'monthly']);

        $calculation = $service->calculationData([
            'date_in' => '2026-08-22',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-22',
            'time_out' => '11:00:00',
            'schedule_type' => 'Shift2',
            'schedule_start' => '12:30:00',
            'schedule_end' => '20:30:00',
        ]);

        $this->assertSame('Saturday', $calculation['schedule_type']);
        $this->assertSame('08:00:00', $calculation['schedule_start']);
        $this->assertSame('11:00:00', $calculation['schedule_end']);
        $this->assertSame(180, $calculation['credited_work_hrs']);
    }

    public function test_sicrc_importer_inherits_monthly_saturday_schedule_resolution(): void
    {
        $service = new TestableEmployeeVisibleDtrImportService;
        $service->employee = (new Employee)->forceFill(['rate_type' => 'monthly']);

        $this->assertSame('Saturday', $service->scheduleType([
            'date_in' => '2026-08-22',
            'schedule_type' => 'Shift1',
        ]));
    }

    public function test_daily_employee_keeps_imported_shift_on_saturday(): void
    {
        $service = new TestableDtrImportService;
        $service->employee = (new Employee)->forceFill([
            'rate_type' => 'daily',
            'schedule_type' => 'daily',
        ]);
        $service->branch = (new Branch)->forceFill([
            'shift2_start' => '12:30:00',
            'shift2_end' => '20:30:00',
        ]);

        $this->assertSame('Shift2', $service->scheduleType([
            'date_in' => '2026-08-22',
            'schedule_type' => 'Saturday',
            'schedule_start' => '12:30:00',
            'schedule_end' => '20:30:00',
        ]));
    }
}

class TestableDtrImportService extends DtrImportService
{
    public ?Employee $employee = null;

    public ?Branch $branch = null;

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    public function requiredColumnErrors(array $row): array
    {
        return $this->getRequiredColumnErrors($row);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function scheduleType(array $data): string
    {
        return $this->resolveScheduleType($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function calculationData(array $data): array
    {
        return $this->calculateImportedDtrData($data);
    }

    protected function getEmployee(array $data): ?Employee
    {
        return $this->employee;
    }

    protected function getBranch(array $data): ?Branch
    {
        return $this->branch;
    }
}

class TestableEmployeeVisibleDtrImportService extends EmployeeVisibleDtrImportService
{
    public ?Employee $employee = null;

    /**
     * @param  array<string, mixed>  $data
     */
    public function scheduleType(array $data): string
    {
        return $this->resolveScheduleType($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function calculationData(array $data): array
    {
        return $this->calculateImportedDtrData($data);
    }

    protected function getEmployee(array $data): ?Employee
    {
        return $this->employee;
    }
}
