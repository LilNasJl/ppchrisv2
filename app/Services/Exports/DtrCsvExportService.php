<?php

namespace App\Services\Exports;

use App\Models\Dtr;
use App\Models\Employee;
use App\Services\DtrAttendanceUnitService;
use App\Services\DtrDayPartService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DtrCsvExportService
{
    /**
     * @return array<int, string>
     */
    protected function columns(): array
    {
        return [
            'Date In',
            'Time In',
            'Date Out',
            'Time Out',
            'Schedule Type',
            'Day Part',
            'Day Count',
            'Schedule Start',
            'Schedule End',
            'Late',
            'Undertime',
            'Overtime',
            'Early Clock In',
            'Credited Overtime',
            'Work Hours',
            'Credited Work Hours',
            'Overtime Status',
            'Absent',
            'Holiday Type',
            'Holiday Rate',
            'Holiday Excluded',
            'Daily Rate',
        ];
    }

    public function download(?int $employeeId, ?int $branchId, ?int $periodId): StreamedResponse
    {
        abort_if(blank($employeeId) || blank($branchId) || blank($periodId), 404);

        $employee = Employee::query()
            ->activeEmployment()
            ->find($employeeId);

        abort_unless($employee, 404);

        $fingerprintId = $employee->fingerprint_id ?: $employee->uid;

        abort_if(blank($fingerprintId), 404);

        $query = Dtr::query()
            ->where('payroll_period_id', $periodId)
            ->where('branch_id', $branchId)
            ->where('fingerprint_id', $fingerprintId)
            ->orderBy('date_in')
            ->orderBy('time_in');

        $filename = 'managedtr-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, $this->columns());

            $query->chunk(500, function ($records) use ($output): void {
                foreach ($records as $record) {
                    fputcsv($output, [
                        $record->date_in,
                        $record->time_in,
                        $record->date_out,
                        $record->time_out,
                        $record->schedule_type,
                        app(DtrDayPartService::class)->label(app(DtrAttendanceUnitService::class)->dayPartForRecord($record)),
                        number_format(app(DtrAttendanceUnitService::class)->recordAttendanceUnits($record), 1, '.', ''),
                        $record->schedule_start,
                        $record->schedule_end,
                        $record->late,
                        $record->undertime,
                        $record->overtime,
                        $record->early_clock_in,
                        $record->credited_overtime,
                        $record->work_hrs,
                        $record->credited_work_hrs,
                        $record->overtime_status,
                        $record->is_absent ? 'Yes' : 'No',
                        $record->holiday_type,
                        $record->holiday_rate,
                        $record->holiday_excluded ? 'Yes' : 'No',
                        $record->daily_rate,
                    ]);
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
