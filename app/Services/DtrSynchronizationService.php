<?php

namespace App\Services;

use App\Models\ActionHistory;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DtrSynchronizationService
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $columnCache = [];

    /**
     * Synchronize an EmployeeVisibleDtr record (Station Management) to the official Dtr table (HR Panel).
     */
    public function syncVisibleToOfficial(
        EmployeeVisibleDtr $visible,
        string $source = 'station_sync',
        Employee|int|null $actorEmployeeId = null,
        bool|array $recalculateDaily = true,
    ): ?Dtr {
        $actorEmployeeId = $actorEmployeeId instanceof Employee ? $actorEmployeeId->id : $actorEmployeeId;
        $recalculateDaily = is_bool($recalculateDaily) ? $recalculateDaily : true;

        if (! Schema::hasTable('dtrs')) {
            return null;
        }

        $dtrColumns = $this->columnsFor('dtrs');
        if ($dtrColumns === []) {
            return null;
        }

        $employee = $visible->employee ?: (filled($visible->employee_id) ? Employee::query()->find($visible->employee_id) : null);
        $fingerprintId = $this->resolveFingerprintId($visible, $employee);

        $official = $this->findCounterpartOfficial($visible, $fingerprintId);
        $beforeData = $official ? $this->auditSnapshot($official) : [];

        $payload = $this->filterColumns('dtrs', [
            'batch_id' => $visible->batch_id,
            'payroll_period_id' => $visible->payroll_period_id,
            'branch_id' => $visible->branch_id ?: $employee?->branch_id,
            'employee_id' => $visible->employee_id ?: $employee?->id,
            'on_field_dtr_submission_id' => $visible->on_field_dtr_submission_id,
            'leave_id' => $visible->leave_id,
            'holiday_id' => $visible->holiday_id,
            'holiday_type' => $visible->holiday_type,
            'holiday_rate' => $visible->holiday_rate,
            'fingerprint_id' => $fingerprintId,
            'source_session_id' => $visible->source_session_id,
            'source_row_hash' => $visible->source_row_hash,
            'source_filename' => $visible->source_filename,
            'source_file_hash' => $visible->source_file_hash,
            'source_imported_at' => $visible->source_imported_at,
            'date_in' => $this->normalizeDate($visible->date_in),
            'time_in' => $visible->time_in,
            'date_out' => $this->normalizeDate($visible->date_out),
            'time_out' => $visible->time_out,
            'schedule_type' => $visible->schedule_type,
            'day_part' => $visible->day_part ?? DtrDayPartService::WHOLE_DAY,
            'entry_source' => $visible->entry_source ?? ($visible->is_imported ? DtrDayPartService::SOURCE_IMPORTED : DtrDayPartService::SOURCE_MANUAL),
            'schedule_start' => $visible->schedule_start,
            'schedule_end' => $visible->schedule_end,
            'late' => (int) ($visible->late ?? 0),
            'undertime' => (int) ($visible->undertime ?? 0),
            'overtime' => (int) ($visible->overtime ?? 0),
            'early_clock_in' => (int) ($visible->early_clock_in ?? 0),
            'credited_early_clock_in' => (int) ($visible->credited_early_clock_in ?? 0),
            'credited_overtime' => (int) ($visible->credited_overtime ?? 0),
            'work_hrs' => (int) ($visible->work_hrs ?? 0),
            'credited_work_hrs' => (int) ($visible->credited_work_hrs ?? 0),
            'overtime_status' => $visible->overtime_status ?? 'n/a',
            'early_clock_in_approved' => (bool) ($visible->early_clock_in_approved ?? false),
            'overtime_approved' => (bool) ($visible->overtime_approved ?? false),
            'daily_rate' => $visible->daily_rate ?? $employee?->daily_rate,
            'comment' => $visible->comment,
            'is_absent' => (bool) ($visible->is_absent ?? false),
            'absence_minutes' => (int) ($visible->absence_minutes ?? 0),
            'is_imported' => (bool) ($visible->is_imported ?? false),
            'is_locked' => (bool) ($visible->is_locked ?? false),
        ]);

        $saveCallback = function () use (&$official, $payload): void {
            if ($official) {
                if (method_exists($official, 'trashed') && $official->trashed()) {
                    $official->restore();
                }
                $official->forceFill($payload)->saveQuietly();
            } else {
                $official = new Dtr;
                $official->forceFill($payload)->saveQuietly();
            }
        };

        if ($recalculateDaily) {
            $saveCallback();
            if ($official && $this->hasAggregationColumns('dtrs')) {
                app(DtrDailyAggregationService::class)->recalculateRecord($official);
                $official->refresh();
                $this->syncAggregatedMetricsToVisible($official, $visible);
            }
        } else {
            app(DtrDailyAggregationService::class)->withoutAutomaticRecalculation($saveCallback);
        }

        if ($official) {
            app(AutomaticAbsenceService::class)->supersede($official);
            $visibleLinkPayload = $this->filterColumns('employee_visible_dtrs', [
                'dtr_source' => $visible->dtr_source ?: $source,
                'dtr_source_id' => $official->id,
            ]);

            if ($visibleLinkPayload !== []) {
                $visible->forceFill($visibleLinkPayload)->saveQuietly();
            }

            $this->recordAudit(
                action: $beforeData === [] ? 'dtr_synced_create' : 'dtr_synced_update',
                record: $official,
                beforeData: $beforeData,
                afterData: $this->auditSnapshot($official),
                source: $source,
                actorEmployeeId: $actorEmployeeId,
            );
        }

        return $official;
    }

    /**
     * Synchronize a batch of EmployeeVisibleDtr records to Dtr and recalculate daily aggregations.
     *
     * @param  iterable<int, EmployeeVisibleDtr>  $visibleRecords
     */
    public function syncVisibleBatchToOfficial(
        iterable $visibleRecords,
        string $source = 'station_import',
        ?int $actorEmployeeId = null,
    ): void {
        if (! Schema::hasTable('dtrs')) {
            return;
        }

        $syncedPairs = [];
        $recalculationKeys = [];

        app(DtrDailyAggregationService::class)->withoutAutomaticRecalculation(function () use (
            $visibleRecords,
            $source,
            $actorEmployeeId,
            &$syncedPairs,
            &$recalculationKeys,
        ): void {
            foreach ($visibleRecords as $visible) {
                $official = $this->syncVisibleToOfficial(
                    visible: $visible,
                    source: $source,
                    actorEmployeeId: $actorEmployeeId,
                    recalculateDaily: false,
                );

                if ($official) {
                    $syncedPairs[] = [$official, $visible];
                    $recalculationKeys[] = [
                        'payroll_period_id' => $official->payroll_period_id,
                        'branch_id' => $official->branch_id,
                        'fingerprint_id' => $official->fingerprint_id,
                        'date_in' => $this->normalizeDate($official->date_in),
                    ];
                }
            }
        });

        if ($recalculationKeys !== [] && $this->hasAggregationColumns('dtrs')) {
            app(DtrDailyAggregationService::class)->recalculateRows($recalculationKeys);

            foreach ($syncedPairs as [$official, $visible]) {
                $official->refresh();
                $this->syncAggregatedMetricsToVisible($official, $visible);
            }
        }
    }

    /**
     * Reconcile and synchronize any EmployeeVisibleDtr records that lack an official counterpart in Dtr.
     */
    public function reconcileUnsyncedRecords(
        ?int $branchId = null,
        ?int $periodId = null,
        string $source = 'reconciliation_sync',
        bool $includeLocked = false,
    ): int {
        if (! Schema::hasTable('employee_visible_dtrs') || ! Schema::hasTable('dtrs')) {
            return 0;
        }

        $query = EmployeeVisibleDtr::query()
            ->when(filled($branchId), fn ($q) => $q->where('branch_id', $branchId))
            ->when(filled($periodId), fn ($q) => $q->where('payroll_period_id', $periodId))
            ->when(! $includeLocked && Schema::hasTable('payroll_periods'), function ($q): void {
                $q->whereHas('payrollPeriod', fn ($pq) => $pq->where('is_locked', false));
            });

        $unsynced = [];
        foreach ($query->lazy(200) as $visible) {
            if (! $this->findCounterpartOfficial($visible)) {
                $unsynced[] = $visible;
            }
        }

        if ($unsynced === []) {
            return 0;
        }

        $this->syncVisibleBatchToOfficial($unsynced, $source);

        return count($unsynced);
    }

    /**
     * Count how many EmployeeVisibleDtr records currently lack an official counterpart in Dtr.
     */
    public function countUnsyncedRecords(
        ?int $branchId = null,
        ?int $periodId = null,
        bool $includeLocked = false,
    ): int {
        if (! Schema::hasTable('employee_visible_dtrs') || ! Schema::hasTable('dtrs')) {
            return 0;
        }

        $query = EmployeeVisibleDtr::query()
            ->when(filled($branchId), fn ($q) => $q->where('branch_id', $branchId))
            ->when(filled($periodId), fn ($q) => $q->where('payroll_period_id', $periodId))
            ->when(! $includeLocked && Schema::hasTable('payroll_periods'), function ($q): void {
                $q->whereHas('payrollPeriod', fn ($pq) => $pq->where('is_locked', false));
            });

        $count = 0;
        foreach ($query->lazy(200) as $visible) {
            if (! $this->findCounterpartOfficial($visible)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Synchronize an official Dtr record (HR Panel) to EmployeeVisibleDtr (Station & Employee View).
     */
    public function syncOfficialToVisible(
        Dtr $official,
        string $source = 'hr_sync',
        Employee|int|null $actorEmployeeId = null,
    ): ?EmployeeVisibleDtr {
        $actorEmployeeId = $actorEmployeeId instanceof Employee ? $actorEmployeeId->id : $actorEmployeeId;

        if ($official instanceof EmployeeVisibleDtr) {
            return $official;
        }

        if (! Schema::hasTable('employee_visible_dtrs')) {
            return null;
        }

        $visibleColumns = $this->columnsFor('employee_visible_dtrs');
        if ($visibleColumns === []) {
            return null;
        }

        $employee = $this->resolveEmployeeForOfficial($official);
        if (! $employee) {
            return null;
        }

        $visible = $this->findCounterpartVisible($official, $employee);
        $beforeData = $visible ? $this->auditSnapshot($visible) : [];

        $fingerprintId = $official->fingerprint_id ?: ($employee->fingerprint_id ?: $employee->uid);

        $payload = $this->filterColumns('employee_visible_dtrs', [
            'batch_id' => $official->batch_id,
            'payroll_period_id' => $official->payroll_period_id,
            'branch_id' => $official->branch_id ?: $employee->branch_id,
            'employee_id' => $employee->id,
            'on_field_dtr_submission_id' => $official->on_field_dtr_submission_id,
            'leave_id' => $official->leave_id,
            'dtr_source' => $source,
            'dtr_source_id' => $official->id,
            'holiday_id' => $official->holiday_id,
            'holiday_type' => $official->holiday_type,
            'holiday_rate' => $official->holiday_rate,
            'fingerprint_id' => $fingerprintId !== null ? (string) $fingerprintId : null,
            'source_session_id' => $official->source_session_id,
            'source_row_hash' => $official->source_row_hash,
            'source_filename' => $official->source_filename,
            'source_file_hash' => $official->source_file_hash,
            'source_imported_at' => $official->source_imported_at,
            'date_in' => $this->normalizeDate($official->date_in),
            'time_in' => $official->time_in,
            'date_out' => $this->normalizeDate($official->date_out),
            'time_out' => $official->time_out,
            'schedule_type' => $official->schedule_type,
            'day_part' => $official->day_part ?? DtrDayPartService::WHOLE_DAY,
            'entry_source' => $official->entry_source ?? ($official->is_imported ? DtrDayPartService::SOURCE_IMPORTED : DtrDayPartService::SOURCE_MANUAL),
            'schedule_start' => $official->schedule_start,
            'schedule_end' => $official->schedule_end,
            'late' => (int) ($official->late ?? 0),
            'undertime' => (int) ($official->undertime ?? 0),
            'overtime' => (int) ($official->overtime ?? 0),
            'early_clock_in' => (int) ($official->early_clock_in ?? 0),
            'credited_early_clock_in' => (int) ($official->credited_early_clock_in ?? 0),
            'credited_overtime' => (int) ($official->credited_overtime ?? 0),
            'work_hrs' => (int) ($official->work_hrs ?? 0),
            'credited_work_hrs' => (int) ($official->credited_work_hrs ?? 0),
            'overtime_status' => $official->overtime_status ?? 'n/a',
            'early_clock_in_approved' => (bool) ($official->early_clock_in_approved ?? false),
            'overtime_approved' => (bool) ($official->overtime_approved ?? false),
            'daily_rate' => $official->daily_rate ?? $employee->daily_rate,
            'comment' => $official->comment,
            'is_absent' => (bool) ($official->is_absent ?? false),
            'absence_minutes' => (int) ($official->absence_minutes ?? 0),
            'is_imported' => (bool) ($official->is_imported ?? false),
            'is_locked' => (bool) ($official->is_locked ?? false),
            'needs_review' => ($official->day_part ?? null) === DtrDayPartService::UNCLASSIFIED,
        ]);

        if ($visible) {
            if (method_exists($visible, 'trashed') && $visible->trashed()) {
                $visible->restore();
            }
            $visible->forceFill($payload)->saveQuietly();
        } else {
            $visible = new EmployeeVisibleDtr;
            $visible->forceFill($payload)->saveQuietly();
        }

        app(AutomaticAbsenceService::class)->supersede($official);
        $this->recordAudit(
            action: $beforeData === [] ? 'visible_dtr_synced_create' : 'visible_dtr_synced_update',
            record: $visible,
            beforeData: $beforeData,
            afterData: $this->auditSnapshot($visible),
            source: $source,
            actorEmployeeId: $actorEmployeeId,
        );

        return $visible;
    }

    /**
     * Synchronize a batch of official Dtr records to EmployeeVisibleDtr.
     *
     * @param  iterable<int, Dtr>  $officialRecords
     */
    public function syncOfficialBatchToVisible(
        iterable $officialRecords,
        string $source = 'hr_import',
        ?int $actorEmployeeId = null,
    ): void {
        if (! Schema::hasTable('employee_visible_dtrs')) {
            return;
        }

        foreach ($officialRecords as $official) {
            if ($official instanceof Dtr && ! ($official instanceof EmployeeVisibleDtr)) {
                $official->refresh();
                $this->syncOfficialToVisible($official, $source, $actorEmployeeId);
            }
        }
    }

    /**
     * Synchronize overtime approval/rejection/undo state between EmployeeVisibleDtr and Dtr.
     */
    public function syncOvertimeState(
        Dtr $record,
        Employee|int|null $actorEmployeeId = null,
        string $source = 'overtime_approval',
    ): ?Dtr {
        $actorEmployeeId = $actorEmployeeId instanceof Employee ? $actorEmployeeId->id : $actorEmployeeId;
        $actorEmployeeId ??= auth()->user()?->employee?->id;

        $otFields = [
            'early_clock_in_approved' => (bool) ($record->early_clock_in_approved ?? false),
            'overtime_approved' => (bool) ($record->overtime_approved ?? false),
            'overtime_status' => $record->overtime_status ?? 'n/a',
            'credited_early_clock_in' => (int) ($record->credited_early_clock_in ?? 0),
            'credited_overtime' => (int) ($record->credited_overtime ?? 0),
            'credited_work_hrs' => (int) ($record->credited_work_hrs ?? 0),
        ];

        if ($record instanceof EmployeeVisibleDtr) {
            $visibleAuditFields = $this->filterColumns('employee_visible_dtrs', [
                'is_manually_edited' => true,
                'manual_edited_at' => now(),
                'manual_edited_by_employee_id' => $actorEmployeeId,
            ]);

            if ($visibleAuditFields !== []) {
                $record->forceFill($visibleAuditFields)->saveQuietly();
            }

            if (! Schema::hasTable('dtrs')) {
                return null;
            }

            $employee = $record->employee ?: (filled($record->employee_id) ? Employee::query()->find($record->employee_id) : null);
            $fingerprintId = $this->resolveFingerprintId($record, $employee);
            $official = $this->findCounterpartOfficial($record, $fingerprintId);

            if (! $official) {
                $official = $this->syncVisibleToOfficial(
                    visible: $record,
                    source: $source,
                    actorEmployeeId: $actorEmployeeId,
                    recalculateDaily: false,
                );
            }

            if ($official) {
                $beforeData = $this->auditSnapshot($official);
                $filteredOt = $this->filterColumns('dtrs', $otFields);

                app(DtrDailyAggregationService::class)->withoutAutomaticRecalculation(function () use ($official, $filteredOt): void {
                    $official->forceFill($filteredOt)->saveQuietly();
                });

                if (blank($record->dtr_source_id) && in_array('dtr_source_id', $this->columnsFor('employee_visible_dtrs'), true)) {
                    $record->forceFill([
                        'dtr_source_id' => $official->id,
                        'dtr_source' => $record->dtr_source ?: $source,
                    ])->saveQuietly();
                }

                $this->recordAudit(
                    action: 'overtime_synced_to_official',
                    record: $official,
                    beforeData: $beforeData,
                    afterData: $this->auditSnapshot($official),
                    source: $source,
                    actorEmployeeId: $actorEmployeeId,
                );
            }

            return $official;
        }

        // $record is an official Dtr record
        if (! Schema::hasTable('employee_visible_dtrs')) {
            return null;
        }

        $employee = $this->resolveEmployeeForOfficial($record);
        if (! $employee) {
            return null;
        }

        $visible = $this->findCounterpartVisible($record, $employee);
        if (! $visible) {
            $visible = $this->syncOfficialToVisible($record, $source, $actorEmployeeId);
        }

        if ($visible) {
            $beforeData = $this->auditSnapshot($visible);
            $filteredOt = $this->filterColumns('employee_visible_dtrs', [
                ...$otFields,
                'dtr_source_id' => $record->id,
                'is_manually_edited' => true,
                'manual_edited_at' => now(),
                'manual_edited_by_employee_id' => $actorEmployeeId,
            ]);

            $visible->forceFill($filteredOt)->saveQuietly();

            $this->recordAudit(
                action: 'overtime_synced_to_visible',
                record: $visible,
                beforeData: $beforeData,
                afterData: $this->auditSnapshot($visible),
                source: $source,
                actorEmployeeId: $actorEmployeeId,
            );
        }

        return $visible;
    }

    /**
     * Permanently delete the counterpart record in the other DTR table.
     */
    public function deleteCounterpart(Dtr $record, Employee|int|null $actorEmployeeId = null): void
    {
        $actorEmployeeId = $actorEmployeeId instanceof Employee ? $actorEmployeeId->id : $actorEmployeeId;
        $actorEmployeeId ??= auth()->user()?->employee?->id;

        if ($record instanceof EmployeeVisibleDtr) {
            if (! Schema::hasTable('dtrs')) {
                return;
            }

            $employee = $record->employee ?: (filled($record->employee_id) ? Employee::query()->find($record->employee_id) : null);
            $fingerprintId = $this->resolveFingerprintId($record, $employee);
            $official = $this->findCounterpartOfficial($record, $fingerprintId);

            if ($official) {
                $beforeData = $this->auditSnapshot($official);
                $official->forceDelete();

                $this->recordAudit(
                    action: 'dtr_synced_delete',
                    record: $official,
                    beforeData: $beforeData,
                    afterData: [],
                    source: 'station_delete',
                    actorEmployeeId: $actorEmployeeId,
                );
            }

            return;
        }

        if (! Schema::hasTable('employee_visible_dtrs')) {
            return;
        }

        $employee = $this->resolveEmployeeForOfficial($record);
        $visible = $this->findCounterpartVisible($record, $employee);

        if ($visible) {
            $beforeData = $this->auditSnapshot($visible);
            $visible->forceDelete();

            $this->recordAudit(
                action: 'visible_dtr_synced_delete',
                record: $visible,
                beforeData: $beforeData,
                afterData: [],
                source: 'hr_delete',
                actorEmployeeId: $actorEmployeeId,
            );
        }
    }

    /**
     * Find the counterpart official Dtr record for an EmployeeVisibleDtr.
     */
    public function findCounterpartOfficial(EmployeeVisibleDtr $visible, ?string $fingerprintId = null): ?Dtr
    {
        if (! Schema::hasTable('dtrs')) {
            return null;
        }

        $dtrColumns = $this->columnsFor('dtrs');

        if (filled($visible->dtr_source_id)) {
            $bySourceId = Dtr::withTrashed()->find($visible->dtr_source_id);
            if ($bySourceId) {
                return $bySourceId;
            }
        }

        if (
            filled($visible->on_field_dtr_submission_id)
            && in_array('on_field_dtr_submission_id', $dtrColumns, true)
        ) {
            $onFieldQuery = Dtr::withTrashed()
                ->where('on_field_dtr_submission_id', $visible->on_field_dtr_submission_id);

            $byOnField = (clone $onFieldQuery)
                ->when(
                    filled($visible->date_in) && in_array('date_in', $dtrColumns, true),
                    fn ($q) => $q->whereDate('date_in', $this->normalizeDate($visible->date_in))
                )
                ->when(
                    filled($visible->time_in) && in_array('time_in', $dtrColumns, true),
                    fn ($q) => $q->where('time_in', $visible->time_in)
                )
                ->first() ?? $onFieldQuery->first();

            if ($byOnField) {
                return $byOnField;
            }
        }

        if (
            filled($visible->leave_id)
            && filled($visible->date_in)
            && in_array('leave_id', $dtrColumns, true)
        ) {
            $byLeave = Dtr::withTrashed()
                ->where('leave_id', $visible->leave_id)
                ->where('date_in', $this->normalizeDate($visible->date_in))
                ->where('day_part', $visible->day_part ?: DtrDayPartService::WHOLE_DAY)
                ->first();

            return $byLeave; // A new leave must never reuse an attendance row on the same date.
        }

        $employee = $visible->employee ?: (filled($visible->employee_id) ? Employee::query()->find($visible->employee_id) : null);
        $fingerprintId ??= $this->resolveFingerprintId($visible, $employee);

        if (blank($visible->payroll_period_id) || blank($fingerprintId)) {
            return null;
        }

        $baseQuery = Dtr::withTrashed()
            ->where('payroll_period_id', $visible->payroll_period_id);
        if ($visible->entry_source === AutomaticAbsenceService::SOURCE) {
            $baseQuery->where('entry_source', AutomaticAbsenceService::SOURCE)->where('day_part', $visible->day_part);
        } else {
            $baseQuery->where(fn ($q) => $q->whereNull('entry_source')->orWhere('entry_source', '!=', AutomaticAbsenceService::SOURCE));
        }

        if (in_array('branch_id', $dtrColumns, true) && filled($visible->branch_id)) {
            $baseQuery->where('branch_id', $visible->branch_id);
        }

        if (in_array('fingerprint_id', $dtrColumns, true)) {
            $baseQuery->where('fingerprint_id', $fingerprintId);
        }

        if (
            filled($visible->source_session_id)
            && in_array('source_session_id', $dtrColumns, true)
        ) {
            $bySession = (clone $baseQuery)
                ->where('source_session_id', $visible->source_session_id)
                ->first();
            if ($bySession) {
                return $bySession;
            }
        }

        if (
            filled($visible->source_row_hash)
            && in_array('source_row_hash', $dtrColumns, true)
        ) {
            $byHash = (clone $baseQuery)
                ->where('source_row_hash', $visible->source_row_hash)
                ->first();
            if ($byHash) {
                return $byHash;
            }
        }

        if (blank($visible->date_in) || ! in_array('date_in', $dtrColumns, true)) {
            return null;
        }

        $dateQuery = (clone $baseQuery)->whereDate('date_in', $this->normalizeDate($visible->date_in));

        if (in_array('time_in', $dtrColumns, true)) {
            $exactTime = (clone $dateQuery)
                ->when(
                    filled($visible->time_in),
                    fn ($q) => $q->where('time_in', $visible->time_in),
                    fn ($q) => $q->whereNull('time_in'),
                )
                ->first();

            if ($exactTime) {
                return $exactTime;
            }
        }

        // A new imported session is not an edit of an arbitrary same-day row.
        if ($visible->is_imported && filled($visible->source_row_hash)) {
            return null;
        }
        $candidates = $dateQuery->get();
        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $normSchedule = DtrChangeRequestService::normalizeScheduleType($visible->schedule_type);
        $bySchedule = $candidates->first(
            fn (Dtr $item): bool => DtrChangeRequestService::normalizeScheduleType($item->schedule_type) === $normSchedule
        );

        return $bySchedule ?: $candidates->first();
    }

    /**
     * Find the counterpart EmployeeVisibleDtr record for an official Dtr.
     */
    public function findCounterpartVisible(Dtr $official, Employee|int|null $employee = null): ?EmployeeVisibleDtr
    {
        if (is_int($employee)) {
            $employee = Employee::query()->find($employee);
        }

        if ($official instanceof EmployeeVisibleDtr) {
            return $official;
        }

        if (! Schema::hasTable('employee_visible_dtrs')) {
            return null;
        }

        $visibleColumns = $this->columnsFor('employee_visible_dtrs');

        if (filled($official->id) && in_array('dtr_source_id', $visibleColumns, true)) {
            $bySourceId = EmployeeVisibleDtr::withTrashed()
                ->where('dtr_source_id', $official->id)
                ->first();
            if ($bySourceId) {
                return $bySourceId;
            }
        }

        if (
            filled($official->on_field_dtr_submission_id)
            && in_array('on_field_dtr_submission_id', $visibleColumns, true)
        ) {
            $onFieldQuery = EmployeeVisibleDtr::withTrashed()
                ->where('on_field_dtr_submission_id', $official->on_field_dtr_submission_id);

            $byOnField = (clone $onFieldQuery)
                ->when(
                    filled($official->date_in) && in_array('date_in', $visibleColumns, true),
                    fn ($q) => $q->whereDate('date_in', $this->normalizeDate($official->date_in))
                )
                ->when(
                    filled($official->time_in) && in_array('time_in', $visibleColumns, true),
                    fn ($q) => $q->where('time_in', $official->time_in)
                )
                ->first() ?? $onFieldQuery->first();

            if ($byOnField) {
                return $byOnField;
            }
        }

        if (
            filled($official->leave_id)
            && filled($official->date_in)
            && in_array('leave_id', $visibleColumns, true)
        ) {
            $byLeave = EmployeeVisibleDtr::withTrashed()
                ->where('leave_id', $official->leave_id)
                ->where('date_in', $this->normalizeDate($official->date_in))
                ->where('day_part', $official->day_part ?: DtrDayPartService::WHOLE_DAY)
                ->first();

            return $byLeave;
        }

        $employee ??= $this->resolveEmployeeForOfficial($official);
        if (! $employee || blank($official->payroll_period_id)) {
            return null;
        }

        $baseQuery = EmployeeVisibleDtr::withTrashed()
            ->where('payroll_period_id', $official->payroll_period_id)
            ->where('employee_id', $employee->id);

        if (
            filled($official->source_session_id)
            && in_array('source_session_id', $visibleColumns, true)
        ) {
            $bySession = (clone $baseQuery)
                ->where('source_session_id', $official->source_session_id)
                ->first();
            if ($bySession) {
                return $bySession;
            }
        }

        if (
            filled($official->source_row_hash)
            && in_array('source_row_hash', $visibleColumns, true)
        ) {
            $byHash = (clone $baseQuery)
                ->where('source_row_hash', $official->source_row_hash)
                ->first();
            if ($byHash) {
                return $byHash;
            }
        }

        if (blank($official->date_in) || ! in_array('date_in', $visibleColumns, true)) {
            return null;
        }

        if ($official->entry_source === AutomaticAbsenceService::SOURCE) {
            $baseQuery->where('entry_source', AutomaticAbsenceService::SOURCE)->where('day_part', $official->day_part);
        } else {
            $baseQuery->where(fn ($q) => $q->whereNull('entry_source')->orWhere('entry_source', '!=', AutomaticAbsenceService::SOURCE));
        }
        $dateQuery = (clone $baseQuery)->whereDate('date_in', $this->normalizeDate($official->date_in));

        if (in_array('time_in', $visibleColumns, true)) {
            $exactTime = (clone $dateQuery)
                ->when(
                    filled($official->time_in),
                    fn ($q) => $q->where('time_in', $official->time_in),
                    fn ($q) => $q->whereNull('time_in'),
                )
                ->first();

            if ($exactTime) {
                return $exactTime;
            }
        }

        if ($official->is_imported && filled($official->source_row_hash)) {
            return null;
        }
        $candidates = $dateQuery->get();
        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $normSchedule = DtrChangeRequestService::normalizeScheduleType($official->schedule_type);
        $bySchedule = $candidates->first(
            fn (EmployeeVisibleDtr $item): bool => DtrChangeRequestService::normalizeScheduleType($item->schedule_type) === $normSchedule
        );

        return $bySchedule ?: $candidates->first();
    }

    /**
     * Copy daily-aggregated calculation fields from Dtr back to EmployeeVisibleDtr.
     */
    protected function syncAggregatedMetricsToVisible(Dtr $official, EmployeeVisibleDtr $visible): void
    {
        if (! Schema::hasTable('employee_visible_dtrs')) {
            return;
        }

        $payload = $this->filterColumns('employee_visible_dtrs', [
            'dtr_source_id' => $official->id,
            'schedule_start' => $official->schedule_start,
            'schedule_end' => $official->schedule_end,
            'day_part' => $official->day_part,
            'late' => (int) ($official->late ?? 0),
            'undertime' => (int) ($official->undertime ?? 0),
            'early_clock_in' => (int) ($official->early_clock_in ?? 0),
            'overtime' => (int) ($official->overtime ?? 0),
            'credited_early_clock_in' => (int) ($official->credited_early_clock_in ?? 0),
            'credited_overtime' => (int) ($official->credited_overtime ?? 0),
            'work_hrs' => (int) ($official->work_hrs ?? 0),
            'credited_work_hrs' => (int) ($official->credited_work_hrs ?? 0),
            'overtime_status' => $official->overtime_status ?? 'n/a',
            'early_clock_in_approved' => (bool) ($official->early_clock_in_approved ?? false),
            'overtime_approved' => (bool) ($official->overtime_approved ?? false),
            'is_absent' => (bool) ($official->is_absent ?? false),
            'absence_minutes' => (int) ($official->absence_minutes ?? 0),
        ]);

        if ($payload !== []) {
            $visible->forceFill($payload)->saveQuietly();
        }
    }

    /**
     * Resolve the active Employee corresponding to an official Dtr row.
     */
    public function resolveEmployeeForOfficial(Dtr $official): ?Employee
    {
        if (! Schema::hasTable('employees')) {
            return null;
        }

        $empColumns = $this->columnsFor('employees');

        if (isset($official->employee_id) && filled($official->employee_id)) {
            $byId = Employee::query()->find($official->employee_id);
            if ($byId) {
                return $byId;
            }
        }

        $fingerprintId = trim((string) ($official->fingerprint_id ?? ''));
        if ($fingerprintId === '') {
            return null;
        }

        $normalizedDigits = ltrim($fingerprintId, '0');
        $normalizedDigits = $normalizedDigits === '' ? '0' : $normalizedDigits;

        $candidates = Employee::query()
            ->when(
                filled($official->branch_id) && in_array('branch_id', $empColumns, true),
                fn ($q) => $q->where('branch_id', $official->branch_id)
            )
            ->get();

        foreach ($candidates as $employee) {
            foreach ([$employee->fingerprint_id ?? null, $employee->uid ?? null] as $candidateValue) {
                $candidateStr = trim((string) ($candidateValue ?? ''));
                if ($candidateStr === '') {
                    continue;
                }
                if ($candidateStr === $fingerprintId) {
                    return $employee;
                }
                $candidateDigits = ltrim($candidateStr, '0');
                $candidateDigits = $candidateDigits === '' ? '0' : $candidateDigits;
                if ($candidateDigits === $normalizedDigits) {
                    return $employee;
                }
            }
        }

        return null;
    }

    protected function resolveFingerprintId(EmployeeVisibleDtr $visible, ?Employee $employee = null): ?string
    {
        $fingerprintId = $visible->fingerprint_id
            ?: ($employee?->fingerprint_id ?: ($employee?->uid ?: null));

        return filled($fingerprintId) ? (string) $fingerprintId : null;
    }

    public function recordAudit(
        string $action,
        Model $record,
        array $beforeData = [],
        array|Employee|int|null $afterData = [],
        string $source = 'dtr_sync',
        Employee|int|null $actorEmployeeId = null,
    ): void {
        if (! Schema::hasTable('action_histories')) {
            return;
        }

        if (! is_array($afterData)) {
            $actorEmployeeId ??= $afterData;
            $afterData = $this->auditSnapshot($record);
        }

        $actorEmployeeId = $actorEmployeeId instanceof Employee ? $actorEmployeeId->id : $actorEmployeeId;

        try {
            $user = auth()->user();
            $actorEmp = $actorEmployeeId ? Employee::query()->find($actorEmployeeId) : $user?->employee;
            $actorName = $actorEmp?->full_name ?: ($user?->name ?: ($user?->username ?: 'System Sync'));
            $actorRole = $user?->role ?: 'system';

            $changed = [];
            foreach ($afterData as $k => $v) {
                if (! array_key_exists($k, $beforeData) || $beforeData[$k] !== $v) {
                    $changed[$k] = [
                        'old' => $beforeData[$k] ?? null,
                        'new' => $v,
                    ];
                }
            }

            if ($beforeData !== [] && $afterData !== [] && $changed === []) {
                return;
            }

            ActionHistory::query()->create([
                'actor_id' => $user?->id,
                'actor_name' => $actorName,
                'actor_role' => $actorRole,
                'action' => $action,
                'model_type' => get_class($record),
                'model_id' => $record->getKey(),
                'model_label' => $record instanceof EmployeeVisibleDtr ? 'Station DTR' : 'Official DTR',
                'record_label' => sprintf(
                    'FP:%s Date:%s (%s)',
                    (string) ($record->fingerprint_id ?? $record->employee_id ?? 'N/A'),
                    (string) ($this->normalizeDate($record->date_in) ?? 'N/A'),
                    $source,
                ),
                'summary' => sprintf('DTR synchronized via %s by %s', $source, $actorName),
                'before_data' => $beforeData ?: null,
                'after_data' => $afterData ?: null,
                'changed_data' => [
                    'source' => $source,
                    'actor_employee_id' => $actorEmp?->id ?? $actorEmployeeId,
                    'changes' => $changed,
                ],
            ]);
        } catch (\Throwable) {
            // Do not break DTR operations if audit logging table schema varies in partial test setups.
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditSnapshot(Model $record): array
    {
        return [
            'id' => $record->getKey(),
            'payroll_period_id' => $record->payroll_period_id ?? null,
            'branch_id' => $record->branch_id ?? null,
            'employee_id' => $record->employee_id ?? null,
            'fingerprint_id' => $record->fingerprint_id ?? null,
            'date_in' => $this->normalizeDate($record->date_in ?? null),
            'time_in' => $record->time_in ?? null,
            'date_out' => $this->normalizeDate($record->date_out ?? null),
            'time_out' => $record->time_out ?? null,
            'schedule_type' => $record->schedule_type ?? null,
            'schedule_start' => $record->schedule_start ?? null,
            'schedule_end' => $record->schedule_end ?? null,
            'day_part' => $record->day_part ?? null,
            'late' => (int) ($record->late ?? 0),
            'undertime' => (int) ($record->undertime ?? 0),
            'early_clock_in' => (int) ($record->early_clock_in ?? 0),
            'overtime' => (int) ($record->overtime ?? 0),
            'credited_early_clock_in' => (int) ($record->credited_early_clock_in ?? 0),
            'credited_overtime' => (int) ($record->credited_overtime ?? 0),
            'work_hrs' => (int) ($record->work_hrs ?? 0),
            'credited_work_hrs' => (int) ($record->credited_work_hrs ?? 0),
            'overtime_status' => $record->overtime_status ?? null,
            'early_clock_in_approved' => (bool) ($record->early_clock_in_approved ?? false),
            'overtime_approved' => (bool) ($record->overtime_approved ?? false),
            'is_absent' => (bool) ($record->is_absent ?? false),
            'comment' => $record->comment ?? null,
        ];
    }

    protected function normalizeDate(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        return Carbon::parse($date)->toDateString();
    }

    protected function hasAggregationColumns(string $table): bool
    {
        if (! Schema::hasTable('payroll_calculation_settings')) {
            return false;
        }

        $columns = $this->columnsFor($table);

        return in_array('date_in', $columns, true)
            && in_array('time_in', $columns, true)
            && in_array('schedule_type', $columns, true)
            && in_array('credited_work_hrs', $columns, true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function filterColumns(string $table, array $attributes): array
    {
        $columns = array_flip($this->columnsFor($table));

        return array_intersect_key($attributes, $columns);
    }

    /**
     * @return array<int, string>
     */
    protected function columnsFor(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return $this->columnCache[$table] ??= Schema::getColumnListing($table);
    }
}
