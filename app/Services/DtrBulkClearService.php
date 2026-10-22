<?php

namespace App\Services;

use App\Models\ActionHistory;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\DtrSubmission;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DtrBulkClearService
{
    /** @return array{official:int, visible:int} */
    public function counts(int $periodId, int $branchId): array
    {
        return [
            'official' => Dtr::query()->where('payroll_period_id', $periodId)->where('branch_id', $branchId)->count(),
            'visible' => EmployeeVisibleDtr::query()->where('payroll_period_id', $periodId)->where('branch_id', $branchId)->count(),
        ];
    }

    /** @return array{official:int, visible:int} */
    public function clear(int $periodId, int $branchId, User $actor): array
    {
        if (! in_array($actor->role, ['hr', 'admin'], true) || (bool) $actor->is_disabled) {
            throw new AuthorizationException('Only HR or admin accounts can clear D.T.R entries.');
        }

        return DB::transaction(function () use ($periodId, $branchId, $actor): array {
            $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($periodId);
            $branch = Branch::query()->findOrFail($branchId);

            if ($period->is_locked) {
                throw ValidationException::withMessages(['periodId' => 'This payroll period is locked. No D.T.R entries were cleared.']);
            }

            $official = Dtr::query()->where('payroll_period_id', $periodId)->where('branch_id', $branchId);
            $visible = EmployeeVisibleDtr::query()->where('payroll_period_id', $periodId)->where('branch_id', $branchId);

            if ((clone $official)->where('is_locked', true)->exists() || (clone $visible)->where('is_locked', true)->exists()) {
                throw ValidationException::withMessages(['periodId' => 'The selected D.T.R entries include locked records. No entries were cleared.']);
            }

            $submissionIds = (clone $official)->whereNotNull('on_field_dtr_submission_id')
                ->distinct()->pluck('on_field_dtr_submission_id')
                ->merge((clone $visible)->whereNotNull('on_field_dtr_submission_id')
                    ->distinct()->pluck('on_field_dtr_submission_id'))
                ->unique();

            $deleted = [
                'official' => $official->delete(),
                'visible' => $visible->forceDelete(),
            ];

            foreach ($submissionIds as $submissionId) {
                if (Dtr::query()->where('on_field_dtr_submission_id', $submissionId)->exists()
                    || EmployeeVisibleDtr::query()->where('on_field_dtr_submission_id', $submissionId)->exists()) {
                    continue;
                }

                DtrSubmission::query()->whereKey($submissionId)
                    ->whereNull('generated_dtr_deleted_at')
                    ->update([
                        'generated_dtr_deleted_at' => now(),
                        'generated_dtr_deleted_by_user_id' => $actor->getKey(),
                    ]);
            }

            ActionHistory::query()->create([
                'actor_id' => $actor->getKey(),
                'actor_name' => $actor->employee?->full_name ?: ($actor->name ?: $actor->username),
                'actor_role' => $actor->role,
                'action' => 'dtr_bulk_cleared',
                'model_type' => Dtr::class,
                'model_label' => 'Daily Time Record',
                'record_label' => "{$branch->branch_name} | {$period->title}",
                'summary' => "Cleared {$deleted['official']} HR D.T.R and {$deleted['visible']} employee-visible D.T.R entries.",
                'before_data' => [
                    'payroll_period_id' => $period->id,
                    'branch_id' => $branch->id,
                    'official_count' => $deleted['official'],
                    'visible_count' => $deleted['visible'],
                ],
                'after_data' => [],
            ]);

            return $deleted;
        }, 3);
    }
}
