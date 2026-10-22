<?php

namespace App\Services;

use App\Models\ActionHistory;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaturdayWfhDtrService
{
    public const SOURCE = 'saturday_wfh';

    /** @param array<int, array{data: array<string, mixed>}> $validatedRows */
    public function materializeForValidatedRows(array $validatedRows): int
    {
        $scopes = collect($validatedRows)
            ->map(fn (array $row): array => [
                'period_id' => (int) ($row['data']['payroll_period_id'] ?? 0),
                'branch_id' => (int) ($row['data']['branch_id'] ?? 0),
            ])
            ->filter(fn (array $scope): bool => $scope['period_id'] > 0 && $scope['branch_id'] > 0)
            ->unique(fn (array $scope): string => $scope['period_id'].':'.$scope['branch_id']);

        return $scopes->sum(fn (array $scope): int => $this->materializeScope($scope['period_id'], $scope['branch_id']));
    }

    public function materializeScope(int $periodId, int $branchId): int
    {
        return DB::transaction(function () use ($periodId, $branchId): int {
            $period = PayrollPeriod::query()->lockForUpdate()->find($periodId);
            if (! $period || $period->is_locked) {
                return 0;
            }

            $created = 0;
            $employees = Employee::query()->activeEmployment()
                ->where('branch_id', $branchId)
                ->where('schedule_type', 'regular')
                ->whereNotNull('hired_date')
                ->whereNotNull('fingerprint_id')
                ->lockForUpdate()
                ->get();

            foreach ($employees as $employee) {
                $official = Dtr::query()->where('payroll_period_id', $periodId)
                    ->where('branch_id', $branchId)
                    ->where('fingerprint_id', (string) $employee->fingerprint_id)->get();
                $visible = Schema::hasTable('employee_visible_dtrs')
                    ? EmployeeVisibleDtr::query()->where('payroll_period_id', $periodId)
                        ->where('employee_id', $employee->id)->get()
                    : collect();
                $records = $official->concat($visible);

                foreach (app(SaturdayWfhPolicyService::class)->completedWfhDates($employee, $period, $records) as $date) {
                    $record = new Dtr;
                    $record->forceFill([
                        'employee_id' => $employee->id,
                        'branch_id' => $branchId,
                        'payroll_period_id' => $periodId,
                        'fingerprint_id' => (string) $employee->fingerprint_id,
                        'batch_id' => 'SATURDAY-WFH-'.$periodId,
                        'import_name' => 'Saturday WFH policy',
                        'source_session_id' => 'WFH-'.$employee->id.'-'.$date,
                        'date_in' => $date,
                        'time_in' => '08:00:00',
                        'date_out' => $date,
                        'time_out' => '11:00:00',
                        'schedule_type' => 'WFH',
                        'day_part' => DtrDayPartService::WHOLE_DAY,
                        'entry_source' => self::SOURCE,
                        'schedule_start' => '08:00:00',
                        'schedule_end' => '11:00:00',
                        'work_hrs' => 180,
                        'credited_work_hrs' => 180,
                        'daily_rate' => $employee->daily_rate,
                        'is_absent' => false,
                        'is_imported' => false,
                        'is_locked' => false,
                    ])->save();

                    app(DtrSynchronizationService::class)->syncOfficialToVisible($record);
                    ActionHistory::query()->create([
                        'actor_name' => auth()->user()?->name ?: 'DTR import',
                        'action' => 'saturday_wfh_dtr_created',
                        'model_type' => Dtr::class,
                        'model_id' => $record->id,
                        'summary' => "Saturday WFH attendance created for {$employee->full_name} on {$date}.",
                        'before_data' => [],
                        'after_data' => $record->getAttributes(),
                    ]);
                    $records->push($record);
                    $created++;
                }
            }

            return $created;
        });
    }

    /** @param array<string, mixed> $data */
    public function supersedeForActualImport(array $data): void
    {
        $period = PayrollPeriod::query()->lockForUpdate()->find($data['payroll_period_id']);
        if (! $period || $period->is_locked) {
            throw new \DomainException('A locked payroll period cannot replace Saturday WFH attendance.');
        }

        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            if (! Schema::hasTable((new $model)->getTable())) {
                continue;
            }

            $records = $model::query()->where('payroll_period_id', $period->id)
                ->where('branch_id', $data['branch_id'])
                ->where('fingerprint_id', (string) $data['fingerprint_id'])
                ->whereDate('date_in', $data['date_in'])
                ->where('entry_source', self::SOURCE)
                ->lockForUpdate()->get();

            foreach ($records as $record) {
                if ($record->is_locked) {
                    throw new \DomainException('A locked Saturday WFH entry cannot be replaced by imported attendance.');
                }

                $before = $record->getAttributes();
                $record->delete();
                ActionHistory::query()->create([
                    'actor_name' => auth()->user()?->name ?: 'DTR import',
                    'action' => 'saturday_wfh_dtr_superseded',
                    'model_type' => $model,
                    'model_id' => $record->id,
                    'summary' => 'Saturday WFH entry superseded by actual attendance.',
                    'before_data' => $before,
                    'after_data' => $record->getAttributes(),
                ]);
            }
        }
    }
}
