<?php

namespace App\Http\Controllers\SicRc;

use App\Filament\Employee\Pages\Station\StationEmployees;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Services\Biometrics\BiometricDtrBinCodec;
use App\Services\DtrOvertimeTransferService;
use App\Services\StationManagementAccess;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeVisibleDtrExportController extends Controller
{
    public function __invoke(
        Request $request,
        BiometricDtrBinCodec $codec,
        DtrOvertimeTransferService $overtimeTransfer,
    ): Response {
        $manager = auth()->user()?->employee;

        abort_unless($manager instanceof Employee, 403, 'Unauthorized manager.');

        $periodKey = $request->query('period_id') ?? $request->query('periodId');
        $branchKey = $request->query('branch_id') ?? $request->query('branchId');

        $periodId = PayrollPeriod::resolvePublicId($periodKey);
        $branchId = Branch::resolvePublicId($branchKey);

        abort_if(blank($periodId) || blank($branchId), 404, 'Branch or payroll period not found.');
        abort_unless(StationManagementAccess::canManageBranch($manager, (int) $branchId), 403, 'Branch not assigned.');

        $period = PayrollPeriod::query()->findOrFail($periodId);
        $branch = Branch::query()->findOrFail($branchId);

        $query = EmployeeVisibleDtr::query()
            ->with('employee:id,fingerprint_id,lastname,middlename,firstname')
            ->where('payroll_period_id', $period->id)
            ->where('branch_id', $branch->id)
            ->orderBy('fingerprint_id')
            ->orderBy('date_in')
            ->orderBy('time_in');

        // Fallback to official DTR table if employee_visible_dtrs has no records
        if (! (clone $query)->exists()) {
            $fallbackQuery = Dtr::query()
                ->with('employee:id,fingerprint_id,lastname,middlename,firstname')
                ->where('payroll_period_id', $period->id)
                ->where('branch_id', $branch->id)
                ->orderBy('fingerprint_id')
                ->orderBy('date_in')
                ->orderBy('time_in');

            if ((clone $fallbackQuery)->exists()) {
                $query = $fallbackQuery;
            }
        }

        if (! (clone $query)->exists()) {
            Notification::make()
                ->title('No D.T.R Records Available')
                ->warning()
                ->body("There are no D.T.R records available to download for {$branch->branch_name} in {$period->title}. Please import D.T.R first.")
                ->send();

            return redirect()->to(StationEmployees::getUrl([
                'branchId' => $branch->publicKey(),
                'periodId' => $period->publicKey(),
            ]));
        }

        $filename = 'station-dtr-'.str($branch->branch_name)->slug().'-'.now()->format('Ymd-His').'.bin';

        return response()->streamDownload(function () use ($query, $codec, $overtimeTransfer): void {
            $query->chunk(500, function ($records) use ($codec, $overtimeTransfer): void {
                $names = Employee::query()
                    ->whereIn('fingerprint_id', $records->pluck('fingerprint_id')->filter()->unique()->values())
                    ->get(['fingerprint_id', 'lastname', 'middlename', 'firstname'])
                    ->mapWithKeys(fn (Employee $employee): array => [
                        (string) $employee->fingerprint_id => $employee->full_name,
                    ]);

                foreach ($records as $record) {
                    echo $codec->encodeRecord([
                        'uid' => is_numeric($record->fingerprint_id)
                            ? (int) $record->fingerprint_id
                            : (string) $record->fingerprint_id,
                        'name' => $record->employee?->full_name ?: $names->get((string) $record->fingerprint_id, ''),
                        'date_in' => (string) ($record->date_in ?? ''),
                        'time_in' => (string) ($record->time_in ?? ''),
                        'date_out' => (string) ($record->date_out ?? ''),
                        'time_out' => (string) ($record->time_out ?? ''),
                        'sched' => (string) ($record->schedule_type ?? ''),
                        'sched_start' => (string) ($record->schedule_start ?? ''),
                        'sched_end' => (string) ($record->schedule_end ?? ''),
                        'session_id' => filled($record->source_session_id)
                            ? (string) $record->source_session_id
                            : 'hris-'.$record->getKey(),
                        ...$overtimeTransfer->exportPayload($record),
                    ]);
                }
            });
        }, $filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
