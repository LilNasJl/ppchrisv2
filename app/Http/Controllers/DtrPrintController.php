<?php

namespace App\Http\Controllers;

use App\Filament\Pages\DtrManage;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\DtrAttendanceUnitService;
use App\Services\DtrDayPartService;
use App\Services\DtrRecordService;
use App\Support\CompanyExportHeader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DtrPrintController extends Controller
{
    public function __invoke(
        Request $request,
        string $period,
        string $branch,
        string $employee,
        DtrRecordService $dtrRecords,
    ): View {
        $user = $request->user();

        abort_unless($user, 403);

        $periodId = PayrollPeriod::resolvePublicId($period);
        $branchId = Branch::resolvePublicId($branch);
        $employeeId = Employee::resolvePublicId($employee);

        abort_unless($periodId && $branchId && $employeeId, 404);

        $payrollPeriod = PayrollPeriod::query()->findOrFail($periodId);
        $selectedBranch = Branch::query()->findOrFail($branchId);
        $selectedEmployee = Employee::query()
            ->with(['branch', 'designation'])
            ->findOrFail($employeeId);

        if ($user->role === 'employee') {
            abort_unless(
                (int) $user->employee?->id === (int) $selectedEmployee->id
                && (int) $user->employee?->branch_id === (int) $selectedBranch->id
                && ! $selectedEmployee->hasEndedEmployment(),
                403,
            );
        } else {
            abort_unless(in_array($user->role, ['hr', 'admin'], true) && DtrManage::canAccess(), 403);
        }

        $rows = $dtrRecords
            ->query($selectedEmployee, $selectedBranch->id, $payrollPeriod->id)
            ->orderBy('date_in')
            ->orderBy('time_in')
            ->orderBy('id')
            ->get()
            ->map(fn (Dtr $record): array => $this->printRow($record));

        return view('dtr.print', [
            'employee' => $selectedEmployee,
            'branch' => $selectedBranch,
            'period' => $payrollPeriod,
            'rows' => $rows,
            'companyName' => CompanyExportHeader::COMPANY_NAME,
            'companyAddress' => CompanyExportHeader::ADDRESS_LINE.', '.CompanyExportHeader::PROVINCE_LINE,
            'logo' => CompanyExportHeader::logoDataUri(),
            'generatedAt' => CompanyExportHeader::generatedAt(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function printRow(Dtr $record): array
    {
        $isLeave = filled($record->leave_id)
            || str($record->schedule_type)->lower()->toString() === 'leave';
        $isForgotToPunch = str($record->schedule_type)->lower()->contains('forgot');
        $isAbsent = (bool) $record->is_absent;
        $isNonPunch = $isLeave || ($isAbsent && ! $isForgotToPunch);
        $attendanceUnits = app(DtrAttendanceUnitService::class);
        $dayPart = app(DtrDayPartService::class)->label($attendanceUnits->dayPartForRecord($record));

        $status = match (true) {
            $isLeave => 'Leave',
            $isForgotToPunch => 'Forgot to Punch',
            $isAbsent => 'Absent',
            str($record->schedule_type)->lower()->toString() === 'overtime' => 'Overtime',
            default => 'Present',
        };

        if (in_array($dayPart, ['Morning', 'Afternoon'], true)) {
            $status .= ' - '.$dayPart;
        } elseif ($dayPart === 'Review Required') {
            $status = $dayPart;
        }

        if ($this->isNextDayTimeout($record)) {
            $status .= ' / Next-day timeout';
        }

        return [
            'date_in' => $this->formatDate($record->date_in),
            'time_in' => $isNonPunch ? '-' : $this->formatTime($record->time_in),
            'date_out' => $this->formatDate($record->date_out),
            'time_out' => $isNonPunch ? '-' : $this->formatTime($record->time_out),
            'schedule' => $this->formatScheduleType($record->schedule_type),
            'day_count' => number_format($attendanceUnits->recordAttendanceUnits($record), 1),
            'status' => $status,
        ];
    }

    protected function formatScheduleType(?string $scheduleType): string
    {
        $normalized = str($scheduleType)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();

        return match ($normalized) {
            '' => '-',
            'regular', 'regularschedule', 'regsched' => 'Regular',
            'shift1' => 'Shift1',
            'shift2' => 'Shift2',
            'shift3' => 'Shift3',
            'brkn1', 'broken1', 'brokenshift1' => 'Broken1',
            'brkn2', 'broken2', 'brokenshift2' => 'Broken2',
            'saturday' => 'Saturday',
            'overtime' => 'Overtime',
            'forgottopunch' => 'Forgot to Punch',
            'leave' => 'Leave',
            'absent' => 'Absent',
            default => str($scheduleType)->trim()->headline()->toString(),
        };
    }

    protected function isNextDayTimeout(Dtr $record): bool
    {
        if (blank($record->date_in) || blank($record->date_out)) {
            return false;
        }

        return Carbon::parse($record->date_out)->startOfDay()
            ->greaterThan(Carbon::parse($record->date_in)->startOfDay());
    }

    protected function formatDate(mixed $value): string
    {
        return filled($value) ? Carbon::parse($value)->format('M d, Y') : '-';
    }

    protected function formatTime(mixed $value): string
    {
        return filled($value) ? Carbon::parse($value)->format('h:i A') : '-';
    }
}
