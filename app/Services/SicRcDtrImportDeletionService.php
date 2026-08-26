<?php

namespace App\Services;

use App\Models\EmployeeVisibleDtr;
use App\Models\SicRcDtrImport;
use Illuminate\Support\Facades\DB;

class SicRcDtrImportDeletionService
{
    /**
     * @return array{entries:int, histories:int}
     */
    public function delete(SicRcDtrImport $import): array
    {
        return DB::transaction(function () use ($import): array {
            $isCompletedBatch = $import->status === SicRcDtrImport::STATUS_COMPLETED
                && $import->imported_rows > 0
                && filled($import->batch_id)
                && filled($import->branch_id)
                && filled($import->payroll_period_id);

            if (! $isCompletedBatch) {
                $import->delete();

                return [
                    'entries' => 0,
                    'histories' => 1,
                ];
            }

            $scope = [
                'branch_id' => $import->branch_id,
                'payroll_period_id' => $import->payroll_period_id,
                'batch_id' => $import->batch_id,
            ];

            $deletedEntries = EmployeeVisibleDtr::withTrashed()
                ->where($scope)
                ->forceDelete();

            $deletedHistories = SicRcDtrImport::query()
                ->where($scope)
                ->delete();

            return [
                'entries' => $deletedEntries,
                'histories' => $deletedHistories,
            ];
        });
    }
}
