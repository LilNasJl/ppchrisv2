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

            if ($isCompletedBatch) {
                // Imported DTR batches and records must remain protected once saved.
                return [
                    'entries' => 0,
                    'histories' => 0,
                ];
            }

            $import->delete();

            return [
                'entries' => 0,
                'histories' => 1,
            ];
        });
    }
}
