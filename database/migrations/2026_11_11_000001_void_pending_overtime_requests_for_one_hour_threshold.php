<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $minimumMinutes = 60;
        $voidTimestamp = now();
        $voidReason = 'Voided: Minimum overtime threshold updated to 1 hour (60 minutes).';
        $voidRemarks = 'Voided automatically due to updated overtime threshold policy requiring at least 60 minutes (1 hour).';

        if (
            Schema::hasTable('dtr_change_requests')
            && Schema::hasColumns('dtr_change_requests', [
                'id',
                'request_type',
                'status',
                'current_level',
                'assigned_employee_id',
                'current_step_label',
                'approvals_history',
                'rejection_reason',
                'reviewer_remarks',
                'reviewed_at',
                'employee_seen_at',
                'updated_at',
            ])
        ) {
            DB::table('dtr_change_requests')
                ->where('request_type', 'overtime_approval')
                ->where('status', 'Pending')
                ->orderBy('id')
                ->chunkById(100, function ($requests) use ($voidTimestamp, $voidReason, $voidRemarks): void {
                    foreach ($requests as $item) {
                        DB::transaction(function () use ($item, $voidTimestamp, $voidReason, $voidRemarks): void {
                            $request = DB::table('dtr_change_requests')
                                ->where('id', $item->id)
                                ->lockForUpdate()
                                ->first();

                            if (! $request || $request->status !== 'Pending' || $request->request_type !== 'overtime_approval') {
                                return;
                            }

                            $history = json_decode((string) ($request->approvals_history ?? '[]'), true);
                            if (! is_array($history)) {
                                $history = [];
                            }

                            $history[] = [
                                'level' => (int) ($request->current_level ?? 1),
                                'label' => 'System Policy Migration',
                                'action' => 'Voided',
                                'actor_id' => null,
                                'actor_name' => 'System (1-Hour Overtime Policy)',
                                'acted_at' => $voidTimestamp->toDateTimeString(),
                                'remarks' => $voidRemarks,
                            ];

                            DB::table('dtr_change_requests')
                                ->where('id', $request->id)
                                ->update([
                                    'status' => 'Voided',
                                    'assigned_employee_id' => null,
                                    'current_step_label' => 'Voided (1-Hour Overtime Policy)',
                                    'rejection_reason' => $voidReason,
                                    'reviewer_remarks' => $voidRemarks,
                                    'reviewed_at' => $voidTimestamp,
                                    'employee_seen_at' => null,
                                    'approvals_history' => json_encode($history, JSON_THROW_ON_ERROR),
                                    'updated_at' => $voidTimestamp,
                                ]);
                        });
                    }
                });
        }

        if (! Schema::hasTable('payroll_periods') || ! Schema::hasColumn('payroll_periods', 'is_locked')) {
            return;
        }

        $openPeriodIds = DB::table('payroll_periods')
            ->when(Schema::hasColumn('payroll_periods', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->where(function ($q): void {
                $q->where('is_locked', false)->orWhereNull('is_locked');
            })
            ->pluck('id')
            ->all();

        if ($openPeriodIds === []) {
            return;
        }

        foreach (['dtrs', 'employee_visible_dtrs'] as $table) {
            if (
                ! Schema::hasTable($table)
                || ! Schema::hasColumns($table, ['payroll_period_id', 'overtime_status', 'overtime', 'early_clock_in'])
            ) {
                continue;
            }

            $hasDeletedAt = Schema::hasColumn($table, 'deleted_at');
            $hasIsLocked = Schema::hasColumn($table, 'is_locked');
            $hasOtApproved = Schema::hasColumn($table, 'overtime_approved');
            $hasEarlyApproved = Schema::hasColumn($table, 'early_clock_in_approved');
            $hasUpdatedAt = Schema::hasColumn($table, 'updated_at');

            foreach (array_chunk($openPeriodIds, 200) as $periodChunk) {
                DB::transaction(function () use (
                    $table,
                    $periodChunk,
                    $minimumMinutes,
                    $voidTimestamp,
                    $hasDeletedAt,
                    $hasIsLocked,
                    $hasOtApproved,
                    $hasEarlyApproved,
                    $hasUpdatedAt
                ): void {
                    $updatePayload = ['overtime_status' => 'n/a'];
                    if ($hasUpdatedAt) {
                        $updatePayload['updated_at'] = $voidTimestamp;
                    }

                    DB::table($table)
                        ->whereIn('payroll_period_id', $periodChunk)
                        ->when($hasDeletedAt, fn ($q) => $q->whereNull('deleted_at'))
                        ->when($hasIsLocked, fn ($q) => $q->where(function ($sub): void {
                            $sub->where('is_locked', false)->orWhereNull('is_locked');
                        }))
                        ->where('overtime_status', 'Pending')
                        ->where(function ($q) use ($minimumMinutes): void {
                            $q->where('overtime', '<', $minimumMinutes)->orWhereNull('overtime');
                        })
                        ->where(function ($q) use ($minimumMinutes): void {
                            $q->where('early_clock_in', '<', $minimumMinutes)->orWhereNull('early_clock_in');
                        })
                        ->when($hasOtApproved, fn ($q) => $q->where(function ($sub): void {
                            $sub->where('overtime_approved', false)->orWhereNull('overtime_approved');
                        }))
                        ->when($hasEarlyApproved, fn ($q) => $q->where(function ($sub): void {
                            $sub->where('early_clock_in_approved', false)->orWhereNull('early_clock_in_approved');
                        }))
                        ->update($updatePayload);
                });
            }
        }
    }

    public function down(): void
    {
        // Policy voids and subsequent DTR re-submissions cannot be safely reverted without risking stale approvals.
    }
};
